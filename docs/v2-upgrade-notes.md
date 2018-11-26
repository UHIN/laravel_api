# Version 2 Upgrade Documentation

[To Readme](../README.md)

## Steps to Upgrade

1. For the RabbitBuilder, RabbitSender, and RabbitReceiver classes, a new $connectionName property has been created and is set via the constructor. If you have overwritten these constructors previously, ensure that they are updated to still set the connection information.
1. The following methods now potentially throw a `RuntimeException`. Verify that the code calling these methods properly accounts for this new behavior:
   - RabbitBuilder->createExchange()
   - RabbitBuilder->createQueue()
   - RabbitBuilder->bind()
   - RabbitReceiver->receiver()
   - RabbitReceiver->receive()
   - RabbitSender->send()
   - RabbitSender->sendBatch()
1. For the RabbitBuilder, RabbitSender, and RabbitReceiver classes, ensure that you are not using any of the following getter and setter methods as they no longer exist:
   - setHost(), getHost(), setPort(), getPort(), setUsername(), getUsername(), setPassword(), getPassword()
1. The RabbitReceiver and RabbitSender setSettings method no longer sets the host, port, username, or password settings. Ensure that your code is no longer dependent on this functionality
1. The RabbitSender->send() method no longer accepts a connection or channel. Update instances where this method is used and ensure existing code does not require a unique connection.
1. The following methods now throw an `Exception` on failure rather than returning false. Ensure that your code is not expecting a false value when using these methods:
   - RabbitSender->send()
   - RabbitSender->sendBatch()
   - RabbitReceiver->receive()

## Changes

`RabbitConnectionManager.php`

This new class helps to remove the redundancy of setting up a host, port, username, and password that were in the RabbitBuilder, RabbitReceiver, and RabbitSender classes.

`RabbitBuilder.php`, `RabbitSender.php`, `RabbitReceiver.php`

- added:
  - private property: $connectionName
  - public setter: setConnectionName(?string $connectionName = 'default')
  - If the RabbitConnectionManager is not properly set, a RuntimeException may be thrown when attempting to use it.

- removed:
  - protected properties: $host, $port, $username, $password, $connection, $channel
  - public getters and setters: setHost(), getHost(), setPort(), getPort(), setUsername(), getUsername(), setPassword(), getPassword()
  - private functions: openConnection(), closeConnection()
  - setSettings() no longer sets host, port, username, or password settings as the RabbitBuilder does not hold them.

`RabbitBuilder.php`

- changed:
  - Constructor takes optional $connectionName = 'default'.
  - Constructor no longer sets private connection variables.
  - createExchange(), createQueue(), bind() now uses RabbitConnectionManager instead of privately stored connection.
  - execute() no longer closes its connection after creation (as the connection is managed by the RCM).

`RabbitSender.php`

- changed:
  - Constructor takes optional $connectionName = 'default'.
  - send() no longer accepts an optional connection or channel.
  - send() uses the RabbitConnectionManager.
  - send() now throws an exception on failure rather than returning false.
  - sendBatch() uses the RabbitConnectionManager.
  - sendBatch() logging messages no longer return host and port information.
  - sendBatch() now throws an exception on failure rather than returning false.

`RabbitReceiver.php`

- changed:
  - receive() uses the RabbitConnectionManager.
  - receive() logging messages no longer return host and port information.
  - receive() now throws an exception on failure rather than returning false.