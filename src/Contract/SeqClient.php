<?php
declare(strict_types=1);

namespace RicardoBoss\PhpSeq\Contract;

use JetBrains\PhpStorm\ExpectedValues;
use RicardoBoss\PhpSeq\SeqClientException;
use RicardoBoss\PhpSeq\SeqEvent;
use RicardoBoss\PhpSeq\SeqLogLevel;

interface SeqClient
{
	/**
	 * @param SeqEvent[] $events
	 * @throws SeqClientException
	 */
	public function sendEvents(array &$events): void;

	#[ExpectedValues(valuesFromClass: SeqLogLevel::class)]
	public function getMinimumLevelAccepted(): ?string;
}
