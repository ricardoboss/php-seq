<?php
declare(strict_types=1);

namespace RicardoBoss\PhpSeq;

use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use RicardoBoss\PhpSeq\Contract\SeqClient;

/**
 * @covers \RicardoBoss\PhpSeq\SeqLogger
 * @covers \RicardoBoss\PhpSeq\SeqEvent
 * @covers \RicardoBoss\PhpSeq\SeqLoggerConfiguration
 *
 * @internal
 */
final class SeqLoggerTest extends TestCase
{
	public function testSendWithoutBacklog(): void
	{
		$config = new SeqLoggerConfiguration(0);
		$client = Mockery::mock(SeqClient::class);
		$event = SeqEvent::info("test");

		$client
			->expects('sendEvents')
			->with(Mockery::capture($events))
			->once()
		;

		$client
			->expects('getMinimumLogLevel')
			->withNoArgs()
			->twice()
			->andReturns(null)
		;

		$logger = new SeqLogger($config, $client);
		$logger->send($event);

		self::assertCount(1, $events);
		self::assertSame($event, $events[0]);
	}

	public function testSendWithFlush(): void
	{
		$config = new SeqLoggerConfiguration();
		$client = Mockery::mock(SeqClient::class);
		$event = SeqEvent::info("test");

		$client
			->expects('getMinimumLogLevel')
			->withNoArgs()
			->twice()
			->andReturns(null)
		;

		$client
			->expects('sendEvents')
			->with(Mockery::capture($events))
			->once()
		;

		$logger = new SeqLogger($config, $client);
		$logger->send($event);
		$logger->flush();

		self::assertCount(1, $events);
		self::assertSame($event, $events[0]);
	}

	public function testDestructorCallsFlush(): void
	{
		$config = new SeqLoggerConfiguration();
		$client = Mockery::mock(SeqClient::class);
		$event = SeqEvent::info("test");

		$client
			->expects('getMinimumLogLevel')
			->withNoArgs()
			->twice()
			->andReturns(null)
		;

		$client
			->expects('sendEvents')
			->with(Mockery::capture($events))
			->once()
		;

		$logger = new SeqLogger($config, $client);
		$logger->send($event);
		$logger->__destruct();

		self::assertCount(1, $events);
		self::assertSame($event, $events[0]);
	}

	public function testDestructorCapturesExceptions(): void
	{
		$config = new SeqLoggerConfiguration();
		$client = Mockery::mock(SeqClient::class);
		$event = SeqEvent::info("test");

		$client
			->expects('getMinimumLogLevel')
			->withNoArgs()
			->once()
			->andReturns(null)
		;

		$client
			->expects('sendEvents')
			->with(Mockery::capture($events))
			->once()
			->andThrows(new SeqClientException("Mock Exception"))
		;

		$logger = new SeqLogger($config, $client);
		$logger->send($event);
		$logger->__destruct();

		self::assertCount(1, $events);
		self::assertSame($event, $events[0]);
	}

	public static function compareLevelsData(): iterable
	{
		yield [null, null, 0];
		yield ['', null, 1];
		yield [null, '', -1];
		yield ['', '', 0];
		yield ['a', 'b', 0];
		yield [LogLevel::INFO, SeqLogLevel::Information, 0];
		yield [LogLevel::INFO, SeqLogLevel::Warning, -1];
		yield [LogLevel::WARNING, SeqLogLevel::Information, 1];
		yield [LogLevel::DEBUG, LogLevel::INFO, -1];
		yield [LogLevel::DEBUG, LogLevel::EMERGENCY, -1];
	}

	/**
	 * @dataProvider compareLevelsData
	 */
	public function testCompareLevels(?string $a, ?string $b, int $expected): void
	{
		$actual = SeqLogger::compareLevels($a, $b);

		self::assertSame($expected, $actual);
	}

	public function testGlobalContext(): void
	{
		$globalContext = ['foo' => 'bar'];
		$localContext = ['baz' => 'qux'];

		$loggerConfiguration = new SeqLoggerConfiguration(globalContext: $globalContext);
		$clientMock = Mockery::mock(SeqClient::class);

		$clientMock
			->expects('getMinimumLogLevel')
			->withNoArgs()
			->twice()
			->andReturns(null)
		;

		$clientMock
			->expects('sendEvents')
			->with(Mockery::capture($events))
			->once()
		;

		$logger = new SeqLogger($loggerConfiguration, $clientMock);

		$logger->send(
			SeqEvent::info("test", context: $localContext),
			SeqEvent::info("test2"),
		);

		$logger->flush();

		self::assertCount(2, $events);
		self::assertSame($localContext + $globalContext, $events[0]->context);
		self::assertSame($globalContext, $events[1]->context);
	}

	public function testConfiguredMinimumLogLevel(): void
	{
		$loggerConfiguration = new SeqLoggerConfiguration(minimumLogLevel: SeqLogLevel::Warning);
		$clientMock = Mockery::mock(SeqClient::class);

		$clientMock
			->expects('getMinimumLogLevel')
			->withNoArgs()
			->twice()
			->andReturns(null)
		;

		$clientMock
			->expects('sendEvents')
			->with(Mockery::capture($events))
			->once()
		;

		$logger = new SeqLogger($loggerConfiguration, $clientMock);

		$logger->send(
			SeqEvent::info("test"),
			SeqEvent::info("test2"),
			SeqEvent::warning("test3"),
			SeqEvent::fatal("test4"),
			SeqEvent::debug("test5"),
		);

		$logger->flush();

		self::assertCount(2, $events);
		self::assertSame("test3", $events[0]->message);
		self::assertSame(SeqLogLevel::Warning, $events[0]->level);
		self::assertSame("test4", $events[1]->message);
		self::assertSame(SeqLogLevel::Fatal, $events[1]->level);

		$logger->setMinimumLogLevel(SeqLogLevel::Information);

		$logger->send(
			SeqEvent::debug("test6"),
			SeqEvent::info("test7"),
			SeqEvent::warning("test8"),
		);

		$logger->flush();

		self::assertCount(4, $events);
		self::assertSame("test7", $events[2]->message);
		self::assertSame(SeqLogLevel::Information, $events[2]->level);
		self::assertSame("test8", $events[3]->message);
		self::assertSame(SeqLogLevel::Warning, $events[3]->level);
	}

	public function testDynamicLevelControl(): void
	{
		$loggerConfiguration = new SeqLoggerConfiguration(minimumLogLevel: null);
		$clientMock = Mockery::mock(SeqClient::class);

		$clientMock
			->expects('getMinimumLogLevel')
			->withNoArgs()
			->times(5)
			->andReturns(SeqLogLevel::Warning, SeqLogLevel::Error, SeqLogLevel::Error, null, null)
		;

		$clientMock
			->expects('sendEvents')
			->with(Mockery::capture($events))
			->once()
		;

		$logger = new SeqLogger($loggerConfiguration, $clientMock);

		self::assertSame(SeqLogLevel::Warning, $logger->getMinimumLogLevel());

		$logger->send(
			SeqEvent::info("test"),
			SeqEvent::info("test2"),
			SeqEvent::warning("test3"),
			SeqEvent::fatal("test4"),
			SeqEvent::debug("test5"),
		);

		$logger->flush();

		self::assertSame(SeqLogLevel::Error, $logger->getMinimumLogLevel());

		self::assertCount(2, $events);
		self::assertSame("test3", $events[0]->message);
		self::assertSame(SeqLogLevel::Warning, $events[0]->level);
		self::assertSame("test4", $events[1]->message);
		self::assertSame(SeqLogLevel::Fatal, $events[1]->level);

		$logger->send(
			SeqEvent::debug("test6"),
			SeqEvent::warning("test7"),
			SeqEvent::error("test8"),
		);

		$logger->flush();

		self::assertSame(SeqLogLevel::Error, $logger->getMinimumLogLevel());

		self::assertCount(3, $events);
		self::assertSame("test8", $events[2]->message);
		self::assertSame(SeqLogLevel::Error, $events[2]->level);

		$logger->send(
			SeqEvent::info("test9"),
		);

		$logger->flush();

		self::assertNull($logger->getMinimumLogLevel());

		self::assertCount(3, $events);
	}

	public function testLog(): void
	{
		$loggerConfiguration = new SeqLoggerConfiguration();
		$clientMock = Mockery::mock(SeqClient::class);
		$exception = new SimpleToStringException("message");

		$clientMock
			->expects('getMinimumLogLevel')
			->withNoArgs()
			->times(3)
			->andReturns(null)
		;

		$clientMock
			->expects('sendEvents')
			->with(Mockery::capture($events))
			->twice()
		;

		$logger = new SeqLogger($loggerConfiguration, $clientMock);
		$logger->log(LogLevel::INFO, "message");
		$logger->flush();

		self::assertCount(1, $events);
		self::assertSame("message", $events[0]->message);

		$logger->log(LogLevel::ERROR, "error", ['exception' => $exception]);
		$logger->flush();

		self::assertCount(2, $events);
		self::assertSame("error", $events[1]->message);
		self::assertSame($exception, $events[1]->exception);
	}
}
