<?php

declare(strict_types=1);

namespace Codejitsu\Execution\Substrates;

use Codejitsu\Contracts\Execution\Executable;
use Codejitsu\Contracts\Execution\Substrate;
use Codejitsu\Execution\ExecutionContext;
use Codejitsu\Execution\ExecutionResult;
use Codejitsu\Execution\ExecutionStatus;
use DateTimeImmutable;
use Throwable;

final class Php implements Substrate
{
    public function supports(Executable $executable): bool
    {
        return $executable instanceof \Codejitsu\Execution\InvokableExecutable;
    }

    public function execute(
        Executable $executable,
        ExecutionContext $context,
    ): ExecutionResult {
        try {
            return new ExecutionResult(
                id: $context->id,
                status: ExecutionStatus::SUCCEEDED,
                output: $executable->execute($context),
                returnCode: 0,
                finishedAt: new DateTimeImmutable(),
            );
        } catch (Throwable $error) {
            return new ExecutionResult(
                id: $context->id,
                status: ExecutionStatus::FAILED,
                returnCode: 1,
                error: $error,
                finishedAt: new DateTimeImmutable(),
            );
        }
    }
}