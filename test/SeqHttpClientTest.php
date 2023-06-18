<?php
declare(strict_types=1);

namespace RicardoBoss\PhpSeq;

use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @covers \RicardoBoss\PhpSeq\SeqHttpClient
 * @covers \RicardoBoss\PhpSeq\SeqEvent
 * @covers \RicardoBoss\PhpSeq\SeqHttpClientConfiguration
 * @covers \RicardoBoss\PhpSeq\SeqResponse
 *
 * @internal
 */
final class SeqHttpClientTest extends TestCase
{
	public function testSendEvents(): void
	{
		$endpoint = "http://localhost/endpoint";
		$token = "token";
		$message = "test";

		$config = new SeqHttpClientConfiguration($endpoint, $token);
		$event = SeqEvent::info($message);
		$events = [$event];

		$httpClient = Mockery::mock(ClientInterface::class);
		$requestFactory = Mockery::mock(RequestFactoryInterface::class);
		$streamFactory = Mockery::mock(StreamFactoryInterface::class);
		$requestStream = Mockery::mock(StreamInterface::class);
		$request = Mockery::mock(RequestInterface::class);
		$response = Mockery::mock(ResponseInterface::class);
		$responseStream = Mockery::mock(StreamInterface::class);

		$streamFactory
			->expects('createStream')
			->with(Mockery::capture($body))
			->once()
			->andReturns($requestStream)
		;

		$requestFactory
			->expects('createRequest')
			->with('POST', $endpoint)
			->once()
			->andReturns($request)
		;

		$request
			->expects('withHeader')
			->with('Content-Type', 'application/vnd.serilog.clef')
			->once()
			->andReturns($request)
		;

		$request
			->expects('withBody')
			->with($requestStream)
			->once()
			->andReturns($request)
		;

		$request
			->expects('withHeader')
			->with('X-Seq-ApiKey', $token)
			->once()
			->andReturns($request)
		;

		$httpClient
			->expects('sendRequest')
			->with($request)
			->once()
			->andReturns($response)
		;

		$response
			->expects('getStatusCode')
			->withNoArgs()
			->twice()
			->andReturns(201)
		;

		$response
			->expects('getBody')
			->withNoArgs()
			->once()
			->andReturns($responseStream)
		;

		$responseStream
			->expects('getContents')
			->withNoArgs()
			->once()
			->andReturns('{}')
		;

		$seqClient = new SeqHttpClient($config, $httpClient, $requestFactory, $streamFactory);
		$seqClient->sendEvents($events);

		self::assertMatchesRegularExpression("{\"@t\":\"\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.\d{6}[+-]\d{2}:\d{2}\",\"@m\":\"$message\",\"@l\":\"Information\"}\n", $body);
		self::assertEmpty($events);
	}

	public function testSendEmpty(): void
	{
		$endpoint = "http://localhost/endpoint";
		$token = "token";
		$events = [];

		$config = new SeqHttpClientConfiguration($endpoint, $token);

		$httpClient = Mockery::mock(ClientInterface::class);
		$requestFactory = Mockery::mock(RequestFactoryInterface::class);
		$streamFactory = Mockery::mock(StreamFactoryInterface::class);
		$request = Mockery::mock(RequestInterface::class);

		$requestFactory
			->expects('createRequest')
			->with('POST', $endpoint)
			->once()
			->andReturns($request)
		;

		$request
			->expects('withHeader')
			->with('Content-Type', 'application/vnd.serilog.clef')
			->once()
			->andReturns($request)
		;

		$request
			->expects('withHeader')
			->with('X-Seq-ApiKey', $token)
			->once()
			->andReturns($request)
		;

		$seqClient = new SeqHttpClient($config, $httpClient, $requestFactory, $streamFactory);
		$seqClient->sendEvents($events);

		self::assertEmpty($events);
	}

	public function testDynamicLevelControl(): void
	{
		$endpoint = "http://localhost/endpoint";
		$token = "token";
		$events = [SeqEvent::info("test")];
		$responseBody = '{"MinimumLevelAccepted":"Warning"}';

		$config = new SeqHttpClientConfiguration($endpoint, $token);

		$httpClient = Mockery::mock(ClientInterface::class);
		$requestFactory = Mockery::mock(RequestFactoryInterface::class);
		$streamFactory = Mockery::mock(StreamFactoryInterface::class);
		$request = Mockery::mock(RequestInterface::class);
		$requestStream = Mockery::mock(StreamInterface::class);
		$response = Mockery::mock(ResponseInterface::class);
		$responseStream = Mockery::mock(StreamInterface::class);

		$requestFactory
			->expects('createRequest')
			->with('POST', $endpoint)
			->once()
			->andReturns($request)
		;

		$request
			->expects('withHeader')
			->with('Content-Type', 'application/vnd.serilog.clef')
			->once()
			->andReturns($request)
		;

		$request
			->expects('withHeader')
			->with('X-Seq-ApiKey', $token)
			->once()
			->andReturns($request)
		;

		$streamFactory
			->expects('createStream')
			->with(Mockery::capture($body))
			->once()
			->andReturns($requestStream)
		;

		$request
			->expects('withBody')
			->with($requestStream)
			->once()
			->andReturns($request)
		;

		$httpClient
			->expects('sendRequest')
			->with($request)
			->once()
			->andReturns($response)
		;

		$response
			->expects('getStatusCode')
			->withNoArgs()
			->once()
			->andReturns(201)
		;

		$response
			->expects('getBody')
			->withNoArgs()
			->once()
			->andReturns($responseStream)
		;

		$responseStream
			->expects('getContents')
			->withNoArgs()
			->once()
			->andReturns($responseBody)
		;

		$seqClient = new SeqHttpClient($config, $httpClient, $requestFactory, $streamFactory);

		self::assertNull($seqClient->getMinimumLevelAccepted());

		$seqClient->sendEvents($events);

		self::assertNotEmpty($body);
		self::assertSame(SeqLogLevel::Warning, $seqClient->getMinimumLevelAccepted());
	}

	public function testWrapsNetworkException(): void
	{
		$endpoint = "http://localhost/endpoint";
		$token = "token";
		$message = "test";

		$config = new SeqHttpClientConfiguration($endpoint, $token);
		$event = SeqEvent::info($message);
		$events = [$event];

		$httpClient = Mockery::mock(ClientInterface::class);
		$requestFactory = Mockery::mock(RequestFactoryInterface::class);
		$streamFactory = Mockery::mock(StreamFactoryInterface::class);
		$stream = Mockery::mock(StreamInterface::class);
		$request = Mockery::mock(RequestInterface::class);

		$networkException = new SimpleNetworkException($request, 'network exception test');

		$streamFactory
			->expects('createStream')
			->with(Mockery::capture($body))
			->once()
			->andReturns($stream)
		;

		$requestFactory
			->expects('createRequest')
			->with('POST', $endpoint)
			->once()
			->andReturns($request)
		;

		$request
			->expects('withHeader')
			->with('Content-Type', 'application/vnd.serilog.clef')
			->once()
			->andReturns($request)
		;

		$request
			->expects('withBody')
			->with($stream)
			->once()
			->andReturns($request)
		;

		$request
			->expects('withHeader')
			->with('X-Seq-ApiKey', $token)
			->once()
			->andReturns($request)
		;

		$httpClient
			->expects('sendRequest')
			->with($request)
			->once()
			->andThrows($networkException)
		;

		$this->expectException(SeqClientException::class);
		$this->expectExceptionMessage("Failed to send request: {$networkException->getMessage()}");

		$seqClient = new SeqHttpClient($config, $httpClient, $requestFactory, $streamFactory);
		$seqClient->sendEvents($events);
	}

	public function testWrapsClientException(): void
	{
		$endpoint = "http://localhost/endpoint";
		$token = "token";
		$message = "test";
		$maxRetries = 4;

		$config = new SeqHttpClientConfiguration($endpoint, $token, $maxRetries);
		$event = SeqEvent::info($message);
		$events = [$event];

		$httpClient = Mockery::mock(ClientInterface::class);
		$requestFactory = Mockery::mock(RequestFactoryInterface::class);
		$streamFactory = Mockery::mock(StreamFactoryInterface::class);
		$stream = Mockery::mock(StreamInterface::class);
		$request = Mockery::mock(RequestInterface::class);

		$clientException = new SimpleClientException('client exception test 1');
		$clientException2 = new SimpleClientException('client exception test 2');

		$streamFactory
			->expects('createStream')
			->with(Mockery::capture($body))
			->once()
			->andReturns($stream)
		;

		$requestFactory
			->expects('createRequest')
			->with('POST', $endpoint)
			->once()
			->andReturns($request)
		;

		$request
			->expects('withHeader')
			->with('Content-Type', 'application/vnd.serilog.clef')
			->once()
			->andReturns($request)
		;

		$request
			->expects('withBody')
			->with($stream)
			->once()
			->andReturns($request)
		;

		$request
			->expects('withHeader')
			->with('X-Seq-ApiKey', $token)
			->once()
			->andReturns($request)
		;

		$httpClient
			->expects('sendRequest')
			->with($request)
			->times(2 * $maxRetries)
			->andThrowExceptions([$clientException, $clientException2])
		;

		$this->expectException(SeqClientException::class);
		$this->expectExceptionMessage("Failed to send request: {$clientException2->getMessage()}");

		$seqClient = new SeqHttpClient($config, $httpClient, $requestFactory, $streamFactory);
		$seqClient->sendEvents($events);
	}

	public static function throwsForStatusCodeData(): iterable
	{
		yield [400, '{"Error":"Bad Request"}', 'The request was malformed: Bad Request'];
		yield [401, '{"Error":"Unauthorized"}', 'Authorization is required: Unauthorized'];
		yield [403, '{"Error":"Forbidden"}', 'The provided credentials don\'t have ingestion permission: Forbidden'];
		yield [413, '{"Error":"Payload Too Large"}', 'The payload itself exceeds the configured maximum size: Payload Too Large'];
		yield [429, '{"Error":"Too Many Requests"}', 'Too many requests'];
		yield [500, '{"Error":"Internal Server Error"}', 'An internal error prevented the events from being ingested; check Seq\'s diagnostic log for more information: Internal Server Error'];
		yield [503, '{"Error":"Service Unavailable"}', 'The Seq server is starting up and can\'t currently service the request, or, free storage space has fallen below the minimum required threshold; this status code may also be returned by HTTP proxies and other network infrastructure when Seq is unreachable: Service Unavailable'];
		yield [599, '{"Error":"Undocumented"}', 'Undocumented status code. Error: Undocumented'];
		yield [599, '{}', 'Undocumented status code. Error: no problem details known'];
	}

	/**
	 * @dataProvider throwsForStatusCodeData
	 */
	public function testThrowsForStatusCode(int $responseCode, string $responseBody, string $expectedMessage): void
	{
		$endpoint = "http://localhost/endpoint";
		$token = "token";
		$message = "test";

		$config = new SeqHttpClientConfiguration($endpoint, $token);
		$event = SeqEvent::info($message);
		$events = [$event];

		$httpClient = Mockery::mock(ClientInterface::class);
		$requestFactory = Mockery::mock(RequestFactoryInterface::class);
		$streamFactory = Mockery::mock(StreamFactoryInterface::class);
		$requestStream = Mockery::mock(StreamInterface::class);
		$request = Mockery::mock(RequestInterface::class);
		$response = Mockery::mock(ResponseInterface::class);
		$responseStream = Mockery::mock(StreamInterface::class);

		$streamFactory
			->expects('createStream')
			->with(Mockery::capture($body))
			->once()
			->andReturns($requestStream)
		;

		$requestFactory
			->expects('createRequest')
			->with('POST', $endpoint)
			->once()
			->andReturns($request)
		;

		$request
			->expects('withHeader')
			->with('Content-Type', 'application/vnd.serilog.clef')
			->once()
			->andReturns($request)
		;

		$request
			->expects('withBody')
			->with($requestStream)
			->once()
			->andReturns($request)
		;

		$request
			->expects('withHeader')
			->with('X-Seq-ApiKey', $token)
			->once()
			->andReturns($request)
		;

		$httpClient
			->expects('sendRequest')
			->with($request)
			->once()
			->andReturns($response)
		;

		$response
			->expects('getStatusCode')
			->withNoArgs()
			->twice()
			->andReturns($responseCode)
		;

		$response
			->expects('getBody')
			->withNoArgs()
			->once()
			->andReturns($responseStream)
		;

		$responseStream
			->expects('getContents')
			->withNoArgs()
			->once()
			->andReturns($responseBody)
		;

		$this->expectException(SeqClientException::class);
		$this->expectExceptionMessage($expectedMessage);
		$this->expectExceptionCode($responseCode);

		$seqClient = new SeqHttpClient($config, $httpClient, $requestFactory, $streamFactory);
		$seqClient->sendEvents($events);
	}

	public function testThrowsForInvalidResponseBodies(): void
	{
		$endpoint = "http://localhost/endpoint";
		$token = "token";
		$message = "test";
		$responseCode = 599;
		$responseBody = '{';

		$config = new SeqHttpClientConfiguration($endpoint, $token);
		$event = SeqEvent::info($message);
		$events = [$event];

		$httpClient = Mockery::mock(ClientInterface::class);
		$requestFactory = Mockery::mock(RequestFactoryInterface::class);
		$streamFactory = Mockery::mock(StreamFactoryInterface::class);
		$requestStream = Mockery::mock(StreamInterface::class);
		$request = Mockery::mock(RequestInterface::class);
		$response = Mockery::mock(ResponseInterface::class);
		$responseStream = Mockery::mock(StreamInterface::class);

		$streamFactory
			->expects('createStream')
			->with(Mockery::capture($body))
			->once()
			->andReturns($requestStream)
		;

		$requestFactory
			->expects('createRequest')
			->with('POST', $endpoint)
			->once()
			->andReturns($request)
		;

		$request
			->expects('withHeader')
			->with('Content-Type', 'application/vnd.serilog.clef')
			->once()
			->andReturns($request)
		;

		$request
			->expects('withBody')
			->with($requestStream)
			->once()
			->andReturns($request)
		;

		$request
			->expects('withHeader')
			->with('X-Seq-ApiKey', $token)
			->once()
			->andReturns($request)
		;

		$httpClient
			->expects('sendRequest')
			->with($request)
			->once()
			->andReturns($response)
		;

		$response
			->expects('getStatusCode')
			->withNoArgs()
			->twice()
			->andReturns($responseCode)
		;

		$response
			->expects('getBody')
			->withNoArgs()
			->once()
			->andReturns($responseStream)
		;

		$responseStream
			->expects('getContents')
			->withNoArgs()
			->once()
			->andReturns($responseBody)
		;

		$this->expectException(SeqClientException::class);
		$this->expectExceptionMessage("Failed to decode response: Syntax error");

		$seqClient = new SeqHttpClient($config, $httpClient, $requestFactory, $streamFactory);
		$seqClient->sendEvents($events);
	}
}
