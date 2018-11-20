# Version 2 Upgrade Documentation

## changes

`RabbitConnectionManager.php`

This new class helps to remove the redundancy of setting up a host, port, username, and password that were in the RabbitBuilder, RabbitReceiver, and RabbitSender classes.

`RabbitBuilder.php`, `RabbitSender.php`, `RabbitReceiver.php`

- added:
  - private property: $connectionName
  - public setter: setConnectionName(?string $connectionName = 'default')
  - if the RabbitConnectionManager is not properly set, a RuntimeException may be thrown when attempting to use it

- removed:
  - protected properties: $host, $port, $username, $password, $connection, $channel
  - public getters and setters: setHost(), getHost(), setPort(), getPort(), setUsername(), getUsername(), setPassword(), getPassword()
  - setSettings() no longer sets host, port, username, or password settings as the RabbitBuilder does not hold them
  - private functions: openConnection(), closeConnection()

`RabbitBuilder.php`

- changed:
  - constructor takes optional $connectionName = 'default'
  - constructor no longer sets private connection variables
  - createExchange(), createQueue(), bind() now uses RabbitConnectionManager instead of privately stored connection
  - execute() no longer closes its connection after creation (as the connection is managed by the RCM)

`RabbitSender.php`

- changed:
  - constructor takes optional $connectionName = 'default'
  - send() no longer accepts an optional connection or channel
  - send() uses the RabbitConnectionManager
  - send() now throws an exception on failure rather than returning false
  - sendBatch() uses the RabbitConnectionManager
  - sendBatch() logging messages no longer return host and port information
  - sendBatch() now throws an exception on failure rather than returning false

`RabbitReceiver.php`

- changed:
  - receive() uses the RabbitConnectionManager
  - receive() logging messages no longer return host and port information
  - receive() now throws an exception on failure rather than returning false