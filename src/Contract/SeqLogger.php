<?php
declare(strict_types=1);

namespace RicardoBoss\PhpSeq\Contract;

use RicardoBoss\PhpSeq\SeqEvent;

interface SeqLogger
{
	public function log(SeqEvent $event, SeqEvent ...$events): void;

	public function flush(): void;
}
