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

		return $aLevel <=> $bLevel;
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
			default => -1,
		};
	}

	/**
	 * @var list<SeqEvent>
	 */
	private array $eventBuffer = [];

	private ?string $minimumLogLevel;

	private readonly ?array $globalContext;

	public function __construct(
		private readonly SeqLoggerConfiguration $config,
		private readonly Contract\SeqClient $client,
	) {
		if ($this->config->globalContext !== null) {
			$this->globalContext = $this->config->globalContext;
		} else {
			$this->globalContext = null;
		}

		$this->minimumLogLevel = $this->client->getMinimumLevelAccepted() ?? $this->config->minimumLogLevel;
	}

	/**
	 * @throws SeqClientException
	 */
	public function __destruct()
	{
		$this->flush();
	}

	/**
	 * @throws SeqClientException
	 */
	public function send(SeqEvent $event, SeqEvent ...$events): void
	{
		/** @var SeqEvent $e */
		foreach ([$event, ...$events] as $e) {
			$e = $e->withAddedContext($this->globalContext);

			if (!$this->shouldLog($e)) {
				continue;
			}

			$this->eventBuffer[] = $e;
		}

		if ($this->shouldFlush()) {
			$this->flush();
		}
	}

	protected function shouldLog(SeqEvent $event): bool
	{
		return self::compareLevels($this->getMinimumLogLevel(), $event->level) <= 0;
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

		$newLogLevel = $this->client->getMinimumLevelAccepted();
		if ($this->getMinimumLogLevel() !== $newLogLevel) {
			$this->setMinimumLogLevel($newLogLevel);
		}
	}

	public function log(
		#[ExpectedValues(valuesFromClass: LogLevel::class)]
		$level,
		Stringable|string $message,
		?array $context = null,
	): void
	{
		assert(is_string($level) || $level instanceof Stringable);

		$strLevel = (string)$level;

		// MAYBE: throw exception if level is none of the known log levels, as the specification demands it

		$exception = null;
		if ($context !== null && array_key_exists('exception', $context) && $context['exception'] instanceof Throwable) {
			$exception = $context['exception'];
			unset($context['exception']);
		}

		if ($context !== null && count($context) === 0) {
			$context = null;
		}

		$event = SeqEvent::now($message, $strLevel, $exception, $context);

		$this->send($event);
	}

	public function setMinimumLogLevel(#[ExpectedValues(valuesFromClass: SeqLogLevel::class)] ?string $level): void {
		$this->minimumLogLevel = $level;
	}

	#[ExpectedValues(valuesFromClass: SeqLogLevel::class)]
	public function getMinimumLogLevel(): ?string {
		return $this->minimumLogLevel;
	}
}
