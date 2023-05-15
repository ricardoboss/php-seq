<?php
declare(strict_types=1);

namespace Ricardoboss\PhpSeq;

use Exception;

/**
 * @internal
 */
class SimpleToStringException extends Exception
{
	public function __construct(string $message)
	{
		parent::__construct($message);
	}

	public function __toString(): string
	{
		return $this->getMessage();
	}
}
