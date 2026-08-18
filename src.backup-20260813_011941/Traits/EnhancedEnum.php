<?php
declare(strict_types=1);

namespace Codejitsu\Traits;

trait EnhancedEnum
{
    protected static array $lookupCache = [];

    public static function flushCache(): void
    {
        unset(self::$lookupCache[static::class]);
    }

    public static function normalize(mixed $value, self|string|null $default = null, bool $passthroughUnmatched = false): self|string|null
    {
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
            ?? (method_exists(static::class, 'default') ? static::default() : null) 
            ?? (self::cases()[0] ?? null);

        if ($fallback instanceof self) {
            return $fallback;
        }

        if (is_string($fallback) && $matchFallback = self::tryFrom($fallback)) {
            return $matchFallback;
        }

        return null;
    }

    public static function tryNormalize(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        $index = static::getLookupIndex();

        if (is_scalar($value) && isset($index['value'][$value])) {
            return $index['value'][$value];
        }

        if (is_string($value)) {
            $normalizedStr = strtolower(trim($value));
            if (isset($index['string'][$normalizedStr])) {
                return $index['string'][$normalizedStr];
            }
        }

        if ((is_scalar($value) || is_object($value)) && isset($index['dimensions'][$value])) {
            return $index['dimensions'][$value];
        }

        if (is_string($value)) {
            $normalizedStr = strtolower(trim($value));
            if (isset($index['dimension_strings'][$normalizedStr])) {
                return $index['dimension_strings'][$normalizedStr];
            }
        }

        return null;
    }

    public static function get(mixed $value, ?string $dimension = null): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        if ($dimension !== null) {
            $normalizedDim = ltrim($dimension, '$_');
            $map = static::getMap();
            foreach ($map as $caseValue => $meta) {
                $actualVal = $meta[$dimension] ?? $meta['$' . $normalizedDim] ?? $meta['_' . $normalizedDim] ?? null;
                if ($actualVal === $value) {
                    return self::tryFrom($caseValue);
                }
            }
            return null;
        }

        return static::normalize($value);
    }

    public static function has(self|string $value): bool
    {
        return self::tryNormalize($value) !== null;
    }

    public static function all(): array
    {
        return self::cases();
    }

    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }

    public static function names(): array
    {
        return array_map(fn(self $case) => $case->name, self::cases());
    }

    public static function toArray(bool $valuesAsKeys = false): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            if ($valuesAsKeys) {
                $result[$case->value] = $case->name;
            } else {
                $result[$case->name] = $case->value;
            }
        }
        return $result;
    }

    public static function random(): self
    {
        $cases = self::cases();
        return $cases[array_rand($cases)];
    }

    public function next(): self|false
    {
        $cases = self::cases();
        $index = array_search($this, $cases, true);
        
        if ($index === false || !isset($cases[$index + 1])) {
            return false;
        }
        
        return $cases[$index + 1];
    }

    public function previous(): self|false
    {
        $cases = self::cases();
        $index = array_search($this, $cases, true);
        
        if ($index === false || !isset($cases[$index - 1])) {
            return false;
        }
        
        return $cases[$index - 1];
    }

    public static function initMap(): array
    {
        $map = [];
        foreach (self::cases() as $case) {
            $map[$case->value] = [
                'name' => ucwords(str_replace(['-', '_'], ' ', $case->value)),
                'enum' => $case,
            ];
        }
        return $map;
    }

    /**
     * Fallback map method if the enum doesn't implement its own map().
     */
    public static function map(): array
    {
        return [];
    }

    /**
     * Get the merged map combining defaults with user-defined overrides from map().
     */
    public static function getMap(): array
    {
        $defaultMap = static::initMap();
        $userMap = method_exists(static::class, 'map') ? static::map() : [];

        $merged = $defaultMap;
        foreach ($userMap as $key => $values) {
            if (isset($merged[$key]) && is_array($values)) {
                $merged[$key] = array_merge($merged[$key], $values);
            } else {
                $merged[$key] = $values;
            }
        }

        return $merged;
    }

    protected static function getLookupIndex(): array
    {
        $class = static::class;

        if (isset(self::$lookupCache[$class])) {
            return self::$lookupCache[$class];
        }

        $index = [
            'value'             => [],
            'string'            => [],
            'dimensions'        => [],
            'dimension_strings' => [],
        ];

        foreach (static::getMap() as $caseValue => $meta) {
            $enumCase = self::tryFrom($caseValue);
            if (!$enumCase) {
                continue;
            }

            $index['value'][$caseValue] = $enumCase;
            $index['string'][strtolower($caseValue)] = $enumCase;

            foreach ($meta as $dimKey => $dimVal) {
                if ($dimVal instanceof self || str_starts_with((string)$dimKey, '$') || str_starts_with((string)$dimKey, '_')) {
                    continue;
                }

                if (is_scalar($dimVal)) {
                    $index['dimensions'][$dimVal] = $enumCase;
                }

                if (is_string($dimVal)) {
                    $index['dimension_strings'][strtolower(trim($dimVal))] = $enumCase;
                }
            }
        }

        return self::$lookupCache[$class] = $index;
    }

    public function to(?string $dimension = null, mixed $default = null): mixed
    {
        $map = static::getMap();
        $caseMap = $map[$this->value] ?? [];

        if ($dimension === null) {
            return $caseMap;
        }

        if (array_key_exists($dimension, $caseMap)) {
            return $caseMap[$dimension];
        }

        $cleanDim = ltrim($dimension, '$_');
        foreach (['$', '_', ''] as $prefix) {
            $variant = $prefix . $cleanDim;
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

    public function __toString(): string
    {
        return $this->toString();
    }
}