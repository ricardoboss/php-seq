<?php
declare(strict_types=1);

namespace RicardoBoss\PhpSeq\Contract;

use Psr\Log\LoggerInterface;
use RicardoBoss\PhpSeq\SeqEvent;

interface SeqLogger extends LoggerInterface
{
	public function send(SeqEvent $event, SeqEvent ...$events): void;

	public function flush(): void;
}
