<?php

namespace uhin\laravel_api;

use Exception;
use InvalidArgumentException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Class RabbitBuilder
 *
 * @package uhin\laravel_api
 */
class RabbitBuilder
{

    /** @var null|string */
    protected $host = null;

    /** @var null|integer */
    protected $port = null;

    /** @var null|string */
    protected $username = null;

    /** @var null|string */
    protected $password = null;

    /** @var null|AMQPStreamConnection */
    protected $connection = null;

    /** @var null|AMQPChannel */
    protected $channel = null;

    /** @var null|string */
    protected $exchange = null;

    /** @var null|string */
    protected $routingKey = null;

    /** @var null|string */
    protected $queue = null;

    public function __construct()
    {
        $this->host = config('uhin.rabbit.host');
        $this->port = config('uhin.rabbit.port');
        $this->username = config('uhin.rabbit.username');
        $this->password = config('uhin.rabbit.password');
        $this->exchange = config('uhin.rabbit.exchange');
        $this->queue = config('uhin.rabbit.queue');
        $this->routingKey = config('uhin.rabbit.routing_key');
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
     * @return null|string
     */
    public function getHost()
    {
        return $this->host;
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
     * @return int|null
     */
    public function getPort()
    {
        return $this->port;
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
     * @return null|string
     */
    public function getUsername()
    {
        return $this->username;
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

    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param null|string $exchange
     */
    public function setExchange(?string $exchange)
    {
        $this->exchange = $exchange;
    }

    /**
     * @return null|string
     */
    public function getExchange()
    {
        return $this->exchange;
    }

    /**
     * @param null|string $routingKey
     */
    public function setRoutingKey(?string $routingKey)
    {
        $this->routingKey = $routingKey;
    }

    /**
     * @return null|string
     */
    public function getRoutingKey()
    {
        return $this->routingKey;
    }

    /**
     * @param null|string $queue
     */
    public function setQueue(?string $queue)
    {
        $this->queue = $queue;
    }

    /**
     * @return null|string
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Opens a connection to Rabbit and initializes the queues. If the exchange/queues don't exist, then
     * the exchange will be created, as well as a default queue and a dead letter exchange queue that are
     * automatically bound to the exchange.
     *
     * @return boolean
     */
    private function openConnection()
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

        // Create the connection to Rabbit
        $this->connection = new AMQPStreamConnection($host, $port, $username, $password);
        $this->channel = $this->connection->channel();

        return true;
    }

    /**
     * Closes the connection that was previously opened.
     *
     * @return bool
     */
    public function closeConnection()
    {
        try {
            $this->channel->close();
            $this->connection->close();
            return true;
        } catch (Exception $e) {
            return false;
        } finally {
            $this->channel = null;
            $this->connection = null;
        }
    }

    /**
     * Builds a list of options by filtering out options that are not
     * allowed. If no options are found, then this will return null.
     *
     * @param $options
     * @param $availableOptions
     * @return null|AMQPTable
     */
    private function filterArguments($options, $availableOptions) {
        $validOptions = [];
        foreach ($availableOptions as $key) {
            if (array_key_exists($key, $options)) {
                $validOptions[$key] = $options[$key];
            }
        }
        return count($validOptions) > 0 ? new AMQPTable($validOptions) : null;
    }

    /**
     * Creates an Exchange using the options provided.
     *
     * @param string $exchangeName The name of the exchange
     * @param array $options Available options include:
     *     type: 'fanout', 'direct', or 'topic'
     *     passive: boolean
     *     durable: boolean
     *     auto_delete: boolean
     *     internal: boolean
     *     nowait: boolean
     */
    protected function createExchange(string $exchangeName, $options = [])
    {
        // Determine the options for this exchange
        $type = array_key_exists('type', $options) ? (string)$options['type'] : 'direct';
        $passive = array_key_exists('passive', $options) ? (bool)$options['passive'] : false;
        $durable = array_key_exists('durable', $options) ? (bool)$options['durable'] : true;
        $auto_delete = array_key_exists('auto_delete', $options) ? (bool)$options['auto_delete'] : false;
        $internal = array_key_exists('internal', $options) ? (bool)$options['internal'] : false;
        $nowait = array_key_exists('nowait', $options) ? (bool)$options['nowait'] : false;

        // Create the exchange
        $this->channel->exchange_declare($exchangeName, $type, $passive, $durable, $auto_delete, $internal, $nowait);
    }

    /**
     * Creates a queue using the options provided.
     *
     * @param string $queueName The name of the queue
     * @param array $options Available options include:
     *     passive: boolean
     *     durable: boolean
     *     exclusive: boolean
     *     auto_delete: boolean
     *     nowait: boolean
     *     x-message-ttl: integer
     *     x-expires: integer
     *     x-max-length: integer
     *     x-max-length-bytes: integer
     *     x-dead-letter-exchange: string
     *     x-dead-letter-routing-key: string
     *     x-max-priority: integer
     * @return array - Contains [queueName, messageCount, consumerCount]
     */
    protected function createQueue(string $queueName, $options = [])
    {
        // Determine the options for this queue
        $passive = array_key_exists('passive', $options) ? (bool)$options['passive'] : false;
        $durable = array_key_exists('durable', $options) ? (bool)$options['durable'] : true;
        $exclusive = array_key_exists('exclusive', $options) ? (bool)$options['exclusive'] : false;
        $auto_delete = array_key_exists('auto_delete', $options) ? (bool)$options['auto_delete'] : false;
        $nowait = array_key_exists('nowait', $options) ? (bool)$options['nowait'] : false;

        // Build the additional arguments
        $arguments = $this->filterArguments($options, [
            'x-message-ttl',
            'x-expires',
            'x-max-length',
            'x-max-length-bytes',
            'x-dead-letter-exchange',
            'x-dead-letter-routing-key',
            'x-max-priority',
        ]);

        // Create the queue
        return $this->channel->queue_declare($queueName, $passive, $durable, $exclusive, $auto_delete, $nowait, $arguments);
    }

    /**
     * Creates an Exchange using the options provided.
     *
     * @param string $queueName The name of the queue
     * @param string $exchangeName The name of the exchange
     * @param string $routingKey The routing key string
     * @param array $options Available options include:
     *     nowait: boolean
     */
    protected function bind(string $queueName, string $exchangeName, string $routingKey, $options = [])
    {
        // Determine the options for this binding
        $nowait = array_key_exists('nowait', $options) ? (bool)$options['nowait'] : false;

        // Bind the dead letter queue to the exchange
        $this->channel->queue_bind($queueName, $exchangeName, $routingKey, $nowait);
    }

    /**
     * This will initiate a connection to the RabbitMQ server, run the `build()` function
     * to build all rabbit exchanges, queues, and bindings, and then it will close the
     * connection.
     */
    public function execute()
    {
        // Don't run the Builder until the developer has set a username and password
        if (empty($this->username) && empty($this->password)) {
            return;
        }
        $this->openConnection();
        $this->build();
        $this->closeConnection();
    }

    /**
     * This function should build all of your RabbitMQ exchanges, queues, and bindings.
     */
    protected function build()
    {
        // Grab some files from the config settings
        $exchange = $this->exchange;
        $queue = $this->queue;
        $routingKey = $this->routingKey;

        // Determine the DLX queue info
        $dlxQueue = $queue . '.dlx';
        $dlxRoutingKey = 'dlx';

        // Build the Exchange
        $this->createExchange($exchange);

        // Build the Queues
        $this->createQueue($dlxQueue);
        $this->createQueue($queue, [
            'x-dead-letter-exchange' => $exchange,
            'x-dead-letter-routing-key' => $dlxRoutingKey,
        ]);

        // Bind the Queues to the Exchange
        $this->bind($dlxQueue, $exchange, $dlxRoutingKey);
        $this->bind($queue, $exchange, $routingKey);
    }

}
