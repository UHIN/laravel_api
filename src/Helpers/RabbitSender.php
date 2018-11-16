<?php

namespace uhin\laravel_api;

use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;

/**
 * Class RabbitSender
 *
 * Example for reading from a queue:
 *
 *   (new RabbitSender)->send([
 *       'type' => 'file',
 *       'id' => 'V1-FILE-ARCHIVE-GUID-123123-12312312',
 *       'source' => 'sftp',
 *       'filename' => 'test-file.x12',
 *       'data' => 'base64fileherewithnobase64inthebeginning',
 *       'datetime' => 'ATOM UTC'
 *   ]);
 *
 * @package uhin\laravel_api
 */
class RabbitSender
{
    /** @var null|string */
    private $exchange = null;

    /** @var null|string */
    private $routingKey = null;

    /** @var null|string */
    private $connectionName = null;

    private $queue = null;

    /**
     * RabbitSender constructor.
     *
     * If $autoBuild is set to true, then the rabbit exchange/queue structure will attempt
     * to be built in this constructor. You can turn this off by passing $autoBuild=false to
     * bypass the exchange/queue creation.
     *
     * You may also use your own custom RabbitBuilder by using the artisan command uhin:make:rabbit-builder
     * and then passing in an instance of your custom builder to this method.
     *
     * @param bool $autoBuild
     * @param null|RabbitBuilder $builder
     * @param null|string $connectionName
     */
    public function __construct(bool $autoBuild = true, ?RabbitBuilder $builder = null, ?string $connectionName = 'default')
    {
        /* If no builder is passed create a default one */
        if ($builder === null)
        {
            $builder = new RabbitBuilder();
        }

        /* If requested create the queues and exchange. */
        if ($autoBuild)
        {
            $builder->execute();
        }

        $this->connectionName = $connectionName;

        /* Set the connection details from ether config file or from the builder */
        $this->setSettings($builder);
    }

    /**
     * @param null|RabbitBuilder $builder
     */
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
     * Override the default exchange.
     *
     * @param null|string $exchange
     * @return $this
     */
    public function setExchange(?string $exchange)
    {
        $this->exchange = $exchange;
        return $this;
    }

    /**
     * Override the default routing key.
     *
     * @param null|string $routingKey
     * @return $this
     */
    public function setRoutingKey(?string $routingKey)
    {
        $this->routingKey = $routingKey;
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
     * Sends a message to the Rabbit queue. If you want to re-use a connection to rabbit that
     * you already have, you can pass in the connection and channel. Otherwise, a new
     * connection/channel will be opened and closed on completion. If you do provide an open
     * connection/channel, they will not be closed on completion by this method
     *
     * @param string $message
     * @return bool
     * @throws Exception
     */
    public function send(string $message)
    {
        // exchange
        $exchange = $this->exchange;
        if ($exchange === null) {
            throw new InvalidArgumentException("RabbitMQ exchange is undefined. Either set the RABBITMQ_EXCHANGE in the .env file or call ->setExchange(...)");
        }

        // routing key
        $routingKey = $this->routingKey;
        if ($routingKey === null) {
            throw new InvalidArgumentException("RabbitMQ routing key is undefined. Either set the RABBITMQ_QUEUE_ROUTING_KEY in the .env file or call ->setRoutingKey(...)");
        }

        // message
        if ($message === null) {
            throw new InvalidArgumentException("RabbitMQ message is null - you must provide a non-null message to send.");
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

        try {
            // Send the message
            $amqpMessage = new AMQPMessage($message, [
                'delivery_mode' => 2,
            ]);
            $channel->basic_publish($amqpMessage, $exchange, $routingKey);

            /** @noinspection PhpUndefinedMethodInspection */
            if (config('app.debug'))
            {
                Log::debug("Message queued to Rabbit. {$this->host}:{$this->port} {$this->exchange}:{$this->routingKey}");
            }
            return true;
        } catch (Exception $e) {
            $message = "Error in " . __FILE__ . " line " . __LINE__ .
                " - Failed to publish message. " .
                $e->getMessage() .
                json_encode([
                    'message_length' => strlen($message),
                    'host' => $this->host,
                    'port' => $this->port,
                    'username' => $this->username,
                    'password' => $this->password,
                    'exchange' => $this->exchange,
                    'routingKey' => $this->routingKey,
                ], JSON_PRETTY_PRINT);

            /** @noinspection PhpUndefinedMethodInspection */
            Log::error($message);
            throw $e;
        }
    }

    /**
     * @param array $messages
     * @return bool
     * @throws Exception
     */
    public function sendBatch(array $messages)
    {
        // exchange
        $exchange = $this->exchange;
        if ($exchange === null) {
            throw new InvalidArgumentException("RabbitMQ exchange is undefined. Either set the RABBITMQ_EXCHANGE in the .env file or call ->setExchange(...)");
        }

        // routing key
        $routingKey = $this->routingKey;
        if ($routingKey === null) {
            throw new InvalidArgumentException("RabbitMQ routing key is undefined. Either set the RABBITMQ_QUEUE_ROUTING_KEY in the .env file or call ->setRoutingKey(...)");
        }

        // message
        if (empty($messages)) {
            throw new InvalidArgumentException("RabbitMQ messages is empty - you must provide a non-empty array of messages to send.");
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

        try {
            // Send the messages
            foreach ($messages as $message)
            {
                $amqpMessage = new AMQPMessage($message, [
                    'delivery_mode' => 2,
                ]);

                $channel->batch_basic_publish($amqpMessage, $exchange, $routingKey);
            }

            $channel->publish_batch();

            /** @noinspection PhpUndefinedMethodInspection */
            if (config('app.debug'))
            {
                Log::debug("Message queued to Rabbit. {$this->host}:{$this->port} {$this->exchange}:{$this->routingKey}");
            }
            return true;
        } catch (Exception $e) {
            $message = "Error in " . __FILE__ . " line " . __LINE__ .
                " - Failed to publish message. " .
                $e->getMessage() .
                json_encode([
                    'message_length' => strlen($message),
                    'host' => $this->host,
                    'port' => $this->port,
                    'username' => $this->username,
                    'password' => $this->password,
                    'exchange' => $this->exchange,
                    'routingKey' => $this->routingKey,
                ], JSON_PRETTY_PRINT);

            /** @noinspection PhpUndefinedMethodInspection */
            Log::error($message);
            throw $e;
        }
    }

}
