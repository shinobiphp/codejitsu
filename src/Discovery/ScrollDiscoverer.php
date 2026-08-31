<?php

declare(strict_types=1);

namespace Codejitsu\Discovery;

use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use Codejitsu\Scrolls\TypeDefinition;
use Codejitsu\Scrolls\TypeRegistry;
use InvalidArgumentException;
use RuntimeException;

final class ScrollDiscoverer
{
    public function __construct(
        private readonly string $baseDir,
        private readonly ?string $extension = null,
        private readonly ?TypeRegistry $types = null,
    ) {}

    /**
     * Discover all Scroll files.
     *
     * Expected layout:
     *
     *   $baseDir/$pluralType/$name.$extension
     *
     * @return list<DiscoveredScroll>
     */
    public function discover(): array
    {
        $baseDir = rtrim($this->baseDir, '/\\');

        if (!is_dir($baseDir)) {
            return [];
        }

        $discovered = [];

        foreach ($this->registry()->all() as $type) {
            $discovered = [
                ...$discovered,
                ...$this->discoverType($type),
            ];
        }

        return $discovered;
    }

    /** @return list<DiscoveredScroll> */
    public function discoverType(string|ScrollTypes|TypeDefinition $type): array
    {
        $type = $type instanceof TypeDefinition
            ? $type
            : $this->registry()->get($type instanceof ScrollTypes ? $type->value : $type);

        return $this->discoverTypeFromDirectory(rtrim($this->baseDir, '/\\'), $type);
    }

    /** @return list<DiscoveredScroll> */
    private function discoverTypeFromDirectory(string $baseDir, TypeDefinition $type): array
    {
        $directory = $this->getDirectory($baseDir, $type);

        if (!is_dir($directory)) {
            return [];
        }

        $extension = $this->resolveExtension($type);
        $files = glob($directory . DIRECTORY_SEPARATOR . '*.' . $extension);

        if ($files === false) {
            throw new RuntimeException(sprintf('Unable to discover Scrolls in [%s].', $directory));
        }

        $result = [];

        foreach ($files as $path) {
            if (!is_file($path)) {
                continue;
            }

            $name = pathinfo($path, PATHINFO_FILENAME);
            if ($name === '') {
                continue;
            }

            $result[] = new DiscoveredScroll(
                name: strtolower($name),
                type: ScrollTypes::tryFrom($type->name) ?? $type->name,
                path: $path,
                extension: $extension,
            );
        }

        return $result;
    }

    private function getDirectory(string $baseDir, TypeDefinition $type): string
    {
        return $baseDir . DIRECTORY_SEPARATOR . $type->plural;
    }

    private function resolveExtension(TypeDefinition $type): string
    {
        if ($this->extension !== null) {
            return ltrim($this->extension, '.');
        }

        return $type->extension;
    }

    private function registry(): TypeRegistry
    {
        return $this->types ?? TypeRegistry::builtins();
    }
}
