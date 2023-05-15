<?php
declare(strict_types=1);

namespace Ricardoboss\PhpSeq\Contract;

use Ricardoboss\PhpSeq\SeqClientException;
use Ricardoboss\PhpSeq\SeqEvent;

interface SeqClient
{
	/**
	 * @param SeqEvent[] $events
	 * @throws SeqClientException
	 */
	public function sendEvents(array &$events): void;
}
