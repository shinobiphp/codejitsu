<?php
declare(strict_types=1);

namespace Codejitsu\Traits;

use ArrayIterator;
use IteratorAggregate;
use Countable;
use Closure;

/**
 * @template TKey of array-key
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 */
trait EnhancedCollection
{
    /** @var array<TKey, TValue> */
    protected array $items = [];

    /** @var array<TKey, mixed> Local cache for formatted/hydrated items */
    protected array $cache = [];

    public function __construct(
        array $items = [],
        protected ?Closure $formatter = null
    ) {
        $this->items = $items;
    }

    public function all(): array
    {
        return $this->items;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function set(mixed $key, mixed $value): static
    {
        $this->items[$key] = $value;
        unset($this->cache[$key]); // Clear item cache if overridden
        return $this;
    }

    /**
     * Get an item by key, applying the formatter/hydration logic and caching the result.
     */
    public function get(mixed $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, $this->items)) {
            return $default;
        }

        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $item = $this->items[$key];

        if ($this->formatter !== null) {
            $formatter = $this->formatter;
            $item = $formatter($item, $key);
        }

        return $this->cache[$key] = $item;
    }

    /**
     * Filter the collection using a callback, preserving collection type and formatter.
     */
    public function filter(Closure $callback): static
    {
        return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH), $this->formatter);
    }

    /**
     * Filter collection items by a specific property or key matching a value.
     */
    public function where(string $property, mixed $value): static
    {
        return $this->filter(function ($item) use ($property, $value) {
            $actual = null;
            if (is_object($item)) {
                $actual = property_exists($item, $property) ? $item->{$property} : null;
            } elseif (is_array($item)) {
                $actual = $item[$property] ?? null;
            }
            return $actual === $value;
        });
    }

    public function first(Closure $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            $key = array_key_first($this->items);
            return $key !== null ? $this->get($key) : $default;
        }

        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return $this->get($key);
            }
        }

        return $default;
    }

    public function map(Closure $callback): static
    {
        $keys = array_keys($this->items);
        $mapped = array_map($callback, $this->items, $keys);
        return new static(array_combine($keys, $mapped), $this->formatter);
    }
}