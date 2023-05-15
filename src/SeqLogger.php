<?php
declare(strict_types=1);

namespace Ricardoboss\PhpSeq;

use Ricardoboss\PhpSeq\Contract\SeqException;

class SeqLogger implements Contract\SeqLogger
{
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
		return match (strtolower($level)) {
			"verbose" => 0,
			"debug" => 1,
			"information" => 2,
			"warning" => 3,
			"error" => 4,
			"fatal" => 5,
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
	public function log(SeqEvent $event, SeqEvent ...$events): void
	{
		$this->addToBuffer([$event, ...$events], $this->eventBuffer);

		if ($this->shouldFlush()) {
			$this->flush();
		}
	}

	/**
	 * @throws SeqClientException
	 */
	public function logImmediate(SeqEvent $event, SeqEvent ...$events): void
	{
		$immediateBuffer = [];

		$this->addToBuffer([$event, ...$events], $immediateBuffer);

		$this->client->sendEvents($immediateBuffer);
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
		return $this->compareLevels($this->minimumLogLevel, $event->level) < 0;
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
}
