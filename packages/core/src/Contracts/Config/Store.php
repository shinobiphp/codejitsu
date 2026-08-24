<?php

declare(strict_types=1);

namespace Codejitsu\Contracts\Config;

interface Store
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): void;
    public function has(string $key): bool;
}