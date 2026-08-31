<?php

declare(strict_types=1);

namespace Codejitsu\Contracts;

use Codejitsu\ProcessResult;

interface ProcessRunner
{
    /** @param list<string> $command */
    public function run(array $command, string $cwd): ProcessResult;
}
