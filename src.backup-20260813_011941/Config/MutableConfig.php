<?php

declare(strict_types=1);

namespace Codejitsu\Config;

use Codejitsu\Contracts\Config\Config as ConfigContract;
use Codejitsu\Contracts\Config\Store as StoreContract;
use Traversable;
use ArrayIterator;

abstract class MutableConfig implements ConfigContract
{
    protected array $data = [];

    public function __construct(
        protected ?StoreContract $storeInstance = null, 
        array $initialData = []
    ) {
        if (!empty($initialData)) {
            $this->data = $initialData;
        } elseif ($this->storeInstance !== null) {
            // Auto-load data from store if present on construction
            $this->data = $this->storeInstance->load()->all();
        }
    }

    public static function from(StoreContract $store): static
    {
        return new static($store);
    }

    public static function make(mixed ...$params): static
    {
        $storeClass = static::getStoreClass();
        $store = $storeClass::make(...$params);
        return static::from($store);
    }

    abstract protected static function getStoreClass(): string;

    /**
     * Hooked property for the store instance using simple naming.
     */
    public ?StoreContract $store {
        get => $this->storeInstance;
    }

    /**
     * Hooked property indicating whether configuration data is present.
     */
    public bool $loaded {
        get => !empty($this->data);
    }

    public function save(): void
    {
        if ($this->storeInstance !== null) {
            $this->storeInstance->save($this->all());
        }
    }

    public function all(): array
    {
        return $this->data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        $current = $this->data;
        foreach (explode('.', $key) as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } else {
                return $default;
            }
        }

        return $current;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return $this->has($key);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string) $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get((string) $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->data[] = $value;
        } else {
            $this->data[(string) $offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[(string) $offset]);
    }
}