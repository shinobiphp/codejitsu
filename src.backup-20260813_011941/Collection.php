<?php
declare(strict_types=1);

namespace Codejitsu;

use ArrayIterator;
use Closure;
use Countable;
use IteratorAggregate;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @implements IteratorAggregate<TKey, TValue>
 */
class Collection implements IteratorAggregate, Countable
{
    /** @var array<TKey, TValue> */
    protected array $items = [];

    /**
     * @param array<TKey, TValue> $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * @return array<TKey, TValue>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @return ArrayIterator<TKey, TValue>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function has(mixed $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function get(mixed $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    public function set(mixed $key, mixed $value): static
    {
        $this->items[$key] = $value;

        return $this;
    }

    public function filter(Closure $callback): static
    {
        return new static(
            array_filter(
                $this->items,
                $callback,
                ARRAY_FILTER_USE_BOTH,
            ),
        );
    }

    public function where(string $property, mixed $value): static
    {
        return $this->filter(
            static function (mixed $item) use ($property, $value): bool {
                $actual = match (true) {
                    is_object($item) && property_exists($item, $property)
                        => $item->{$property},

                    is_array($item)
                        => $item[$property] ?? null,

                    default
                        => null,
                };

                return $actual === $value;
            },
        );
    }

    public function first(?Closure $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            $key = array_key_first($this->items);

            return $key !== null
                ? $this->items[$key]
                : $default;
        }

        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    public function map(Closure $callback): static
    {
        $keys = array_keys($this->items);
        $mapped = array_map($callback, $this->items, $keys);

        return new static(array_combine($keys, $mapped));
    }
}