<?php
declare(strict_types=1);

namespace RicardoBoss\PhpSeq;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * @covers \RicardoBoss\PhpSeq\SeqEvent
 *
 * @internal
 */
final class SeqEventTest extends TestCase
{
	public static function jsonSerializeDataProvider(): iterable
	{
		$now = new DateTimeImmutable();
		$nowString = $now->format("Y-m-d\TH:i:s.uP");

		yield [$now, null, null, null, null, null, null, null, ['@t' => $nowString]];
		yield [$now, 'message', null, null, null, null, null, null, ['@t' => $nowString, '@m' => 'message']];
		yield [$now, null, 'template', null, null, null, null, null, ['@t' => $nowString, '@mt' => 'template']];
		yield [$now, null, null, 'level', null, null, null, null, ['@t' => $nowString, '@l' => 'level']];
		yield [$now, null, null, null, new SimpleToStringException("exception"), null, null, null, ['@t' => $nowString, '@x' => 'exception']];
		yield [$now, null, null, null, null, 1234, null, null, ['@t' => $nowString, '@i' => 1234]];
		yield [$now, null, null, null, null, null, [], null, ['@t' => $nowString]];
		yield [$now, null, null, null, null, null, ['a' => 'b'], null, ['@t' => $nowString, '@r' => ['a' => 'b']]];
		yield [$now, null, null, null, null, null, null, [], ['@t' => $nowString]];
		yield [$now, null, null, null, null, null, null, ['a' => 'b'], ['@t' => $nowString, 'a' => 'b']];
	}

	/**
	 * @dataProvider jsonSerializeDataProvider
	 */
	public function testJsonSerialize(DateTimeImmutable $time, ?string $message, ?string $messageTemplate, ?string $level, ?Throwable $exception, ?int $id, ?array $renderings, ?array $context, array $json): void
	{
		$event = new SeqEvent($time, $message, $messageTemplate, $level, $exception, $id, $renderings, $context);

		self::assertSame($json, $event->jsonSerialize());
	}

	public function testWithAddedContext(): void
	{
		$now = new DateTimeImmutable();

		$event = new SeqEvent($now, "message", null, null, null, null, null, ["a" => true, "b" => "data"]);

		self::assertSame(["a" => true, "b" => "data"], $event->context);

		$event2 = $event->withAddedContext(["b" => "data2", "c" => "data3"]);

		self::assertNotSame($event, $event2);
		self::assertSame(["a" => true, "b" => "data2", "c" => "data3"], $event2->context);
	}

	public static function staticConstructorsProvider(): iterable
	{
		yield ["verbose", SeqLogLevel::Verbose];
		yield ["debug", SeqLogLevel::Debug];
		yield ["info", SeqLogLevel::Information];
		yield ["warning", SeqLogLevel::Warning];
		yield ["error", SeqLogLevel::Error];
		yield ["fatal", SeqLogLevel::Fatal];
	}

	/**
	 * @dataProvider staticConstructorsProvider
	 */
	public function testStaticConstructors(string $methodName, string $expectedLevel): void
	{
		$event = SeqEvent::{$methodName}("message");

		self::assertSame($expectedLevel, $event->level);
	}
}
