<?php
declare(strict_types=1);

namespace RicardoBoss\PhpSeq;

use AssertionError;
use PHPUnit\Framework\TestCase;

/**
 * @covers \RicardoBoss\PhpSeq\SeqLoggerConfiguration
 *
 * @internal
 */
final class SeqLoggerConfigurationTest extends TestCase
{
	public function testThrowsForNegativeBacklogLimit(): void
	{
		$this->expectException(AssertionError::class);
		$this->expectExceptionMessage("backlogLimit must be >= 0");

		new SeqLoggerConfiguration(-1);
	}
}
