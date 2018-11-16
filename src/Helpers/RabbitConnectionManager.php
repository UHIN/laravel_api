<?php

namespace uhin\laravel_api;

use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class RabbitConnectionManager
 *
 * Example for reading from a queue:
 *
 *   (new RabbitConnectionManager)->send([
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
class RabbitConnectionManager
{

    /** @var null|object */
    private $connections = [];

    //create the private instance variable
    private static $instance=null;

    /**
     * RabbitConnectionManager constructor.
     *
     * If $autoDefaultConnect is set to true, then the default rabbit connection will be set
     * automatically.
     *
     * @param bool $autoDefaultConnect
     */
    protected function __construct()
    {
        $host = config('uhin.rabbit.host');
        $port = config('uhin.rabbit.port');
        $username = config('uhin.rabbit.username');
        $password = config('uhin.rabbit.password');

        if (!is_null($host) && !is_null($port) && !is_null($username) && !is_null($password)) {
            $this->addConnection('default', $host, $port, $username, $password);
        }
    }

    protected function __clone()
    {
        // No cloning
    }

    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }

    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new RabbitConnectionManager($autoDefaultConnect);
        }
        return self::$instance;
    }

    public function __destruct() {
        //
        foreach ($connections as $connectionName => $connection) {
            $this->removeConnection($connectionName);
        }
    }

    /**
     * Adds a new connection.
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

        if (array_key_exists($name, $connections)) {
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

    public function checkConnection(string $name = 'default') {
        return array_key_exists($name, $connections);
    }

    public function getConnection(string $name = 'default') {
        if (!array_key_exists($name, $connections)) {
            return false;
        }

        return $connections[$name]->connection;
    }

    public function getChannel(string $name = 'default') {
        if (!array_key_exists($name, $connections)) {
            return false;
        }

        return $connections[$name]->channel;
    }

    public function removeConnection(string $name) {
        if (!array_key_exists($name, $connections)) {
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

        unset($connections[$name]);
        return true;
    }

    public function updateConnection(string $name, string $host, string $port, string $username, string $password) {
        if (!$this->removeConnection($name)) {
            return false;
        }

        return $this->addConnection($name, $host, $port, $username, $password);
    }
}
