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
        return parent::hydrate($data);
    }

    /** @return list<array<string,mixed>> */
    public function entries(): array
    {
        return array_values($this->attributes['entries'] ?? []);
    }
}
