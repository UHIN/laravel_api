<?php

namespace uhin\laravel_api;

use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

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
    private $routingKey = null;

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
     */
    public function __construct(bool $autoBuild = true, ?RabbitBuilder $builder = null)
    {
        if ($autoBuild) {
            if ($builder === null) {
                $builder = new RabbitBuilder();
            }
            $builder->execute();
        }
        $this->host = config('uhin.rabbit.host');
        $this->port = config('uhin.rabbit.port');
        $this->username = config('uhin.rabbit.username');
        $this->password = config('uhin.rabbit.password');
        $this->exchange = config('uhin.rabbit.exchange');
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
     * Opens a connection to Rabbit and initializes the queues. If the exchange/queues don't exist, then
     * the exchange will be created, as well as a default queue and a dead letter exchange queue that are
     * automatically bound to the exchange.
     *
     * @param AMQPStreamConnection $connection
     * @param AMQPChannel $channel
     * @return boolean
     */
    public function openConnection(?AMQPStreamConnection &$connection, ?AMQPChannel &$channel)
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
        $connection = new AMQPStreamConnection($host, $port, $username, $password);
        $channel = $connection->channel();

        return true;
    }

    /**
     * Closes the connection that was previously opened.
     *
     * @param AMQPStreamConnection $connection
     * @param AMQPChannel $channel
     * @return bool
     */
    public function closeConnection(?AMQPStreamConnection &$connection, ?AMQPChannel &$channel)
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
     * Sends a message to the Rabbit queue. If you want to re-use a connection to rabbit that
     * you already have, you can pass in the connection and channel. Otherwise, a new
     * connection/channel will be opened and closed on completion. If you do provide an open
     * connection/channel, they will not be closed on completion by this method
     *
     * @param string $message
     * @param null|AMQPStreamConnection $connection
     * @param null|AMQPChannel $channel
     * @return bool
     */
    public function send(string $message, ?AMQPStreamConnection $connection = null, ?AMQPChannel $channel = null)
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

        // Open the connection
        $openedConnection = false;
        if ($connection === null || $channel === null) {
            $openedConnection = $this->openConnection($connection, $channel);
        }

        try {
            // Send the message
            $amqpMessage = new AMQPMessage($message, [
                'delivery_mode' => 2,
            ]);
            $channel->basic_publish($amqpMessage, $exchange, $routingKey);

            /** @noinspection PhpUndefinedMethodInspection */
            Log::debug("Message queued to Rabbit. {$this->host}:{$this->port} {$this->exchange}:{$this->routingKey}");
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
            return false;
        } finally {
            if ($openedConnection) {
                // Close the connection
                $this->closeConnection($connection, $channel);
            }
        }
    }

}
