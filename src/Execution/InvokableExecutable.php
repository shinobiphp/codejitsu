<?php

declare(strict_types=1);

namespace Codejitsu\Execution;

use Codejitsu\Contracts\Execution\Executable;
use Codejitsu\Contracts\Invokable;

final readonly class InvokableExecutable implements Executable
{
    public function __construct(
        private Invokable $target,
    ) {}

    public function execute(ExecutionContext $context): mixed
    {
        return ($this->target)(...match (true) {
            is_array($context->input) => $context->input,
            $context->input === null => [],
            default => [$context->input],
        });
    }
}