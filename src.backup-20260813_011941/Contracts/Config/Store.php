<?php

declare(strict_types=1);

namespace Codejitsu\Contracts\Config;

use Codejitsu\Contracts\Config\Store as StoreContract;

use IteratorAggregate;
use ArrayAccess;

interface Config extends IteratorAggregate, ArrayAccess
{
    public static function from(StoreContract $store): static;

    public function get(string $key, mixed $default = null): mixed;
    public function has(string $key): bool;
    public function all(): array;
    
    public ?StoreContract $store { get; }
    public bool $loaded { get; }
}