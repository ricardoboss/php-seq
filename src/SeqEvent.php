<?php
declare(strict_types=1);

namespace Ricardoboss\PhpSeq;

use DateTimeImmutable;
use JsonSerializable;
use Throwable;

readonly class SeqEvent implements JsonSerializable {
	/**
	 * @param DateTimeImmutable $timestamp The timestamp is required.
	 * @param null|string $message A fully-rendered message describing the event.
	 * @param null|string $messageTemplate Alternative to Message; specifies a <a href="http://messagetemplates.org/">message template</a> over the event's properties that provides for rendering into a textual description of the event.
	 * @param null|string $level An implementation-specific level identifier; Seq requires a string value if present. Absence implies "informational".
	 * @param null|Throwable $exception A language-dependent error representation potentially including backtrace; Seq requires a string value, if present.
	 * @param null|int $id An implementation specific event id; Seq requires a numeric value, if present.
	 * @param null|iterable $renderings If <code>mt</code> includes tokens with programming-language-specific formatting, an array of pre-rendered values for each such token. May be omitted; if present, the count of renderings must match the count of formatted tokens exactly.
	 */
	public function __construct(
		public DateTimeImmutable $timestamp,
		public ?string $message,
		public ?string $messageTemplate,
		public ?string $level,
		public ?Throwable $exception,
		public ?int $id,
		public ?iterable $renderings,
		public ?iterable $context,
	) {}

	public function jsonSerialize(): array {
		$data = [
			"@t" => $this->timestamp->format(DATE_ISO8601_EXPANDED)
		];

		if ($this->message !== null) {
			$data["@m"] = $this->message;
		}

		if ($this->messageTemplate !== null) {
			$data["@mt"] = $this->messageTemplate;
		}

		if ($this->level !== null) {
			$data["@l"] = $this->level;
		}

		if ($this->exception !== null) {
			$data["@x"] = $this->exception;
		}

		if ($this->id !== null) {
			$data["@i"] = $this->id;
		}

		if ($this->renderings !== null) {
			$data["@r"] = iterator_to_array($this->renderings);
		}

		if ($this->context !== null) {
			foreach ($this->context as $key => $value) {
				if ($key[0] === "@") {
					$key = "@$key";
				}

				$data[$key] = $value;
			}
		}

		return $data;
	}
}
