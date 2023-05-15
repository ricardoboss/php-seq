<?php
declare(strict_types=1);

namespace RicardoBoss\PhpSeq;

final readonly class SeqClientConfiguration {
	public function __construct(
		public string $endpoint,
		public ?string $apiKey = null,
		public int $maxRetries = 3,
	) {
		assert($this->endpoint !== "", "endpoint must be non-empty");
		assert($this->apiKey === null || $this->apiKey !== "", "If an API key is given, it must be non-empty");
		assert($this->maxRetries >= 0, "maxRetries must be >= 0");
	}
}
