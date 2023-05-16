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
		$stream = Mockery::mock(StreamInterface::class);
		$request = Mockery::mock(RequestInterface::class);
		$response = Mockery::mock(ResponseInterface::class);

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
			->andReturns($response)
		;

		$response
			->expects('getStatusCode')
			->withNoArgs()
			->twice()
			->andReturns(201)
		;

		$seqClient = new SeqHttpClient($config, $httpClient, $requestFactory, $streamFactory);
		$seqClient->sendEvents($events);

		self::assertMatchesRegularExpression("{\"@t\":\"\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.\d{6}[+-]\d{2}:\d{2}\",\"@mt\":\"$message\",\"@l\":\"Information\"}\n", $body);
	}
}
