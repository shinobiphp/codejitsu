<?php
declare(strict_types=1);

namespace Codejitsu\Config;

abstract class ImmutableConfig extends MutableConfig
{
    public function save(): void
    {
        throw new \LogicException("Cannot save changes from an immutable configuration container.");
    }

    public function __set(string $key, mixed $value): void
    {
        throw new \LogicException("Cannot modify an immutable configuration container.");
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException("Cannot modify an immutable configuration container.");
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException("Cannot modify an immutable configuration container.");
    }
}