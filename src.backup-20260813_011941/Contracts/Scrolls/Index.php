<?php
declare(strict_types=1);

namespace Codejitsu\Contracts\Scrolls;

use Codejitsu\Contracts\Scrolls\Codice;

interface Index
{
    /**
     * Load cached manifest into Codice if valid. Returns false if cache missed or stale.
     */
    public function loadFromCache(Codice $codice): bool;

    /**
     * Scan target directories, build index entries using Codec::inspect(), 
     * and write compiled index cache to disk.
     */
    public function buildAndCache(Codice $codice, array $scanDirectories): array;

    /**
     * Clear current index cache files.
     */
    public function clearCache(): bool;

    /**
     * Check if compiled index cache exists and is fresh.
     */
    public function isFresh(): bool;
}