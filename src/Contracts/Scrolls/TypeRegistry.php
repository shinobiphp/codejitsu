<?php

declare(strict_types=1);

namespace Codejitsu\Contracts\Scrolls;

use Codejitsu\Scrolls\TypeDefinition;

interface TypeRegistry
{
    public function register(TypeDefinition $definition): static;
    public function has(string $name): bool;
    public function get(string $name): TypeDefinition;
    public function forExtension(string $extension): ?TypeDefinition;
    public function forScheme(string $scheme): ?TypeDefinition;

    /** @return list<TypeDefinition> */
    public function all(): array;
}
