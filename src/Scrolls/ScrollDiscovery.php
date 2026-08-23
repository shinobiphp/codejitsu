<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls;

use Codejitsu\Codecs\Neon;
use Codejitsu\Enums\Scrolls\Types;
use Codejitsu\Contracts\Scrolls\Scroll as ScrollContract;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;
use RuntimeException;
use Throwable;

final class ScrollDiscovery
{
    public function __construct(
        private readonly Neon $codec,
    ) {}

    /** @return list<ScrollContract> */
    public function discover(string $root): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $extensions = [];
        foreach (Types::cases() as $type) {
            $extensions[$type->extension()] = $type;
        }

        $scrolls = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $type = $extensions[strtolower($file->getExtension())] ?? null;
            if (!$type instanceof Types) {
                continue;
            }

            $path = $file->getPathname();
            $payload = file_get_contents($path);
            if ($payload === false) {
                throw new RuntimeException(sprintf(
                    'Unable to read Scroll resource [%s].',
                    $path,
                ));
            }

            if ($type === Types::CONTEXT) {
                $relative = ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR);
                $name = preg_replace('/\.md$/i', '', str_replace(DIRECTORY_SEPARATOR, '/', $relative));
                $segments = array_values(array_filter(explode('/', dirname($name)), static fn (string $segment): bool => $segment !== '.'));

                $data = [
                    'name' => $name,
                    'data' => $payload,
                    'tags' => $segments,
                    'source' => $path,
                ];
            } else {
                try {
                    $data = $this->codec->decode($payload);
                } catch (Throwable $e) {
                    throw new RuntimeException(sprintf(
                        'Failed to decode Scroll resource [%s]: %s',
                        $path,
                        $e->getMessage(),
                    ), previous: $e);
                }
            }

            $scrolls[] = $type->make(null, $data);
        }

        return $scrolls;
    }
}
