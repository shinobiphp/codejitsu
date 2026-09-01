<?php
declare(strict_types=1);
namespace Codejitsu\Scrolls\Types;

use Codejitsu\Scrolls\Scroll;
use InvalidArgumentException;

final class Catalog extends Scroll
{
    public const string TYPE = 'catalog';

    public function hydrate(array $data): static
    {
        if (isset($data['entries']) && !is_array($data['entries'])) {
            throw new InvalidArgumentException('Catalog entries must be an array.');
        }
        foreach (($data['entries'] ?? []) as $index => $entry) {
            if (!is_array($entry) || !is_string($entry['identifier'] ?? null) || !str_contains($entry['identifier'], '://')) {
                throw new InvalidArgumentException(sprintf('Catalog entry [%s] requires a resource identifier.', $index));
            }
            if (!is_string($entry['kind'] ?? null) || preg_match('/^[a-z][a-z0-9_-]*$/', $entry['kind']) !== 1) {
                throw new InvalidArgumentException(sprintf('Catalog entry [%s] requires a valid kind.', $index));
            }
            if (isset($entry['location']) && (!is_string($entry['location']) || !str_contains($entry['location'], '://'))) {
                throw new InvalidArgumentException(sprintf('Catalog entry [%s] has an invalid location.', $index));
            }
        }
        if (isset($data['entrySchemas']) && !is_array($data['entrySchemas'])) {
            throw new InvalidArgumentException('Catalog entrySchemas must be a map.');
        }
        foreach (($data['entrySchemas'] ?? []) as $kind => $schema) {
            if (preg_match('/^[a-z][a-z0-9_-]*$/', (string) $kind) !== 1
                || !is_string($schema) || !str_starts_with($schema, 'schema://')) {
                throw new InvalidArgumentException(sprintf('Catalog entry schema [%s] is invalid.', $kind));
            }
        }
        if (isset($data['access']) && !in_array($data['access'], ['writable', 'read-only'], true)) {
            throw new InvalidArgumentException('Catalog access must be writable or read-only.');
        }
        return parent::hydrate($data);
    }

    /** @return list<array<string,mixed>> */
    public function entries(): array
    {
        return array_values($this->attributes['entries'] ?? []);
    }

    public function access(): string
    {
        return ($this->attributes['access'] ?? 'writable') === 'read-only' ? 'read-only' : 'writable';
    }

    /** @return array<string,string> */
    public function entrySchemas(): array
    {
        return $this->attributes['entrySchemas'] ?? [];
    }
}
