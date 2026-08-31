<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;

final class ScrollDiscovery
{
    public function __construct(
        private readonly TypeRegistry $types,
    ) {}

    /** @return list<DiscoveredResource> */
    public function discover(string $root): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $resources = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $type = $this->types->forExtension($file->getExtension());
            if (!$type instanceof TypeDefinition) {
                continue;
            }

            $path = $file->getPathname();
            $payload = file_get_contents($path);
            if ($payload === false) {
                throw new RuntimeException(sprintf('Unable to read Scroll resource [%s].', $path));
            }

            $data = $this->parse($type, $payload, $root, $path);
            $name = strtolower(trim((string) ($data['name'] ?? pathinfo($path, PATHINFO_FILENAME)), '/'));
            $data['name'] = $name;
            $version = (string) ($data['version'] ?? '1.0.0');
            $tags = array_values(array_unique(array_map(
                static fn (mixed $tag): string => strtolower(trim((string) $tag)),
                is_array($data['tags'] ?? null) ? $data['tags'] : [],
            )));
            $attributes = array_diff_key($data, array_flip(['name', 'type', 'version', 'tags']));

            $resources[] = new DiscoveredResource(
                $type,
                $name,
                $version,
                $tags,
                $attributes,
                $this->referenceUris($attributes['references'] ?? []),
                ltrim(str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen(rtrim($root, '/\\')))), '/'),
                static fn () => $type->make(null, $data),
            );
        }

        return $resources;
    }

    private function parse(TypeDefinition $type, string $payload, string $root, string $path): array
    {
        if ($type->name === 'context') {
            $relative = ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR);
            $name = preg_replace('/\.ctx$/i', '', str_replace(DIRECTORY_SEPARATOR, '/', $relative));
            $segments = array_values(array_filter(
                explode('/', dirname($name)),
                static fn (string $segment): bool => $segment !== '.',
            ));

            return [
                'name' => $name,
                'data' => $payload,
                'tags' => $segments,
            ];
        }

        try {
            return $type->makeCodec()->decode($payload);
        } catch (Throwable $e) {
            throw new RuntimeException(sprintf(
                'Failed to decode Scroll resource [%s]: %s',
                $path,
                $e->getMessage(),
            ), previous: $e);
        }
    }

    /** @return array<string> */
    private function referenceUris(mixed $references): array
    {
        if (!is_array($references)) {
            return [];
        }
        $uris = [];
        foreach ($references as $reference) {
            $uri = is_string($reference) ? $reference : ($reference['uri'] ?? null);
            if (is_string($uri) && trim($uri) !== '') {
                $uris[] = strtolower(trim($uri));
            }
        }
        return array_values(array_unique($uris));
    }
}
