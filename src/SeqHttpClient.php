<?php
declare(strict_types=1);

namespace RicardoBoss\PhpSeq;

use JetBrains\PhpStorm\Immutable;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

#[Immutable]
class SeqHttpClient implements Contract\SeqClient
{
	public const CLEF_CONTENT_TYPE = "application/vnd.serilog.clef";
	public const SEQ_APIKEY_HEADER_NAME = "X-Seq-ApiKey";

	private readonly RequestInterface $preparedRequest;

	public function __construct(
		protected readonly SeqClientConfiguration $config,
		protected readonly ClientInterface $client,
		protected readonly RequestFactoryInterface $requestFactory,
		protected readonly StreamFactoryInterface $streamFactory,
	)
	{
		$request = $this->requestFactory
			->createRequest("POST", $this->config->endpoint)
			->withHeader("Content-Type", self::CLEF_CONTENT_TYPE);

		if ($this->config->apiKey !== null) {
			$request = $request->withHeader(self::SEQ_APIKEY_HEADER_NAME, $this->config->apiKey);
		}

		$this->preparedRequest = $request;
	}

	/**
	 * @throws SeqClientException
	 */
	public function sendEvents(array &$events): void
	{
		$body = $this->collapseEvents($events);
		if ($body === "") {
			return;
		}

		$request = $this->buildRequest($body);
		$response = $this->sendRequest($request);
		$this->handleResponse($response);
	}

	/**
	 * @throws SeqClientException
	 */
	protected function collapseEvents(array &$events): string
	{
		if (count($events) === 0) {
			return "";
		}

		try {
			$contents = "";

			while ($event = array_shift($events)) {
				assert($event instanceof SeqEvent, "Event must be an instance of SeqEvent");

				$contents .= json_encode($event, JSON_THROW_ON_ERROR) . "\n";
			}

			return $contents;
		} catch (JsonException $e) {
			throw new SeqClientException("Failed to encode event", previous: $e);
		}
	}

	protected function buildRequest(string $body): RequestInterface
	{
		$stream = $this->streamFactory->createStream($body);

		return $this->preparedRequest->withBody($stream);
	}

	/**
	 * @throws SeqClientException
	 */
	protected function sendRequest(RequestInterface $request): ResponseInterface
	{
		$tries = 0;
		$response = null;
		$lastException = null;
		do {
			try {
				$response = $this->client->sendRequest($request);
			} catch (NetworkExceptionInterface $ne) {
				$lastException = $ne;

				break;
			} catch (ClientExceptionInterface $ce) {
				$lastException = $ce;
			}
		} while (!in_array($response?->getStatusCode(), [201, 429]) && ++$tries < $this->config->maxRetries);

		if ($lastException !== null) {
			throw new SeqClientException("Failed to send request: {$lastException->getMessage()}", previous: $lastException);
		}

		return $response;
	}

	/**
	 * @throws SeqClientException
	 */
	protected function handleResponse(ResponseInterface $response): void
	{
		if ($response->getStatusCode() === 201) {
			return;
		}

		$json = $response->getBody()->getContents();

		try {
			$seqResponse = SeqResponse::fromJson($json);
		} catch (JsonException $e) {
			throw new SeqClientException("Failed to decode response: {$e->getMessage()}", previous: $e);
		}

		$problem = $seqResponse->error ?? 'no problem details known';

		throw match ($response->getStatusCode()) {
			400 => new SeqClientException("The request was malformed: $problem", 400),
			401 => new SeqClientException("Authorization is required: $problem", 401),
			403 => new SeqClientException("The provided credentials don't have ingestion permission: $problem", 403),
			413 => new SeqClientException("The payload itself exceeds the configured maximum size: $problem", 413),
			429 => new SeqClientException("Too many requests", 429),
			500 => new SeqClientException("An internal error prevented the events from being ingested; check Seq's diagnostic log for more information: $problem", 500),
			503 => new SeqClientException("The Seq server is starting up and can't currently service the request, or, free storage space has fallen below the minimum required threshold; this status code may also be returned by HTTP proxies and other network infrastructure when Seq is unreachable: $problem", 503),
			default => new SeqClientException("Undocumented status code. Error: " . $seqResponse->error, $response->getStatusCode()),
		};
	}
}
