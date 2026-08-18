<?php

declare(strict_types=1);

namespace Codejitsu\Traits;

trait EnhancedEnum
{
    public static function flushCache(): void
    {
        // Retained as a compatibility API.
        // Enum instances cannot have static properties, so there is
        // currently no per-enum cache to flush.
    }

    public static function normalize(
        mixed $value,
        self|string|null $default = null,
        bool $passthroughUnmatched = false,
    ): self|string|null {
        if ($value instanceof self) {
            return $value;
        }

        if ($match = static::tryNormalize($value)) {
            return $match;
        }

        if ($passthroughUnmatched) {
            return $value;
        }

        $fallback = $default
            ?? (method_exists(static::class, 'default')
                ? static::default()
                : null)
            ?? (static::cases()[0] ?? null);

        if ($fallback instanceof self) {
            return $fallback;
        }

        if (is_string($fallback)) {
            return static::tryFrom($fallback);
        }

        return null;
    }

public static function tryNormalize(mixed $value): ?self
{
    if ($value instanceof self) {
        return $value;
    }

    $index = static::getLookupIndex();

    if (
        is_int($value)
        || is_string($value)
        || is_float($value)
        || is_bool($value)
    ) {
        if (isset($index['value'][$value])) {
            return $index['value'][$value];
        }
    }

    if (is_string($value)) {
        $normalized = strtolower(trim($value));

        if (isset($index['string'][$normalized])) {
            return $index['string'][$normalized];
        }
    }

    if (
        is_int($value)
        || is_string($value)
        || is_float($value)
        || is_bool($value)
    ) {
        if (isset($index['dimensions'][$value])) {
            return $index['dimensions'][$value];
        }
    }

    if (is_string($value)) {
        $normalized = strtolower(trim($value));

        if (
            isset(
                $index['dimension_strings'][$normalized],
            )
        ) {
            return $index['dimension_strings'][$normalized];
        }
    }

    return null;
}

    public static function get(
        mixed $value,
        ?string $dimension = null,
    ): ?self {
        if ($value instanceof self) {
            return $value;
        }

        if ($dimension !== null) {
            $normalizedDimension = ltrim($dimension, '$_');

            foreach (static::getMap() as $caseValue => $meta) {
                $actualValue =
                    $meta[$dimension]
                    ?? $meta['$' . $normalizedDimension]
                    ?? $meta['_' . $normalizedDimension]
                    ?? null;

                if ($actualValue === $value) {
                    return static::tryFrom($caseValue);
                }
            }

            return null;
        }

        return static::tryNormalize($value);
    }

    public static function has(self|string $value): bool
    {
        return static::tryNormalize($value) !== null;
    }

    public static function all(): array
    {
        return static::cases();
    }

    public static function values(): array
    {
        return array_map(
            static fn(self $case): string => $case->value,
            static::cases(),
        );
    }

    public static function names(): array
    {
        return array_map(
            static fn(self $case): string => $case->name,
            static::cases(),
        );
    }

    public static function toArray(bool $valuesAsKeys = false): array
    {
        $result = [];

        foreach (static::cases() as $case) {
            if ($valuesAsKeys) {
                $result[$case->value] = $case->name;
                continue;
            }

            $result[$case->name] = $case->value;
        }

        return $result;
    }

    public static function random(): self
    {
        $cases = static::cases();

        return $cases[array_rand($cases)];
    }

    public function next(): self|false
    {
        $cases = static::cases();
        $index = array_search($this, $cases, true);

        if ($index === false || !isset($cases[$index + 1])) {
            return false;
        }

        return $cases[$index + 1];
    }

    public function previous(): self|false
    {
        $cases = static::cases();
        $index = array_search($this, $cases, true);

        if ($index === false || !isset($cases[$index - 1])) {
            return false;
        }

        return $cases[$index - 1];
    }

    public static function initMap(): array
    {
        $map = [];

        foreach (static::cases() as $case) {
            $map[$case->value] = [
                'name' => ucwords(
                    str_replace(['-', '_'], ' ', $case->value)
                ),
                'enum' => $case,
            ];
        }

        return $map;
    }

    public static function map(): array
    {
        return [];
    }

    public static function getMap(): array
    {
        $defaultMap = static::initMap();
        $userMap = static::map();

        foreach ($userMap as $key => $values) {
            if (isset($defaultMap[$key]) && is_array($values)) {
                $defaultMap[$key] = array_merge(
                    $defaultMap[$key],
                    $values,
                );

                continue;
            }

            $defaultMap[$key] = $values;
        }

        return $defaultMap;
    }

    protected static function getLookupIndex(): array
{
    $index = [
        'value' => [],
        'string' => [],
        'dimensions' => [],
        'dimension_strings' => [],
    ];

    foreach (static::getMap() as $caseValue => $meta) {
        $enumCase = static::tryFrom(
            (string) $caseValue,
        );

        if ($enumCase === null) {
            continue;
        }

        $index['value'][$caseValue] = $enumCase;
        $index['string'][
            strtolower((string) $caseValue)
        ] = $enumCase;

        foreach ($meta as $dimension => $value) {
            $dimension = (string) $dimension;

            if (
                $value instanceof self
                || str_starts_with($dimension, '$')
                || str_starts_with($dimension, '_')
            ) {
                continue;
            }

            if (
                is_int($value)
                || is_string($value)
                || is_float($value)
                || is_bool($value)
            ) {
                $index['dimensions'][$value] = $enumCase;
            }

            if (is_string($value)) {
                $index['dimension_strings'][
                    strtolower(trim($value))
                ] = $enumCase;
            }
        }
    }

    return $index;
}

    public function to(
        ?string $dimension = null,
        mixed $default = null,
    ): mixed {
        $map = static::getMap();
        $caseMap = $map[$this->value] ?? [];

        if ($dimension === null) {
            return $caseMap;
        }

        if (array_key_exists($dimension, $caseMap)) {
            return $caseMap[$dimension];
        }

        $cleanDimension = ltrim($dimension, '$_');

        foreach (['$', '_', ''] as $prefix) {
            $variant = $prefix . $cleanDimension;

            if (array_key_exists($variant, $caseMap)) {
                return $caseMap[$variant];
            }
        }

        return $default;
    }

    public function toString(): string
    {
        return $this->value;
    }
}