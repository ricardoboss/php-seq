<?php
declare(strict_types=1);

namespace RicardoBoss\PhpSeq;

use RicardoBoss\PhpSeq\Contract\SeqException;
use Exception;

class SeqClientException extends Exception implements SeqException
{
}
