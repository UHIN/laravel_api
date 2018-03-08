<?php

namespace uhin\laravel_api;

use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Class RabbitMQ
 *
 * Example for sending a message:
 *   (new RabbitMQ)->setMessage(json_encode([
 *       'type' => 'file',
 *       'id' => 'V1-FILE-ARCHIVE-GUID-123123-12312312',
 *       'source' => 'sftp',
 *       'filename' => 'test-file.x12',
 *       'data' => 'base64fileherewithnobase64inthebeginning',
 *       'datetime' => 'ATOM UTC'
 *   ]))->send();
 *
 *
 * Example for reading from a queue:
 *
 *   (new RabbitMQ)->receive(function($message) {
 *       $channel = $message->delivery_info['channel'];
 *       $deliveryTag = $message->delivery_info['delivery_tag'];
 *       $channel->basic_ack($deliveryTag);
 *   });
 *
 * @package uhin\laravel_api
 */
class RabbitMQ
{

    /** @var null|string */
    private $message = null;

    /** @var null|string */
    private $host = null;

    /** @var null|integer */
    private $port = null;

    /** @var null|string */
    private $username = null;

    /** @var null|string */
    private $password = null;

    /** @var null|string */
    private $exchange = null;

    /** @var null|string */
    private $queue = null;

    /** @var null|string */
    private $dlxQueue = null;

    /** @var null|string */
    private $queueRoutingKey = null;

    /** @var null|string */
    private $dlxQueueRoutingKey = null;


    public function __construct()
    {
        $this->host = config('uhin.rabbit.host');
        $this->port = config('uhin.rabbit.port');
        $this->username = config('uhin.rabbit.username');
        $this->password = config('uhin.rabbit.password');
        $this->exchange = config('uhin.rabbit.exchange');
        $this->queue = config('uhin.rabbit.queue');
        $this->dlxQueue = config('uhin.rabbit.dlx_queue');
        $this->queueRoutingKey = config('uhin.rabbit.routing_key');
        $this->dlxQueueRoutingKey = config('uhin.rabbit.dlx_routing_key');
    }

    /**
     * Sets the message to be sent.
     *
     * @param null|string $message
     * @return $this
     */
    public function setMessage(?string $message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Override the default host.
     *
     * @param null|string $host
     * @return $this
     */
    public function setHost(?string $host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * Override the default port.
     *
     * @param null|integer $port
     * @return $this
     */
    public function setPort(?integer $port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * Override the default username.
     *
     * @param null|string $username
     * @return $this
     */
    public function setUsername(?string $username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Override the default password.
     *
     * @param null|string $password
     * @return $this
     */
    public function setPassword(?string $password)
    {
        $this->password = $password;
        return $this;
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
     * Override the default dead letter exchange queue.
     *
     * @param null|string $dlxQueue
     * @return $this
     */
    public function setDlxQueue(?string $dlxQueue)
    {
        $this->dlxQueue = $dlxQueue;
        return $this;
    }

    /**
     * Override the default queue's routing key.
     *
     * @param null|string $queueRoutingKey
     * @return $this
     */
    public function setQueueRoutingKey(?string $queueRoutingKey)
    {
        $this->queueRoutingKey = $queueRoutingKey;
        return $this;
    }

    /**
     * Override the default dead letter queue's routing key.
     *
     * @param null|string $dlxQueueRoutingKey
     * @return $this
     */
    public function setDlxQueueRoutingKey(?string $dlxQueueRoutingKey)
    {
        $this->dlxQueueRoutingKey = $dlxQueueRoutingKey;
        return $this;
    }

    /**
     * Opens a connection to Rabbit and initializes the queues. If the exchange/queues don't exist, then
     * the exchange will be created, as well as a default queue and a dead letter exchange queue that are
     * automatically bound to the exchange.
     *
     * @param AMQPStreamConnection $connection
     * @param AMQPChannel $channel
     * @return array
     */
    private function openConnection(&$connection, &$channel)
    {
        // host
        $host = $this->host;
        if ($host === null) {
            throw new InvalidArgumentException("RabbitMQ host is undefined. Either set the RABBITMQ_HOST in the .env file or call ->setHost(...)");
        }

        // port
        $port = $this->port;
        if ($port === null) {
            throw new InvalidArgumentException("RabbitMQ port is undefined. Either set the RABBITMQ_PORT in the .env file or call ->setPort(...)");
        }

        // username
        $username = $this->username;
        if ($username === null) {
            throw new InvalidArgumentException("RabbitMQ username is undefined. Either set the RABBITMQ_USERNAME in the .env file or call ->setUsername(...)");
        }

        // password
        $password = $this->password;
        if ($password === null) {
            throw new InvalidArgumentException("RabbitMQ host is undefined. Either set the RABBITMQ_PASSWORD in the .env file or call ->setPassword(...)");
        }

        // exchange
        $exchange = $this->exchange;
        if ($exchange === null) {
            throw new InvalidArgumentException("RabbitMQ exchange is undefined. Either set the RABBITMQ_EXCHANGE in the .env file or call ->setExchange(...)");
        }

        // queue
        $queue = $this->queue;
        if ($queue === null) {
            throw new InvalidArgumentException("RabbitMQ queue is undefined. Either set the RABBITMQ_QUEUE in the .env file or call ->setQueue(...)");
        }

        // dlxQueue
        $dlxQueue = $this->dlxQueue;
        if ($dlxQueue === null) {
            throw new InvalidArgumentException("RabbitMQ dead letter queue is undefined. Either set the RABBITMQ_DLX_QUEUE in the .env file or call ->setDlxQueue(...)");
        }

        // queueRoutingKey
        $queueRoutingKey = $this->queueRoutingKey;
        if ($queueRoutingKey === null) {
            throw new InvalidArgumentException("RabbitMQ routing key is undefined. Either set the RABBITMQ_QUEUE_ROUTING_KEY in the .env file or call ->setQueueRoutingKey(...)");
        }

        // dlxQueue
        $dlxQueueRoutingKey = $this->dlxQueueRoutingKey;
        if ($dlxQueueRoutingKey === null) {
            throw new InvalidArgumentException("RabbitMQ dead letter queue's routing key is undefined. Either set the RABBITMQ_DLX_QUEUE_ROUTING_KEY in the .env file or call ->setDlxQueueRoutingKey(...)");
        }

        // Create the connection to Rabbit
        $connection = new AMQPStreamConnection($host, $port, $username, $password);
        $channel = $connection->channel();

        // Create the Exchange
        $channel->exchange_declare($exchange, 'direct', false, true, false, false, false);

        // Create the Dead Letter Exchange Queue
        $channel->queue_declare($dlxQueue, false, true, false, false, false);

        // Create the actual Queue with a reference to the dead letter queue
        $queueInformation = $channel->queue_declare($queue, false, true, false, false, false, new AMQPTable([
            'x-dead-letter-exchange' => $exchange,
            'x-dead-letter-routing-key' => $dlxQueueRoutingKey,
        ]));

        // Bind the queue and dlx to the exchange
        $channel->queue_bind($queue, $exchange, $queueRoutingKey, false);
        $channel->queue_bind($dlxQueue, $exchange, $dlxQueueRoutingKey, false);

        return $queueInformation;
    }

    /**
     * Closes the connection that was previously opened.
     *
     * @param AMQPStreamConnection $connection
     * @param AMQPChannel $channel
     * @return bool
     */
    private function closeConnection(&$connection, &$channel)
    {
        try {
            $channel->close();
            $connection->close();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Sends a message to the Rabbit queue.
     *
     * @return bool
     */
    public function send()
    {
        // Open the connection
        /** @var AMQPStreamConnection $connection */
        $connection = null;
        /** @var AMQPChannel $channel */
        $channel = null;
        $this->openConnection($connection, $channel);

        try {
            // Send the message
            if ($this->message === null) {
                throw new InvalidArgumentException("RabbitMQ message is null - you must call ->setMessage(...) before sending your message");
            }
            $message = new AMQPMessage($this->message, [
                'delivery_mode' => 2,
            ]);
            $channel->basic_publish($message, $this->exchange, $this->queueRoutingKey);

            // Close the connection
            $this->closeConnection($connection, $channel);

            /** @noinspection PhpUndefinedMethodInspection */
            Log::debug("Message queued to Rabbit. {$this->host}:{$this->port} {$this->exchange}.{$this->queue}");
            return true;
        } catch (Exception $e) {
            $message = "Error in " . __FILE__ . " line " . __LINE__ .
                " - Failed to queue message. " .
                $e->getMessage() .
                json_encode([
                    'message_length' => strlen($this->message),
                    'host' => $this->host,
                    'port' => $this->port,
                    'username' => $this->username,
                    'password' => $this->password,
                    'exchange' => $this->exchange,
                    'queue' => $this->queue,
                    'queueRoutingKey' => $this->queueRoutingKey,
                    'dlxQueue' => $this->dlxQueue,
                    'dlxQueueRoutingKey' => $this->dlxQueueRoutingKey,
                ], JSON_PRETTY_PRINT);

            /** @noinspection PhpUndefinedMethodInspection */
            Log::error($message);
            return false;
        }
    }

    /**
     * Start reading from messages from the queue.
     *
     * @param callable $callback The function that will be called for each message in the queue - this
     * callback will take one argument: the Rabbit message
     * @return bool
     */
    public function receive(callable $callback)
    {
        // Open the connection
        /** @var AMQPStreamConnection $connection */
        $connection = null;
        /** @var AMQPChannel $channel */
        $channel = null;
        $this->openConnection($connection, $channel);

        try {
            // Start reading the queue
            $channel->basic_qos(null, 1, null);
            $channel->basic_consume($this->queue, gethostname(), false, false, false, false, function (/** @var $message AMQPMessage */
                $message) use ($callback) {
                $callback($message);
            });
            while (count($channel->callbacks)) {
                $channel->wait();
            }

            // Close the connection
            $this->closeConnection($connection, $channel);

            /** @noinspection PhpUndefinedMethodInspection */
            Log::debug("Queue successfully read. {$this->host}:{$this->port} {$this->exchange}.{$this->queue}");
            return true;
        } catch (Exception $e) {
            $message = "Error in " . __FILE__ . " line " . __LINE__ .
                " - Failed to read queue. " .
                $e->getMessage() .
                json_encode([
                    'message_length' => strlen($this->message),
                    'host' => $this->host,
                    'port' => $this->port,
                    'username' => $this->username,
                    'password' => $this->password,
                    'exchange' => $this->exchange,
                    'queue' => $this->queue,
                    'queueRoutingKey' => $this->queueRoutingKey,
                    'dlxQueue' => $this->dlxQueue,
                    'dlxQueueRoutingKey' => $this->dlxQueueRoutingKey,
                ], JSON_PRETTY_PRINT);

            /** @noinspection PhpUndefinedMethodInspection */
            Log::error($message);
            return false;
        }
    }

    /**
     * Gets the number of consumers that are attached to the queue.
     *
     * @return integer
     */
    public function getConsumerCount() {
        // Open the connection
        /** @var AMQPStreamConnection $connection */
        $connection = null;
        /** @var AMQPChannel $channel */
        $channel = null;
        list(,,$consumerCount) = $this->openConnection($connection, $channel);

        // Return the number of consumers
        return (integer) $consumerCount;
    }

    /**
     * Gets the number of messages that are ready in the queue.
     *
     * @return integer
     */
    public function getMessageCount() {
        // Open the connection
        /** @var AMQPStreamConnection $connection */
        $connection = null;
        /** @var AMQPChannel $channel */
        $channel = null;
        list(,$messageCount,) = $this->openConnection($connection, $channel);

        // Return the number of consumers
        return (integer) $messageCount;
    }

}