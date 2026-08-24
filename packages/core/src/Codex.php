<?php

declare(strict_types=1);

namespace Codejitsu;

use ArrayAccess;
use ArrayIterator;
use Closure;
use Codejitsu\Contracts\Codex as CodexContract;
use Countable;
use IteratorAggregate;
use OutOfBoundsException;
use Traversable;

/**
 * Base generic in-memory container.
 *
 * @template T
 */
class Codex implements CodexContract, ArrayAccess, IteratorAggregate, Countable
{
    /** @var array<string, mixed> */
    protected array $items = [];

    public function __construct(array $items = [])
    {
        foreach ($items as $key => $item) {
            $this->register(is_string($key) ? $key : (string) $key, $item);
        }
    }

    public function register(string $key, mixed $item): static
    {
        $this->items[strtolower($key)] = $item;
        return $this;
    }

    public function has(string $target): bool
    {
        return isset($this->items[strtolower($target)]);
    }

    public function get(string $target): mixed
    {
        $key = strtolower($target);

        if (!isset($this->items[$key])) {
            throw new OutOfBoundsException("Item [{$target}] not found in Codex.");
        }

        return $this->items[$key];
    }

    public function all(bool $hydrateAll = false): array
    {
        return $this->items;
    }

    public function filter(Closure $predicate): static
    {
        $filtered = array_filter($this->all(), $predicate, ARRAY_FILTER_USE_BOTH);
        return new static($filtered);
    }

    public function isHydrated(string $target): bool
    {
        return $this->has($target);
    }

    /*
     * ---------------------------------------------------------
     * ArrayAccess & Iterator Defaults
     * ---------------------------------------------------------
     */

    public function offsetExists(mixed $offset): bool { return is_string($offset) && $this->has($offset); }
    public function offsetGet(mixed $offset): mixed { return $this->get((string) $offset); }
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_string($offset)) {
            $this->register($offset, $value);
        }
    }
    public function offsetUnset(mixed $offset): void
    {
        if (is_string($offset)) {
            unset($this->items[strtolower($offset)]);
        }
    }

    public function getIterator(): Traversable { return new ArrayIterator($this->all()); }
    public function count(): int { return count($this->all()); }
}