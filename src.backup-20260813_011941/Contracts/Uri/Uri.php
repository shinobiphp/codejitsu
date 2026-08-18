<?php
declare(strict_types=1);

namespace Codejitsu\Contracts\Uri;

use Stringable;

interface Uri extends Stringable
{
    public ?string $tenant { get; }
    public string $type { get; }
    public string $target { get; }
    public ?string $version { get; }
    public ?string $path { get; }
    
    public array $query { get; set; }
    public string $key { get; }
    
    public bool $isLocal { get; }
    public bool $isGlobal { get; }
    public bool $isLatest { get; }

    public static function make(
        string|self $uri, 
        ?string $defaultTenant = null, 
        ?string $defaultVersion = null
    ): static;

    public function withQuery(array $params): static;

    public function __get(string $name): mixed;
    public function __set(string $name, mixed $value): void;
    public function __isset(string $name): bool;
    public function __unset(string $name): void;
}