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

            $payload = file_get_contents($file->getPathname());
            if ($payload === false) {
                throw new RuntimeException(sprintf(
                    'Unable to read Scroll resource [%s].',
                    $file->getPathname(),
                ));
            }

            $scroll = $type->make(null, $this->codec->decode($payload));
            $scrolls[] = $scroll;
        }

        return $scrolls;
    }
}
