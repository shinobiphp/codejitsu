<?php

declare(strict_types=1);

namespace Codejitsu\Contracts;

use ArrayAccess;
use Closure;
use Countable;
use IteratorAggregate;

/**
 * @template T
 * @extends ArrayAccess<string, T>
 * @extends IteratorAggregate<string, T>
 */
interface Codex extends Countable, IteratorAggregate, ArrayAccess
{
    public function register(string $key, mixed $item): static;

    public function has(string $target): bool;

    public function get(string $target): mixed;

    public function isHydrated(string $target): bool;

    public function all(bool $hydrateAll = false): array;

    public function filter(Closure $predicate): static;
}