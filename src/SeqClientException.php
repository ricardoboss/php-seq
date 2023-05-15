<?php
declare(strict_types=1);

namespace Ricardoboss\PhpSeq;

use Ricardoboss\PhpSeq\Contract\SeqException;
use Exception;

class SeqClientException extends Exception implements SeqException
{
}
