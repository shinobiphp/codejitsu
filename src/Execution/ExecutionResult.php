<?php
declare(strict_types=1);

namespace Codejitsu\Execution;

use DateTimeImmutable;
use Throwable;

final readonly class ExecutionResult
{
    public function __construct(
        public ExecutionId $id,
        public ExecutionStatus $status,
        public mixed $output = null,
        public ?int $returnCode = null,
        public ?Throwable $error = null,
        public ?DateTimeImmutable $finishedAt = null,
    ) {}

    public function succeeded(): bool
    {
        return $this->status === ExecutionStatus::SUCCEEDED;
    }

    public function failed(): bool
    {
        return $this->status === ExecutionStatus::FAILED;
    }

    public function completed(): bool
    {
        return $this->finishedAt !== null;
    }
}