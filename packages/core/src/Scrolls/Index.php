<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls;

use Codejitsu\Uri\Uri;
use Countable;
use IteratorAggregate;
use Traversable;

/** @implements IteratorAggregate<int, IndexEntry> */
final class Index implements Countable, IteratorAggregate
{
    /** @var array<string, IndexEntry> */
    private array $entries = [];

    public function add(IndexEntry $entry): void
    {
        $this->entries[$this->key($entry->uri)] = $entry;
    }

    public function remove(string|Uri $uri): void
    {
        unset($this->entries[$this->key($uri)]);
    }

    public function get(string|Uri $uri): ?IndexEntry
    {
        return $this->entries[$this->key($uri)] ?? null;
    }

    /** @return list<IndexEntry> */
    public function all(): array
    {
        return array_values($this->entries);
    }

    /**
     * @param array<string, mixed> $criteria
     * @return list<IndexEntry>
     */
    public function query(array $criteria = []): array
    {
        $types = isset($criteria['type']) ? array_map('strtolower', (array) $criteria['type']) : null;
        $sources = isset($criteria['source'])
            ? array_values(array_unique(array_map('strtolower', (array) $criteria['source'])))
            : null;
        $sourceRank = $sources === null
            ? []
            : array_flip($sources);
        $name = isset($criteria['name']) ? strtolower(trim((string) $criteria['name'], '/')) : null;
        $version = isset($criteria['version']) ? (string) $criteria['version'] : null;
        $tags = isset($criteria['tags']) ? array_map('strtolower', (array) $criteria['tags']) : [];
        $attributes = isset($criteria['attributes']) && is_array($criteria['attributes'])
            ? $criteria['attributes']
            : [];

        $entries = array_values(array_filter(
            $this->all(),
            static function (IndexEntry $entry) use ($types, $sources, $name, $version, $tags, $attributes): bool {
                if ($types !== null && !in_array(strtolower($entry->type), $types, true)) {
                    return false;
                }

                if ($sources !== null && !in_array(strtolower($entry->source), $sources, true)) {
                    return false;
                }

                if ($name !== null && strtolower(trim($entry->name, '/')) !== $name) {
                    return false;
                }

                if ($version !== null && $entry->version !== $version) {
                    return false;
                }

                if ($tags !== [] && array_diff($tags, array_map('strtolower', $entry->tags)) !== []) {
                    return false;
                }

                foreach ($attributes as $key => $expected) {
                    if (!array_key_exists($key, $entry->attributes) || $entry->attributes[$key] !== $expected) {
                        return false;
                    }
                }

                return true;
            },
        ));

        if ($sources !== null) {
            usort(
                $entries,
                static fn (IndexEntry $left, IndexEntry $right): int =>
                    ($sourceRank[strtolower($left->source)] ?? PHP_INT_MAX)
                    <=> ($sourceRank[strtolower($right->source)] ?? PHP_INT_MAX),
            );
        }

        return $entries;
    }

    public function count(): int
    {
        return count($this->entries);
    }

    public function getIterator(): Traversable
    {
        yield from $this->entries;
    }

    private function key(string|Uri $uri): string
    {
        return strtolower((string) $uri);
    }
}
