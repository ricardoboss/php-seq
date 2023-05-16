<?php
declare(strict_types=1);

namespace RicardoBoss\PhpSeq;

use DateTimeImmutable;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Immutable;
use JetBrains\PhpStorm\Pure;
use JsonException;
use JsonSerializable;
use Stringable;
use Throwable;

#[Immutable]
readonly class SeqEvent implements JsonSerializable
{
	public const CLEF_DATE_FORMAT = "Y-m-d\TH:i:s.uP";

	/**
	 * @param DateTimeImmutable $timestamp The timestamp is required.
	 * @param null|string|Stringable $message A fully-rendered message describing the event.
	 * @param null|string|Stringable $messageTemplate Alternative to Message; specifies a <a href="http://messagetemplates.org/">message template</a> over the event's properties that provides for rendering into a textual description of the event.
	 * @param null|string|Stringable $level An implementation-specific level identifier; Seq requires a string value if present. Absence implies "informational".
	 * @param null|Throwable $exception A language-dependent error representation potentially including backtrace; Seq requires a string value, if present.
	 * @param null|int $id An implementation specific event id; Seq requires a numeric value, if present.
	 * @param null|iterable $renderings If <code>mt</code> includes tokens with programming-language-specific formatting, an array of pre-rendered values for each such token. May be omitted; if present, the count of renderings must match the count of formatted tokens exactly.
	 */
	#[Pure]
	public function __construct(
		public DateTimeImmutable $timestamp,
		public string|Stringable|null $message,
		public string|Stringable|null $messageTemplate,
		public string|Stringable|null $level,
		public ?Throwable $exception,
		public ?int $id,
		public ?iterable $renderings,
		public ?iterable $context,
	) {}

	#[Pure]
	public function withAddedContext(?iterable $context): self
	{
		if ($context === null) {
			return $this;
		}

		return new self($this->timestamp, $this->message, $this->messageTemplate, $this->level, $this->exception, $this->id, $this->renderings, [...($this->context ?? []), ...$context]);
	}

	#[ArrayShape([
		'@t' => 'string',
		'@m' => 'string',
		'@mt' => 'string',
		'@l' => 'string',
		'@x' => 'string',
		'@i' => 'int',
		'@r' => 'array<string, string>',
	])]
	public function jsonSerialize(): array
	{
		$data = [
			"@t" => $this->timestamp->format(self::CLEF_DATE_FORMAT),
		];

		if ($this->message !== null) {
			$data["@m"] = (string)$this->message;
		}

		if ($this->messageTemplate !== null) {
			$data["@mt"] = (string)$this->messageTemplate;
		}

		if ($this->level !== null) {
			$data["@l"] = (string)$this->level;
		}

		if ($this->exception !== null) {
			$data["@x"] = (string)$this->exception;
		}

		if ($this->id !== null) {
			$data["@i"] = $this->id;
		}

		if ($this->renderings !== null) {
			$renderings = iterator_to_array($this->renderings);
			if (count($renderings) > 0) {
				$data["@r"] = $renderings;
			}
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

	public static function now(string|Stringable|null $message, string|Stringable|null $level = null, ?Throwable $exception = null, ?array $context = null): self
	{
		$time = new DateTimeImmutable();
		$renderings = $context === null ? null : array_map(self::renderValue(...), $context);

		return new self($time, null, $message, $level, $exception, null, $renderings, $context);
	}

	/**
	 * @param mixed $value The value to be rendered
	 * @return string|int|float|bool|null The rendered value
	 * @throws JsonException
	 */
	private static function renderValue(mixed $value): string|int|float|bool|null
	{
		return match (true) {
			is_string($value),
			is_numeric($value),
			is_bool($value),
			$value === null => $value,
			$value instanceof Stringable => $value->__toString(),
			default => json_encode($value, JSON_THROW_ON_ERROR),
		};
	}

	public static function verbose(string|Stringable|null $message, ?array $context = null): self
	{
		return self::now($message, SeqLogLevel::Verbose, null, $context);
	}

	public static function debug(string|Stringable|null $message, ?array $context = null): self
	{
		return self::now($message, SeqLogLevel::Debug, null, $context);
	}

	public static function info(string|Stringable|null $message, ?array $context = null): self
	{
		return self::now($message, SeqLogLevel::Information, null, $context);
	}

	public static function warning(string|Stringable|null $message, ?array $context = null): self
	{
		return self::now($message, SeqLogLevel::Warning, null, $context);
	}

	public static function error(string|Stringable|null $message, ?Throwable $exception = null, ?array $context = null): self
	{
		return self::now($message, SeqLogLevel::Error, $exception, $context);
	}

	public static function fatal(string|Stringable|null $message, ?Throwable $exception = null, ?array $context = null): self
	{
		return self::now($message, SeqLogLevel::Fatal, $exception, $context);
	}
}
