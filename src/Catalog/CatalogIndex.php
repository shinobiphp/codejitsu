<?php
declare(strict_types=1);
namespace Codejitsu\Catalog;

use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Types\Catalog;

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
}
