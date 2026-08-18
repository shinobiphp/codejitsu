<?php

declare(strict_types=1);

namespace Codejitsu\Config;

use Codejitsu\Contracts\Config\Store as StoreContract;

class Store implements StoreContract
{
    private array $data = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function has(string $key): bool
    {
        array_key_exists($key, $this->data);
    }
}