<?php
declare(strict_types=1);

namespace RicardoBoss\PhpSeq;

use Exception;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Throwable;

class SimpleClientException extends Exception implements ClientExceptionInterface
{
}
