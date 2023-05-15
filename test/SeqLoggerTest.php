<?php
declare(strict_types=1);

namespace Ricardoboss\PhpSeq;

use Mockery;
use PHPUnit\Framework\TestCase;
use Ricardoboss\PhpSeq\Contract\SeqClient;

/**
 * @covers \Ricardoboss\PhpSeq\SeqLogger
 * @covers \Ricardoboss\PhpSeq\SeqEvent
 * @covers \Ricardoboss\PhpSeq\SeqLoggerConfiguration
 *
 * @internal
 */
final class SeqLoggerTest extends TestCase
{
	public function testLogWithoutBacklog(): void
	{
		$config = new SeqLoggerConfiguration(0);
		$client = Mockery::mock(SeqClient::class);
		$event = SeqEvent::information("test");

		$client
			->expects('sendEvents')
			->with(Mockery::capture($events))
			->once()
		;

		$logger = new SeqLogger($config, $client);
		$logger->log($event);

		self::assertCount(1, $events);
		self::assertSame($event, $events[0]);
	}

	public function testLogWithFlush(): void
	{
		$config = new SeqLoggerConfiguration();
		$client = Mockery::mock(SeqClient::class);
		$event = SeqEvent::information("test");

		$logger = new SeqLogger($config, $client);
		$logger->log($event);

		$client
			->expects('sendEvents')
			->with(Mockery::capture($events))
			->once()
		;

		$logger->flush();

		self::assertCount(1, $events);
		self::assertSame($event, $events[0]);
	}

	public function testDestructorCallsFlush(): void
	{
		$config = new SeqLoggerConfiguration();
		$client = Mockery::mock(SeqClient::class);
		$event = SeqEvent::information("test");

		$logger = new SeqLogger($config, $client);
		$logger->log($event);

		$client
			->expects('sendEvents')
			->with(Mockery::capture($events))
			->once()
		;

		$logger->__destruct();

		self::assertCount(1, $events);
		self::assertSame($event, $events[0]);
	}

	public function testDestructorCapturesExceptions(): void
	{
		$config = new SeqLoggerConfiguration();
		$client = Mockery::mock(SeqClient::class);
		$event = SeqEvent::information("test");

		$logger = new SeqLogger($config, $client);
		$logger->log($event);

		$client
			->expects('sendEvents')
			->with(Mockery::capture($events))
			->once()
			->andThrows(new SeqClientException("Mock Exception"))
		;

		$logger->__destruct();

		self::assertCount(1, $events);
		self::assertSame($event, $events[0]);
	}

	public function testLogImmediate(): void
	{
		$config = new SeqLoggerConfiguration();
		$client = Mockery::mock(SeqClient::class);
		$event = SeqEvent::information("test");

		$client
			->expects('sendEvents')
			->with(Mockery::capture($events))
			->once()
		;

		$logger = new SeqLogger($config, $client);
		$logger->logImmediate($event);

		self::assertCount(1, $events);
		self::assertSame($event, $events[0]);
	}
}
