# laravel_api
Devs: To create a new release for composer you need to make your commit, 
add a tag (new version number), and then push the commit and the tag. 

In PHPStorm: Commit, click on log, right click on commit and add tag, push (check push tags).



For development: 

In a service using this library you will make two changes to the composer.json to do development.

First change the version of the require for this library to dev-master

Second add this block of code to your composer.json (editing the path):

"repositories": [
        {
            "type": "path",
            "url": "/Users/rmclelland/Projects/laravel_api"
        }
    ]


# Using Rabbit

## Rabbit Builders

```php artisan uhin:make:rabbit-builder```

The constructor stubs out the configuration for the builder, modify the constructor to suit your needs. In previous versions of the builder the changes were made i nthe builder method. This is not longer required but if needs be the builder can still be overridden.

```    
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
```


## Rabbit Sender

### Single Messages

To send a message to RabbitMQ there are a few ways to do so. The most basic method uses the default exchange, and routing key from the ```.env```. 

```php
(new RabbitSender)->send(json_encode([
       'job_id' => '12312312',
       'source' => 'sftp',
       'filename' => 'test-file.x12',
       'data' => 'alskfl'
   ]));
```

Another method to send to RabbitMQ takes a RabbitBuilder as a parameter. The sender can now use the parameters of the builder to determine the exchange and routing key. 

```php        
$rabbit = new RabbitSender(true, new MPILookupQueueBuilder());
$rabbit->send(json_encode($message));
```  

The properties of a sender can also be overridden. 

```php        
$rabbit->setExchange(config('uhin.workers.lookup_mpi_exchange'));
$rabbit->setRoutingKey(config('uhin.workers.lookup_mpi_routing_key'));
```  

### Batch Sending

Same usage as single message sending. The ```sendBatch()``` method takes an array of messsages and send them in batch. Performance for larger quantities of messages is significantly better.

## Rabbit Receiver

The receiver can also take a builder and fills the properties for connecting to RabbitMQ. 

## Starting/Stopping/Draining Workers

### Starting 

```php artisan uhin:workers:start```

### Stopping 

```php artisan uhin:workers:stop```

### Drain 

```php artisan uhin:workers:drain```