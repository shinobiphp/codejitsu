<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls;

use Codejitsu\Contracts\Scrolls\TypeRegistry as TypeRegistryContract;
use InvalidArgumentException;
use OutOfBoundsException;

final class TypeRegistry implements TypeRegistryContract
{
    /** @var array<string, TypeDefinition> */
    private array $definitions = [];
    /** @var array<string, string> */
    private array $extensions = [];
    /** @var array<string, string> */
    private array $schemes = [];

    public function register(TypeDefinition $definition): static
    {
        if (isset($this->definitions[$definition->name])) {
            throw new InvalidArgumentException(sprintf('Scroll type name [%s] is already registered.', $definition->name));
        }
        if (isset($this->extensions[$definition->extension])) {
            throw new InvalidArgumentException(sprintf(
                'Scroll extension [%s] for [%s] conflicts with [%s].',
                $definition->extension,
                $definition->name,
                $this->extensions[$definition->extension],
            ));
        }
        if (isset($this->schemes[$definition->scheme])) {
            throw new InvalidArgumentException(sprintf(
                'Scroll scheme [%s] for [%s] conflicts with [%s].',
                $definition->scheme,
                $definition->name,
                $this->schemes[$definition->scheme],
            ));
        }

        $this->definitions[$definition->name] = $definition;
        $this->extensions[$definition->extension] = $definition->name;
        $this->schemes[$definition->scheme] = $definition->name;
        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->definitions[self::normalize($name)]);
    }

    public function get(string $name): TypeDefinition
    {
        $name = self::normalize($name);
        return $this->definitions[$name]
            ?? throw new OutOfBoundsException(sprintf('Scroll type [%s] is not registered.', $name));
    }

    public function forExtension(string $extension): ?TypeDefinition
    {
        $name = $this->extensions[ltrim(self::normalize($extension), '.')] ?? null;
        return $name === null ? null : $this->definitions[$name];
    }

    public function forScheme(string $scheme): ?TypeDefinition
    {
        $scheme = self::normalize($scheme);
        $scheme = str_ends_with($scheme, '://') ? $scheme : $scheme . '://';
        $name = $this->schemes[$scheme] ?? null;
        return $name === null ? null : $this->definitions[$name];
    }

    public function all(): array
    {
        return array_values($this->definitions);
    }

    private static function normalize(string $value): string
    {
        return strtolower(trim($value));
    }
}
