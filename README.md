[Seq]: https://datalust.co/seq
[License]: ./LICENSE.md
[create issue]: https://github.com/ricardoboss/php-seq/issues/new

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

// 1. create an Seq client
$clientConfig = new SeqClientConfiguration("https://my-seq-host:5341/api/events/raw", "my-api-key");
$seqClient = new SeqHttpClient($clientConfig, $httpClient, $requestFactory, $streamFactory);

// 2. create the logger
$loggerConfig = new SeqLoggerConfiguration();
$logger = new SeqLogger($loggerConfig, $seqClient);

// 3. start logging!
$logger->send(SeqEvent::information("Hello from PHP!"));
```

## Contributing

Contributions in all forms are welcome! If you are missing a specific feature or something isn't working as expected,
please create an issue on GitHub: [create issue].

If you can, you are encouraged to create a pull request. Please make sure you add tests for the functionality you
add/change and make sure they pass.

## License

This project is licensed under the MIT license. For more information, see [License].
