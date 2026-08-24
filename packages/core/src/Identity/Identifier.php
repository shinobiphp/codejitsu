<?php
declare(strict_types=1);

namespace Codejitsu\Identity;

use Stringable;

final readonly class Identifier implements Stringable
{
    public function __construct(
        public string $value,
    ) {
        if ($this->value === '') {
            throw new \InvalidArgumentException('Identifier cannot be empty.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}