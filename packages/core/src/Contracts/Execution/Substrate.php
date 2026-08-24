<?php

declare(strict_types=1);

namespace Codejitsu\Contracts\Execution;

use Codejitsu\Execution\ExecutionContext;
use Codejitsu\Execution\ExecutionResult;

interface Substrate
{
    public function supports(Executable $executable): bool;

    public function execute(
        Executable $executable,
        ExecutionContext $context,
    ): ExecutionResult;
}