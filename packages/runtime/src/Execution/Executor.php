<?php

declare(strict_types=1);

namespace Codejitsu\Execution;

use Codejitsu\Contracts\Execution\Executable;
use Codejitsu\Contracts\Execution\Substrate;
use Codejitsu\Contracts\Execution\Executor as ExecutorContract;
use RuntimeException;

final class Executor implements ExecutorContract
{
    /**
     * @param iterable<Substrate> $substrates
     */
    public function __construct(
        private iterable $substrates,
    ) {}

    public function execute(
        Executable $executable,
        ExecutionContext $context,
    ): ExecutionResult {
        foreach ($this->substrates as $substrate) {
            if (!$substrate->supports($executable)) {
                continue;
            }

            return $substrate->execute(
                $executable,
                $context,
            );
        }

        throw new RuntimeException(
            sprintf(
                'No execution substrate supports %s.',
                $executable::class,
            ),
        );
    }
}