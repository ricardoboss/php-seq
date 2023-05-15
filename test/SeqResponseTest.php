<?php
declare(strict_types=1);

namespace RicardoBoss\PhpSeq;

use JsonException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \RicardoBoss\PhpSeq\SeqResponse
 *
 * @internal
 */
final class SeqResponseTest extends TestCase
{
	/**
	 * @throws JsonException
	 */
	public function testFromJson()
	{
		self::assertEquals(
			new SeqResponse(null, null),
			SeqResponse::fromJson('{}'),
		);

		self::assertEquals(
			new SeqResponse(null, null),
			SeqResponse::fromJson('{"MinimumLevelAccepted":null}'),
		);

		self::assertEquals(
			new SeqResponse("Warning", null),
			SeqResponse::fromJson('{"MinimumLevelAccepted":"Warning"}'),
		);

		self::assertEquals(
			new SeqResponse(null, "Error"),
			SeqResponse::fromJson('{"Error":"Error"}'),
		);
	}
}
