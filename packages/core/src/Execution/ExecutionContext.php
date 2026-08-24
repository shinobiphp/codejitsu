<?php
declare(strict_types=1);

namespace Codejitsu\Execution;

use DateTimeImmutable;

final readonly class ExecutionContext
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public ExecutionId $id,
        public mixed $input = null,
        public ExecutionScope $scope = new ExecutionScope(),
        public DateTimeImmutable $startedAt = new DateTimeImmutable(),
        public array $metadata = [],
    ) {}
}