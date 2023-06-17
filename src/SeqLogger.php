<?php
declare(strict_types=1);

namespace RicardoBoss\PhpSeq;

use JetBrains\PhpStorm\ExpectedValues;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use RicardoBoss\PhpSeq\Contract\SeqException;
use Stringable;
use Throwable;

class SeqLogger implements Contract\SeqLogger
{
	use LoggerTrait;

	public static function compareLevels(?string $a, ?string $b): int {
		if ($a === $b) {
			return 0;
		}

		if ($a === null) {
			return -1;
		}

		if ($b === null) {
			return 1;
		}

		$aLevel = self::levelToInt($a);
		$bLevel = self::levelToInt($b);

		return $aLevel - $bLevel;
	}

	public static function levelToInt(string $level): int {
		return match ($level) {
			SeqLogLevel::Verbose => 0,
			SeqLogLevel::Debug, LogLevel::DEBUG => 1,
			SeqLogLevel::Information, LogLevel::INFO => 2,
			LogLevel::NOTICE => 3,
			SeqLogLevel::Warning, LogLevel::WARNING => 4,
			SeqLogLevel::Error, LogLevel::ERROR => 5,
			SeqLogLevel::Fatal, LogLevel::CRITICAL => 6,
			LogLevel::ALERT => 7,
			LogLevel::EMERGENCY => 8,
		};
	}

	/**
	 * @var list<SeqEvent>
	 */
	private array $eventBuffer = [];

	private readonly ?array $globalContext;

	public function __construct(
		private readonly SeqLoggerConfiguration $config,
		private readonly Contract\SeqClient $client,
		#[ExpectedValues(valuesFromClass: SeqLogLevel::class)]
		private ?string $minimumLogLevel = null,
	) {
		if ($this->config->globalContext !== null) {
			$this->globalContext = $this->config->globalContext;
		} else {
			$this->globalContext = null;
		}
	}

	public function __destruct()
	{
		try {
			$this->flush();
		} catch (SeqException) {
			// ignore
		}
	}

	/**
	 * @throws SeqClientException
	 */
	public function send(SeqEvent $event, SeqEvent ...$events): void
	{
		$this->addToBuffer([$event, ...$events], $this->eventBuffer);

		if ($this->shouldFlush()) {
			$this->flush();
		}
	}

	/**
	 * @param list<SeqEvent> $events
	 * @param list<SeqEvent> $buffer
	 */
	protected function addToBuffer(array $events, array &$buffer): void
	{
		foreach ($events as $event) {
			$event = $event->withAddedContext($this->globalContext);

			if (!$this->shouldLog($event)) {
				continue;
			}

			$buffer[] = $event;
		}
	}

	protected function shouldLog(SeqEvent $event): bool
	{
		return self::compareLevels($this->minimumLogLevel, $event->level) < 0;
	}

	protected function shouldFlush(): bool
	{
		return count($this->eventBuffer) >= $this->config->backlogLimit;
	}

	/**
	 * @throws SeqClientException
	 */
	public function flush(): void
	{
		$this->client->sendEvents($this->eventBuffer);
	}

	public function log(
		#[ExpectedValues(valuesFromClass: LogLevel::class)]
		$level,
		Stringable|string $message,
		array $context = [],
	): void
	{
		assert(is_string($level) || $level instanceof Stringable);

		$strLevel = (string)$level;

		// MAYBE: throw exception if level is none of the known log levels, as the specification demands it

		$exception = null;
		if (array_key_exists('exception', $context) && $context['exception'] instanceof Throwable) {
			$exception = $context['exception'];
			unset($context['exception']);
		}

		$event = SeqEvent::now($message, $strLevel, $exception, $context);

		$this->send($event);
	}
}
