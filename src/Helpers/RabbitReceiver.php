<?php

namespace uhin\laravel_api;

use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use RuntimeException;

/**
 * Class RabbitReceiver
 *
 * Example for reading from a queue:
 *
 *   (new RabbitReceiver)->receive(function($message) {
 *       $channel = $message->delivery_info['channel'];
 *       $deliveryTag = $message->delivery_info['delivery_tag'];
 *       $channel->basic_ack($deliveryTag);
 *   });
 *
 * @package uhin\laravel_api
 */
class RabbitReceiver
{
    /** @var null|string */
    private $queue = null;

    /** @var null|string */
    private $consumerTag = null;

    /** @var integer */
    private $prefetchCount;

    /** @var null|string */
    private $connectionName = null;

    public function __construct(RabbitBuilder $builder = null, ?string $connectionName = 'default')
    {

        if(!is_null($builder))
        {
            $builder->execute();
        }

        $this->connectionName = $connectionName;

        $this->setSettings($builder);
        $this->consumerTag = null;
        $this->prefetchCount = 1;
    }

    private function setSettings(?RabbitBuilder $builder)
    {
        /* Set the Exchange */
        if(!is_null($builder) && method_exists($builder,'getExchange') && !is_null($builder->getExchange()))
        {
            $this->exchange = $builder->getExchange();
        }
        else
        {
            $this->exchange = config('uhin.rabbit.exchange');
        }

        /* Set the RoutingKey */
        if(!is_null($builder) && method_exists($builder,'getRoutingKey') && !is_null($builder->getRoutingKey()))
        {
            $this->routingKey = $builder->getRoutingKey();
        }
        else
        {
            $this->routingKey = config('uhin.rabbit.routing_key');
        }

        /* Set the Queue */
        if(!is_null($builder) && method_exists($builder,'getQueue') && !is_null($builder->getQueue()))
        {
            $this->queue = $builder->getQueue();
        }
        else
        {
            $this->queue = config('uhin.rabbit.queue');
        }
    }

    /**
     * Override the default queue.
     *
     * @param null|string $queue
     * @return $this
     */
    public function setQueue(?string $queue)
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * Override the default consumer tag.
     *
     * @param null|string $consumerTag
     * @return $this
     */
    public function setConsumerTag(?string $consumerTag)
    {
        $this->consumerTag = $consumerTag;
        return $this;
    }

    /**
     * Override the prefetch count (1).
     *
     * @param integer $prefetchCount
     * @return $this
     */
    public function setPrefetchCount(integer $prefetchCount)
    {
        $this->prefetchCount = $prefetchCount;
        return $this;
    }

    /**
     * Override the default connection name.
     *
     * @param null|string $connectionName
     * @return $this
     */
    public function setConnectionName(?string $connectionName)
    {
        $this->connectionName = $connectionName;
        return $this;
    }

    /**
     * Start reading the messages from the queue.
     *
     * @param callable $callback The function that will be called for each message in the queue - this
     * callback will take one argument: the Rabbit message
     * @return bool
     * @throws Exception
     */
    public function receive(callable $callback)
    {
        // queue
        $queue = $this->queue;
        if ($queue === null) {
            throw new InvalidArgumentException("RabbitMQ queue is undefined. Either set the RABBITMQ_QUEUE in the .env file or call ->setQueue(...)");
        }

        // consumer tag
        $consumerTag = $this->consumerTag;
        if ($consumerTag === null || strlen($consumerTag) <= 0) {
            $consumerTag = gethostname();
        }

        // if the connectionName is set, attempt to use it
        if (is_null($this->connectionName)) {
            throw new RuntimeException("Rabbit default connection name not set.");
        }

        $rcm = RabbitConnectionManager::getInstance();

        if (!$rcm->checkConnection($this->connectionName)) {
            throw new RuntimeException("Rabbit connection not set");
        }

        $channel = $rcm->getChannel($this->connectionName);

        $this->setupGracefulStop($channel, $consumerTag);

        try {
            // Start reading the queue
            $channel->basic_qos(null, $this->prefetchCount, null);
            $channel->basic_consume($queue, $consumerTag, false, false, false, false, function ($message) use ($callback) {
                $callback($message);
            });

            // Don't exit until the callbacks have been released (basically... never exit)
            while (count($channel->callbacks))
            {
                try
                {
                    $channel->wait();
                }
                catch(Exception $e)
                {
                    /** @noinspection PhpUndefinedMethodInspection */
                    Log::debug("Error while reading queue {$this->queue}: " . $e->getMessage());
                    die();
                }
            }

            /** @noinspection PhpUndefinedMethodInspection */
            Log::debug("Queue finished reading. {$this->queue}");
            return true;
        } catch (Exception $e) {
            $message = "Error in " . __FILE__ . " line " . __LINE__ .
                " - Failed to read queue. " .
                $e->getMessage() .
                json_encode([
                    'queue' => $this->queue,
                    'consumerTag' => $this->consumerTag,
                    'prefetchCount' => $this->prefetchCount,
                ], JSON_PRETTY_PRINT);

            /** @noinspection PhpUndefinedMethodInspection */
            Log::error($message);
            throw $e;
        }
    }

    /**
     * setupGracefulStop
     * If the consumer would potentially have issues reprocessing the message we want to reduce those issues as much as possible
     * By watching signals from outside of the application we can know if the connection needs to be closed and close gracefully
     * @param $channel
     * @param $consumerTag
     */
    protected function setupGracefulStop(&$channel, &$consumerTag) {
        // Create anonymous $shutdown function because $connection isn't set on the object and there isn't a good way to access with with the pcntl_signals otherwise
        $shutdown = function($signal , $signinfo) use ($channel, $consumerTag)
        {
            Log::info('Shutting down worker gracefully');
            $channel->basic_cancel($consumerTag, false, true);
            return;
        };

        // Watch kill signals (from outside the application) asynchronously
        pcntl_async_signals(true);

        // Watch for service being killed externally
        pcntl_signal(SIGINT, $shutdown);

        // Watch for CTRL+C
        pcntl_signal(SIGTERM, $shutdown);
    }
}
