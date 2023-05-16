<?php
declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RicardoBoss\PhpSeq\SeqEvent;
use RicardoBoss\PhpSeq\SeqHttpClient;
use RicardoBoss\PhpSeq\SeqHttpClientConfiguration;
use RicardoBoss\PhpSeq\SeqLogger;
use RicardoBoss\PhpSeq\SeqLoggerConfiguration;

require_once __DIR__ . '/../vendor/autoload.php';

function getPsr18Client(): ClientInterface {
	return new Client();
}

function getPsr17RequestFactory(): RequestFactoryInterface {
	return new HttpFactory();
}

function getPsr17StreamFactory(): StreamFactoryInterface {
	return new HttpFactory();
}

// To run this file, you need to have an Seq server running on localhost:5341.
// You can use the docker-compose.yml file in the project root to start the server.

//////////////////////////////////////////////////////////////////////

// 0. gather dependencies (preferably using dependency injection)
$httpClient = getPsr18Client(); // PSR-18 (HTTP Client)
$requestFactory = getPsr17RequestFactory(); // PSR-17 (HTTP Factories)
$streamFactory = getPsr17StreamFactory(); // PSR-17 (HTTP Factories)

// 1. create the Seq client
$clientConfig = new SeqHttpClientConfiguration("http://localhost:5341/api/events/raw", "my-api-key");
$seqClient = new SeqHttpClient($clientConfig, $httpClient, $requestFactory, $streamFactory);

// 2. create the logger
$loggerConfig = new SeqLoggerConfiguration();
$logger = new SeqLogger($loggerConfig, $seqClient);

// 3. start logging!
$logger->send(SeqEvent::info("Hello from PHP!"));
// or
$logger->info("Hello via PSR-3!"); // or $logger->log(\Psr\Log\LogLevel::INFO, "...");

// (optional) 4. force sending all buffered events
$logger->flush();
