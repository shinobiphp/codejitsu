<?php
declare(strict_types=1);

namespace Codejitsu\Execution;

use Codejitsu\Identity\Identifier;
use Stringable;

final readonly class ExecutionId implements Stringable
{
    public function __construct(
        public Identifier $value,
    ) {}

    public static function generate(): self
    {
        return new self(
            new Identifier(bin2hex(random_bytes(16))),
        );
    }

    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}