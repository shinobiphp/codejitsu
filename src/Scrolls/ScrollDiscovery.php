<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls;

use Codejitsu\Contracts\Scrolls\Scroll as ScrollContract;
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

    /** @return list<ScrollContract> */
    public function discover(string $root): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $scrolls = [];
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

            $scrolls[] = $type->make(null, $this->parse($type, $payload, $root, $path));
        }

        return $scrolls;
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
                'source' => $path,
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
}
