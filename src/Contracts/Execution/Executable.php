<?php

declare(strict_types=1);

namespace Codejitsu\Contracts\Execution;

use Codejitsu\Execution\ExecutionContext;

interface Executable
{
    public function execute(ExecutionContext $context): mixed;
}