<?php
declare(strict_types=1);

namespace RicardoBoss\PhpSeq;

use AssertionError;
use PHPUnit\Framework\TestCase;

/**
 * @covers \RicardoBoss\PhpSeq\SeqHttpClientConfiguration
 *
 * @internal
 */
final class SeqHttpClientConfigurationTest extends TestCase
{
	public function testThrowsForEmptyEndpoint(): void
	{
		$this->expectException(AssertionError::class);
		$this->expectExceptionMessage("endpoint must be non-empty");

		new SeqHttpClientConfiguration("");
	}

	public function testThrowsForEmptyApiKey(): void
	{
		$this->expectException(AssertionError::class);
		$this->expectExceptionMessage("If an API key is given, it must be non-empty");

		new SeqHttpClientConfiguration("endpoint", "");
	}

	public function testThrowsForNegativeMaxRetries(): void
	{
		$this->expectException(AssertionError::class);
		$this->expectExceptionMessage("maxRetries must be >= 0");

		new SeqHttpClientConfiguration("endpoint", maxRetries: -1);
	}
}
