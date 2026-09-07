<?php
declare(strict_types=1);
namespace Codejitsu\Catalog;

use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Types\Catalog;
use RuntimeException;

final readonly class CatalogIndex
{
    public function __construct(private ScrollCodex $codex) {}

    /** @return array<string,array<string,mixed>> */
    public function all(?string $kind = null): array
    {
        if (!$this->codex->types()->has('catalog')) return [];
        $entries = [];
        foreach ($this->codex->query(['type' => 'catalog']) as $indexed) {
            $catalog = $this->codex->resolve((string) $indexed->uri);
            if (!$catalog instanceof Catalog) continue;
            foreach ($catalog->entries() as $entry) {
                if ($kind !== null && $entry['kind'] !== $kind) continue;
                $entries[$entry['identifier']] ??= $entry + ['catalog' => $catalog->name, 'source' => $indexed->source];
            }
        }
        ksort($entries);
        return $entries;
    }

    /** @return array<string,mixed>|null */
    public function find(string $kind, string $name): ?array
    {
        $name = strtolower(trim($name));
        foreach ($this->all($kind) as $entry) {
            if ($this->resourceName((string) $entry['identifier']) === $name) return $entry;
        }
        return null;
    }

    /** @return array<string,array<string,mixed>> */
    public function search(string $kind, string $query): array
    {
        $query = strtolower(trim($query));
        if ($query === '') return [];
        return array_filter($this->all($kind), static function (array $entry) use ($query): bool {
            $haystack = strtolower(implode(' ', array_filter([
                $entry['identifier'] ?? null,
                $entry['description'] ?? null,
                ...($entry['tags'] ?? []),
            ], 'is_string')));
            return str_contains($haystack, $query);
        });
    }

    /** @return list<array{name:string,uri:string,source:string,entries:int,access:string}> */
    public function catalogs(): array
    {
        if (!$this->codex->types()->has('catalog')) return [];
        $catalogs = [];
        foreach ($this->codex->query(['type' => 'catalog']) as $entry) {
            $catalog = $this->codex->resolve((string) $entry->uri);
            if (!$catalog instanceof Catalog) continue;
            $catalogs[] = [
                'name' => $catalog->name,
                'uri' => (string) $entry->uri,
                'source' => $entry->source,
                'entries' => count($catalog->entries()),
                'access' => $catalog->access(),
            ];
        }
        usort($catalogs, static fn (array $a, array $b): int => [$a['name'], $a['source']] <=> [$b['name'], $b['source']]);
        return $catalogs;
    }

    public function catalog(string $identifier): Catalog
    {
        try {
            $catalog = $this->codex->resolveTyped('catalog', $identifier);
        } catch (\Throwable $exception) {
            throw new RuntimeException(sprintf('Catalog [%s] was not found.', $identifier), 0, $exception);
        }
        if ($catalog instanceof Catalog) return $catalog;
        throw new RuntimeException(sprintf('Scroll [%s] is not a Catalog.', $identifier));
    }

    /** @return array<string,array<string,mixed>> */
    public function searchAll(string $query, ?string $kind = null): array
    {
        $query = strtolower(trim($query));
        if ($query === '') return [];
        return array_filter($this->all($kind), static function (array $entry) use ($query): bool {
            $values = [$entry['identifier'] ?? '', $entry['description'] ?? '', $entry['location'] ?? '', ...($entry['tags'] ?? [])];
            return str_contains(strtolower(implode(' ', array_map('strval', $values))), $query);
        });
    }

    private function resourceName(string $identifier): string
    {
        $path = preg_replace('~^[a-z][a-z0-9+.-]*://~i', '', $identifier) ?? $identifier;
        return strtolower((string) preg_replace('/#.*$/', '', $path));
    }
}
