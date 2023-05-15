<?php
declare(strict_types=1);

namespace Ricardoboss\PhpSeq;

use AssertionError;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ricardoboss\PhpSeq\SeqClientConfiguration
 *
 * @internal
 */
final class SeqClientConfigurationTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		ini_set("assert.exception", "1");
	}

	public function testThrowsForEmptyEndpoint(): void
	{
		$this->expectException(AssertionError::class);
		$this->expectExceptionMessage("endpoint must be non-empty");

		new SeqClientConfiguration("");
	}

	public function testThrowsForEmptyApiKey(): void
	{
		$this->expectException(AssertionError::class);
		$this->expectExceptionMessage("If an API key is given, it must be non-empty");

		new SeqClientConfiguration("endpoint", "");
	}

	public function testThrowsForNegativeMaxRetries(): void
	{
		$this->expectException(AssertionError::class);
		$this->expectExceptionMessage("maxRetries must be >= 0");

		new SeqClientConfiguration("endpoint", maxRetries: -1);
	}
}
