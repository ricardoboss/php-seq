<?php
declare(strict_types=1);

namespace Ricardoboss\PhpSeq;

use Psr\Http\Message\StreamInterface;

class SeqBatchStream implements StreamInterface
{
	public function __toString(): string {
		// TODO: Implement __toString() method.
	}

	public function close(): void {
	}

	public function detach(): null {
		return null;
	}

	public function getSize(): ?int {
		// TODO: Implement getSize() method.
	}

	public function tell(): int {
		// TODO: Implement tell() method.
	}

	public function eof(): bool {
		// TODO: Implement eof() method.
	}

	public function isSeekable(): bool {
		// TODO: Implement isSeekable() method.
	}

	public function seek(int $offset, int $whence = SEEK_SET): void {
		// TODO: Implement seek() method.
	}

	public function rewind(): void {
		// TODO: Implement rewind() method.
	}

	public function isWritable(): bool {
		// TODO: Implement isWritable() method.
	}

	public function write(string $string): int {
		// TODO: Implement write() method.
	}

	public function isReadable(): bool {
		// TODO: Implement isReadable() method.
	}

	public function read(int $length): string {
		// TODO: Implement read() method.
	}

	public function getContents(): string {
		// TODO: Implement getContents() method.
	}

	public function getMetadata(?string $key = null) {
		// TODO: Implement getMetadata() method.
	}
}