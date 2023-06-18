<?php
declare(strict_types=1);

namespace RicardoBoss\PhpSeq\Contract;

use RicardoBoss\PhpSeq\SeqClientException;
use RicardoBoss\PhpSeq\SeqEvent;

interface SeqClient
{
	/**
	 * @param SeqEvent[] $events
	 * @throws SeqClientException
	 */
	public function sendEvents(array &$events): void;

	public function getMinimumLevelAccepted(): ?string;
}
