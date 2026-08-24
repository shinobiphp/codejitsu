<?php

declare(strict_types=1);

namespace Codejitsu\Contracts\Discovery;

interface Cache
{
    /**
     * Retrieve cached class list or null if cache miss / stale.
     *
     * @return array<string>|null
     */
    public function get(string $cacheKey): ?array;

    /**
     * Persist discovered class list.
     *
     * @param array<string> $classes
     */
    public function put(string $cacheKey, array $classes, int $ttl = 0): void;

    /**
     * Invalidate or clear cached discovery results.
     */
    public function forget(string $cacheKey): void;
}