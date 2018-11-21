<?php

namespace uhin\laravel_api;

use Exception;
use InvalidArgumentException;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Class RabbitConnectionManager
 *
 * @package uhin\laravel_api
 */
class RabbitConnectionManager
{

    /** @var null|object */
    private $connections = [];

    private static $instance = null;

    /**
     * RabbitConnectionManager constructor.
     *
     * If $autoDefaultConnect is set to true, then the default rabbit connection will be set
     * automatically.
     */
    protected function __construct()
    {
        $host = config('uhin.rabbit.host');
        $port = config('uhin.rabbit.port');
        $username = config('uhin.rabbit.username');
        $password = config('uhin.rabbit.password');
        $this->connections = [];

        if (!is_null($host) && !is_null($port) && !is_null($username) && !is_null($password)) {
            $this->addConnection('default', $host, $port, $username, $password);
        }
    }

    /**
     *
     */
    protected function __clone()
    {
        // No cloning
    }

    /**
     * @throws Exception
     */
    public function __wakeup()
    {
        throw new Exception("Deserialization of a singleton is not allowed.");
    }

    /**
     * @return null|RabbitConnectionManager
     */
    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new RabbitConnectionManager();
        }
        return self::$instance;
    }

    /**
     *
     */
    public function __destruct() {
        foreach ($this->connections as $connectionName => $connection) {
            $this->removeConnection($connectionName);
        }
    }

    /**
     * Adds a new connection.
     * @param string $name
     * @param string $host
     * @param string $port
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function addConnection(string $name, string $host, string $port, string $username, string $password) {
        // host
        if ($host === null) {
            throw new InvalidArgumentException("RabbitMQ host is undefined. Either set the RABBITMQ_HOST in the .env file or call ->setHost(...)");
        }

        // port
        if ($port === null) {
            throw new InvalidArgumentException("RabbitMQ port is undefined. Either set the RABBITMQ_PORT in the .env file or call ->setPort(...)");
        }

        // username
        if ($username === null) {
            throw new InvalidArgumentException("RabbitMQ username is undefined. Either set the RABBITMQ_USERNAME in the .env file or call ->setUsername(...)");
        }

        // password
        if ($password === null) {
            throw new InvalidArgumentException("RabbitMQ host is undefined. Either set the RABBITMQ_PASSWORD in the .env file or call ->setPassword(...)");
        }

        if (array_key_exists($name, $this->connections)) {
            return false;
        }        

        try {
            $connection = new AMQPStreamConnection($host, $port, $username, $password);

            if (is_null($connection)) {
                return false;
            }

            $this->connections[$name] = [
                'connection' => $connection,
                'channel' => $connection->channel()
            ];

            return true;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function checkConnection(string $name = 'default') {
        return array_key_exists($name, $this->connections);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function getConnection(string $name = 'default') {
        if (!array_key_exists($name, $this->connections)) {
            return false;
        }

        return $this->connections[$name]['connection'];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function getChannel(string $name = 'default') {
        if (!array_key_exists($name, $this->connections)) {
            return false;
        }

        return $this->connections[$name]['channel'];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function removeConnection(string $name) {
        if (!array_key_exists($name, $this->connections)) {
            return false;
        }

        $closingChannel = $this->getChannel($name);
        $closingConnection = $this->getConnection($name);
        
        try {
            $closingChannel->close();
            $closingConnection->close();
        } catch (Exception $e) {
            return false;
        }

        unset($this->connections[$name]);
        return true;
    }

    /**
     * @param string $name
     * @param string $host
     * @param string $port
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function updateConnection(string $name, string $host, string $port, string $username, string $password) {
        if (!$this->removeConnection($name)) {
            return false;
        }

        return $this->addConnection($name, $host, $port, $username, $password);
    }
}
