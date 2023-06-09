[Seq]: https://datalust.co/seq
[License]: ./LICENSE.md
[create issue]: https://github.com/ricardoboss/php-seq/issues/new
[CLEF format]: https://clef-json.org/
[message templates]: https://messagetemplates.org/
[Reified properties]: https://docs.datalust.co/docs/posting-raw-events#reified-properties
[`example`]: ./example
[guzzlehttp/guzzle]: https://packagist.org/packages/guzzlehttp/guzzle
[here on Seq's website]: https://docs.datalust.co/docs/using-serilog#dynamic-level-control
[this brief blog post]: https://nblumhardt.com/2016/02/remote-level-control-in-serilog-using-seq/

[![Unit Tests](https://github.com/ricardoboss/php-seq/actions/workflows/unit-tests.yml/badge.svg)](https://github.com/ricardoboss/php-seq/actions/workflows/unit-tests.yml)
[![](https://img.shields.io/packagist/v/ricardoboss/php-seq)](https://packagist.org/packages/ricardoboss/php-seq)

# php-seq

A PHP library for [Seq] HTTP ingestion.

## Installation

```
composer require ricardoboss/php-seq
```

## Usage

```php
// 0. gather dependencies (preferably using dependency injection)
$httpClient = getPsr18Client(); // PSR-18 (HTTP Client)
$requestFactory = getPsr17RequestFactory(); // PSR-17 (HTTP Factories)
$streamFactory = getPsr17StreamFactory(); // PSR-17 (HTTP Factories)

// 1. create the Seq client
$clientConfig = new SeqHttpClientConfiguration("https://my-seq-host:5341/api/events/raw", "my-api-key");
$seqClient = new SeqHttpClient($clientConfig, $httpClient, $requestFactory, $streamFactory);

// 2. create the logger
$loggerConfig = new SeqLoggerConfiguration();
$logger = new SeqLogger($loggerConfig, $seqClient);

// 3. start logging!
$logger->send(SeqEvent::info("Hello from PHP!"));
// or
$logger->info("Hello via PSR-3!"); // or $logger->log(\Psr\Log\LogLevel::INFO, "...");

// using message templates:
$logger->info('This is PHP {PhpVersion}', ['PhpVersion' => PHP_VERSION]);

// (optional) 4. force sending all buffered events
$logger->flush();
```

> **Note**
>
> All events get flushed automatically when the loggers `__destruct` method is called (i.e. at latest when the runtime shuts down).

You can use the example project in the [`example`] folder to try different things out.
It uses [guzzlehttp/guzzle] as its PSR implementations.

## Configuration

The configuration is split up between the actual client, sending the requests and the logger gathering events and forwarding them to the client.
This makes it possible to create multiple loggers with different contexts/minimum log levels using the same client.
Also, it enables us to implement new clients for other interfaces in the future (see “Future Scope” below).

### `SeqHttpClientConfiguration`

| Parameter     | Type           | Default | Description                                                                                                                                                                                        |
|---------------|----------------|---------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `$endpoint`   | `string`       | -       | The endpoint to use. Must not be empty. Usually has the form "https://seq-host:5341/api/events/raw"                                                                                                |
| `$apiKey`     | `string\|null` | `null`  | If your Seq instance requires authentication, you need to provide your API key here. If given, it must not be empty.                                                                               |
| `$maxRetries` | `int`          | `3`     | The number of tries before throwing an exception if sending the events fails. Exceptions implementing `\Psr\Http\Client\NetworkExceptionInterface` bypass this limit and are immediately rethrown. |

### `SeqLoggerConfiguration`

| Parameter        | Type          | Default | Description                                                                                                                                  |
|------------------|---------------|---------|----------------------------------------------------------------------------------------------------------------------------------------------|
| `$backlogLimit`  | `int`         | `10`    | The amount of events to collect before sending a batch to the server. You can explicitly flush all buffered events using the `flush` method. |
| `$globalContext` | `array\|null` | `null`  | A key-value list that gets attached to all events logged using this logger.                                                                  |

## Advanced usage

### Message templates & context

Seq supports the [message templates] syntax.
You can use it too using the context parameter:

```php
$username = "EarlyBird91";

// Will log "Created EarlyBird91 user" to Seq with the "username" attribute set to "EarlyBird91"
$logger->info("Created {username} user", ['username' => $username]);
```

> **Note**
>
> The context values will be converted to strings.
> If they aren't scalar or `Stringable` objects, they are encoded using `json_encode`.

### Custom Seq events

Seq uses the [CLEF format] for HTTP ingestion, which is used by this library.
For your convenience, you can directly access all the properties of the CLEF format using the `SeqEvent` class.

Just create a new instance and send it using the `SeqLogger` or encode it using `json_encode`:

```php
$event = new SeqEvent(
    new DateTimeImmutable(),
    "message",
    "messageTemplate",
    "level",
    new Exception("exception"),
    123,
    ['attribute' => 'rendered'],
    ['tag' => 'value'],
);

$logger->send($event);
// or
echo json_encode($event); // {"@t":"2023-05-16T12:00:01.123456+00:00","@mt":"messageTemplate",...}
```

Note that you still need to validate the event yourself if you create it that way.
You can check the requirements from Seq here: [Reified properties]

Escaping of user properties using `@` is done automatically when encoding the event to JSON.

### Minimum log level

You can specify the minimum log level a logger will buffer and send to Seq using the `$minimumLogLevel` constructor
parameter of the `SeqLoggerConfiguration` class:

```php
$config = new SeqLoggerConfiguration(minimumLogLevel: SeqLogLevel::Warning);
```

This will allow only `Warning` or more critical events to be sent to Seq (like `Error` and `Fatal`).

You can also adjust the minimum log level of the logger at runtime:

```php
$previousLogLevel = $logger->getMinimumLogLevel();

$logger->setMinimumLogLevel(SeqLogLevel::Information);
```

### Dynamic Level Control

Seq supports a scheme called "Dynamic Level Control", which allows the client to adjust its minimum log level based on
what is configured for its API key.

`php-seq` also supports this and will adjust the minimum log level of each logger based on what Seq responds.

You can read up on Dynamic Level Control [here on Seq's website] or in [this brief blog post] by Nicholos Blumhardt.

## Error handling

All exceptions thrown by this library implement the `\RicardBoss\PhpSeq\Contract\SeqException` interface.
This makes it easy to catch any exception wrapped by this library.

## Future Scope

The aim for this library is to provide simple logging for Seq in PHP and stay compatible with current PHP and Seq versions.

A possible addition for this library could be to use GELF instead of HTTP using the `sockets` extension, but this is not planned for now.

## Contributing

Contributions in all forms are welcome! If you are missing a specific feature or something isn't working as expected,
please create an issue on GitHub: [create issue].

If you can, you are encouraged to create a pull request. Please make sure you add tests for the functionality you
add/change and make sure they pass.

## License

This project is licensed under the MIT license. For more information, see [License].
