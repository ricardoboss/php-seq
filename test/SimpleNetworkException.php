<?php
declare(strict_types=1);

namespace RicardoBoss\PhpSeq;

use Exception;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Throwable;

class SimpleNetworkException extends Exception implements NetworkExceptionInterface
{
	public function __construct(private readonly RequestInterface $request, string $message = "", int $code = 0, ?Throwable $previous = null) {
		parent::__construct($message, $code, $previous);
	}

	/**
	 * @inheritDoc
	 */
	public function getRequest(): RequestInterface {
		return $this->request;
	}
}
