<?php
declare(strict_types=1);

namespace RicardoBoss\PhpSeq\Contract;

use JetBrains\PhpStorm\ExpectedValues;
use Psr\Log\LoggerInterface;
use RicardoBoss\PhpSeq\SeqEvent;
use RicardoBoss\PhpSeq\SeqLogLevel;

interface SeqLogger extends LoggerInterface
{
	public function setMinimumLogLevel(#[ExpectedValues(valuesFromClass: SeqLogLevel::class)] ?string $level): void;

	public function getMinimumLogLevel(): ?string;

	public function send(SeqEvent $event, SeqEvent ...$events): void;

	public function flush(): void;
}
