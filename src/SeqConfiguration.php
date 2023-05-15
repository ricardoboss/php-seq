<?php
declare(strict_types=1);

namespace Ricardoboss\PhpSeq;

final readonly class SeqConfiguration {
	public function __construct(
		public string $url,
		public ?string $apiKey,
		public int $backlogLimit = 10,
	) {}
}
