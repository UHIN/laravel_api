# laravel_api
Devs: To create a new release for composer you need to make your commit, 
add a tag (new version number), and then push the commit and the tag. 

In PHPStorm: Commit, click on log, right click on commit and add tag, push (check push tags).



For development: 

In a service using this library you will make two changes to the composer.json to do development.

First change the version of the require for this library to either: "dev-master", "1.x-dev", or "2.x-dev" - depending on which branch you are working on.

Second add this block of code to your composer.json (editing the path):

"repositories": [
        {
            "type": "path",
            "url": "/Users/rmclelland/Projects/laravel_api"
        }
    ]

# Upgrade guides

Review these documents when upgrading versions.

[V1 to V2 Upgrade Guide](./docs/v2-upgrade-notes.md)

# Using Rabbit

## Rabbit Builders

```php artisan uhin:make:rabbit-builder```

The constructor stubs out the configuration for the builder. Modify the constructor to suit your needs. In previous versions of the builder the changes were made in the builder method. This is no longer required but, if needs be, the builder can still be overridden.

```php  
public function __construct()
{
        parent::__construct();

        // You can overwrite these parent values
        // $this->exchange = config('uhin.rabbit.exchange');
        // $this->queue = config('uhin.rabbit.queue');
        // $this->routingKey = config('uhin.rabbit.routing_key');
        // $this->connectionName = 'default';
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


# Using Twilio SendGrid

First, make sure that you have specified the following config values. The `SendGridTemplate` class will use these configs:
- `config('mail.from.address')`
- `config('mail.from.name')`
- `config('mail.sendgrid.api-key')`

## Templates

Example of how to send an email using a SendGrid email template and the `SendGridTemplate` class:

```php
$templateId = config('mail.sendgrid.template.test');
$email = new SendGridTemplate($templateId);

// Send the email to user1, and CC user2 and user3
$metaDataA = new \SendGrid\Mail\Personalization();
$metaDataA->addDynamicTemplateData('sendgrid_var_1', 'custom data value 1');
$metaDataA->addDynamicTemplateData('sendgrid_var_2', 'custom data value 2');
$metaDataA->addCc(new \SendGrid\Mail\Cc('user2@test.com', 'User 2'));
$metaDataA->addCc(new \SendGrid\Mail\Cc('user3@test.com', 'User 3'));
$email->addRecipient('user1@test.com', 'User 1', $metaDataA);

// Send the email to user4, and BCC user5
$metaDataB = new \SendGrid\Mail\Personalization();
$metaDataB->addDynamicTemplateData('sendgrid_var_1', 'custom data value 3');
$metaDataB->addDynamicTemplateData('sendgrid_var_2', 'custom data value 4');
$metaDataB->addBcc(new \SendGrid\Mail\Bcc('user5@test.com', 'User 5'));
$email->addRecipient('user4@test.com', 'User 4', $metaDataB);

// Send out the email template with data attached to it
$email->send();
```
