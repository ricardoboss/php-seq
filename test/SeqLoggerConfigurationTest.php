<?php
declare(strict_types=1);

namespace Ricardoboss\PhpSeq;

use AssertionError;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ricardoboss\PhpSeq\SeqLoggerConfiguration
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
