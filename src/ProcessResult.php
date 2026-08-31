<?php

declare(strict_types=1);

namespace Codejitsu;

final readonly class ProcessResult
{
    public function __construct(
        public int $exitCode,
        public string $stdout,
        public string $stderr,
    ) {}

    public function output(): string
    {
        return trim($this->stdout . ($this->stderr !== '' ? PHP_EOL . $this->stderr : ''));
    }
}
