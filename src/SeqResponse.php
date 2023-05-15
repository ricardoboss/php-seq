<?php
declare(strict_types=1);

namespace Ricardoboss\PhpSeq;

final readonly class SeqResponse {
	public function __construct(
		public ?string $minimumLevelAccepted = null,
	) {}

	public static function fromJson(string $json): self
	{
		$response = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

		assert(is_array($response), 'Response must be an array');

		return new self($response['MinimumLevelAccepted'] ?? null);
	}
}
