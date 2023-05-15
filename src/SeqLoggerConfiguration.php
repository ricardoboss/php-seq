<?php
declare(strict_types=1);

namespace RicardoBoss\PhpSeq;

final readonly class SeqLoggerConfiguration
{
	public function __construct(
		public int $backlogLimit = 10,
		public ?array $globalContext = null,
	) {
		assert($this->backlogLimit >= 0, "backlogLimit must be >= 0");
	}
}
