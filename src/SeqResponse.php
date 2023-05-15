<?php
declare(strict_types=1);

namespace Ricardoboss\PhpSeq;

use JetBrains\PhpStorm\Pure;
use JsonException;

final readonly class SeqResponse {
	#[Pure]
	public function __construct(
		public ?string $minimumLevelAccepted = null,
		public ?string $error = null,
	) {}

	/**
	 * @throws JsonException
	 */
	public static function fromJson(string $json): self
	{
		$response = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

		assert(is_array($response), 'Response must be an array');

		return new self($response['MinimumLevelAccepted'] ?? null, $response['Error'] ?? null);
	}
}
