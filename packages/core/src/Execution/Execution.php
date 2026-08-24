<?php

declare(strict_types=1);

namespace Codejitsu\Execution;

use Codejitsu\Contracts\Execution\Executable;
use DateTimeImmutable;

final readonly class Execution
{
    public function __construct(
        public ExecutionId $id,
        public Executable $target,
        public ExecutionContext $context,
        public ?ExecutionResult $result = null,
    ) {}

    public static function start(
        Executable $target,
        ExecutionContext $context,
    ): self {
        return new self(
            id: $context->id,
            target: $target,
            context: $context,
        );
    }

    public function complete(
        mixed $output = null,
        int $returnCode = 0,
    ): self {
        return new self(
            id: $this->id,
            target: $this->target,
            context: $this->context,
            result: new ExecutionResult(
                id: $this->id,
                status: ExecutionStatus::SUCCEEDED,
                output: $output,
                returnCode: $returnCode,
                finishedAt: new DateTimeImmutable(),
            ),
        );
    }

    public function fail(
        \Throwable $error,
        int $returnCode = 1,
    ): self {
        return new self(
            id: $this->id,
            target: $this->target,
            context: $this->context,
            result: new ExecutionResult(
                id: $this->id,
                status: ExecutionStatus::FAILED,
                returnCode: $returnCode,
                error: $error,
                finishedAt: new DateTimeImmutable(),
            ),
        );
    }
}