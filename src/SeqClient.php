<?php
declare(strict_types=1);

namespace Ricardoboss\PhpSeq;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

class SeqClient {
	/**
	 * @var list<SeqEvent>
	 */
	private array $batch = [];

	public function __construct(
		private readonly SeqConfiguration $config,
		private readonly ClientInterface $client,
		private readonly RequestFactoryInterface $requestFactory,
	) {}

	/**
	 * @throws SeqException
	 */
	public function log(SeqEvent ...$event): void
	{
		foreach ($event as $e) {
			$this->batch[] = $e;
		}

		if ($this->shouldSendBatch()) {
			$this->sendBatch();
		}
	}

	private function shouldSendBatch(): bool
	{
		return count($this->batch) > $this->config->backlogLimit;
	}

	/**
	 * @throws SeqException
	 */
	public function sendBatch(): void
	{
		if (count($this->batch) === 0) {
			return;
		}

		try {
			$body = "";
			while ($line = array_shift($this->batch)) {
				$body .= json_encode($line, JSON_THROW_ON_ERROR) . "\n";
			}
		} catch (JsonException $e) {
			throw new SeqException("Failed to encode batch", previous: $e);
		}

		$request = $this->requestFactory->createRequest("POST", $this->config->url);
		try {
			$response = $this->client->sendRequest($request);
		} catch (ClientExceptionInterface $e) {
			throw new SeqException("Failed to send batch", previous: $e);
		}
	}
}
