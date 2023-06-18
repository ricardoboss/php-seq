<?php
declare(strict_types=1);

namespace RicardoBoss\PhpSeq;

use JetBrains\PhpStorm\ExpectedValues;

final readonly class SeqLoggerConfiguration
{
	public function __construct(
		public int $backlogLimit = 10,
		public ?array $globalContext = null,
		#[ExpectedValues(valuesFromClass: SeqLogLevel::class)] public ?string $minimumLogLevel = null,
	) {
		assert($this->backlogLimit >= 0, "backlogLimit must be >= 0");
	}
}
