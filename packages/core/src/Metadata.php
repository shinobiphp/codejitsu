<?php
declare(strict_types=1);

namespace Codejitsu;

use Codejitsu\Identity\Identity;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<mixed, mixed>
 */
final class Metadata implements IteratorAggregate, Countable
{
    public function __construct(
        private Identity $identity,
        private Collection $items = new Collection(),
    ) {}

    public function identity(): Identity
    {
        return $this->identity;
    }

    public function get(mixed $key, mixed $default = null): mixed
    {
        return $this->items->get($key, $default);
    }

    public function set(mixed $key, mixed $value): static
    {
        $this->items->set($key, $value);

        return $this;
    }

    public function has(mixed $key): bool
    {
        return $this->items->has($key);
    }

    public function remove(mixed $key): static
    {
        $this->items->remove($key);

        return $this;
    }

    public function all(): array
    {
        return $this->items->all();
    }

    public function count(): int
    {
        return $this->items->count();
    }

    public function getIterator(): Traversable
    {
        return $this->items->getIterator();
    }

    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->set($name, $value);
    }

    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    public function __unset(string $name): void
    {
        $this->remove($name);
    }
}