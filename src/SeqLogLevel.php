<?php
declare(strict_types=1);

namespace RicardoBoss\PhpSeq;

final class SeqLogLevel
{
	const Verbose = 'Verbose';
	const Debug = 'Debug';
	const Information = 'Information';
	const Warning = 'Warning';
	const Error = 'Error';
	const Fatal = 'Fatal';

	private function __construct() {}
}
