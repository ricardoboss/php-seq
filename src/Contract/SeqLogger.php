<?php
declare(strict_types=1);

namespace Ricardoboss\PhpSeq\Contract;

use Ricardoboss\PhpSeq\SeqEvent;

interface SeqLogger
{
	public function log(SeqEvent $event, SeqEvent ...$events): void;

	public function flush(): void;

	public function logImmediate(SeqEvent $event, SeqEvent ...$events): void;
}
