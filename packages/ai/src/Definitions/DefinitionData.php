<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Definitions;

use Codejitsu\Ai\Exceptions\DefinitionException;

final class DefinitionData
{
    public static function string(array $data, string $field, string $owner, bool $required = false): ?string
    {
        $value = $data[$field] ?? null;
        if ($value === null && !$required) return null;
        if (!is_string($value) || trim($value) === '') {
            throw new DefinitionException(sprintf('%s %s must be a non-empty string.', $owner, $field));
        }
        return trim($value);
    }

    /** @return list<string> */
    public static function strings(array $data, string $field, string $owner): array
    {
        $values = $data[$field] ?? [];
        if (!is_array($values)) throw new DefinitionException(sprintf('%s %s must be a list.', $owner, $field));
        $result = [];
        foreach ($values as $value) {
            if (!is_string($value) || trim($value) === '') {
                throw new DefinitionException(sprintf('%s %s must contain non-empty strings.', $owner, $field));
            }
            $result[] = trim($value);
        }
        if (count($result) !== count(array_unique($result))) {
            throw new DefinitionException(sprintf('%s %s contains duplicate references.', $owner, $field));
        }
        return $result;
    }

    public static function allowed(array $defaults, array $allowed, string $kind, string $owner): array
    {
        $allowed = $allowed === [] ? $defaults : $allowed;
        foreach ($defaults as $reference) {
            if (!in_array($reference, $allowed, true)) {
                throw new DefinitionException(sprintf('%s default %s [%s] is not allowed.', $owner, $kind, $reference));
            }
        }
        return $allowed;
    }
}
