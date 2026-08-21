<?php

declare(strict_types=1);

namespace Codejitsu\Discovery;

use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use InvalidArgumentException;
use RuntimeException;

final class ScrollDiscoverer
{
    public function __construct(
        private readonly string $baseDir,
        private readonly ?string $extension = null,
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

        foreach (ScrollTypes::cases() as $type) {
            $discovered = [
                ...$discovered,
                ...$this->discoverType($baseDir, $type),
            ];
        }

        return $discovered;
    }

    /** @return list<DiscoveredScroll> */
    public function discoverType(string|ScrollTypes $type): array
    {
        $type = $type instanceof ScrollTypes
            ? $type
            : ScrollTypes::normalize($type, null);

        if (!$type instanceof ScrollTypes) {
            throw new InvalidArgumentException(sprintf('Unknown Scroll type [%s].', (string) $type));
        }

        return $this->discoverTypeFromDirectory(rtrim($this->baseDir, '/\\'), $type);
    }

    /** @return list<DiscoveredScroll> */
    private function discoverTypeFromDirectory(string $baseDir, ScrollTypes $type): array
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
                type: $type,
                path: $path,
                extension: $extension,
            );
        }

        return $result;
    }

    private function getDirectory(string $baseDir, ScrollTypes $type): string
    {
        $plural = method_exists($type, 'plural') ? $type->plural() : $type->value;

        return $baseDir . DIRECTORY_SEPARATOR . strtolower($plural);
    }

    private function resolveExtension(ScrollTypes $type): string
    {
        if ($this->extension !== null) {
            return ltrim($this->extension, '.');
        }

        return $type->extension();
    }
}