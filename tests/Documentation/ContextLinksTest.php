<?php

declare(strict_types=1);

namespace Tests\Documentation;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class ContextLinksTest extends TestCase
{
    public function testRootReadmeOwnsThePublicProjectEntryPoint(): void
    {
        self::assertFileExists(dirname(__DIR__, 2) . '/README.md');
        self::assertFileDoesNotExist(dirname(__DIR__, 2) . '/.context/README.md');
    }

    public function testLocalDocumentationLinksResolve(): void
    {
        $root = dirname(__DIR__, 2);
        $paths = [$root . '/README.md'];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root . '/.context', FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['md', 'ctx'], true)) {
                $paths[] = $file->getPathname();
            }
        }

        foreach ($paths as $path) {
            $content = file_get_contents($path);
            self::assertNotFalse($content);
            preg_match_all('/\[[^\]]+\]\((?!https?:|mailto:|#)([^)#]+)(?:#[^)]+)?\)/', $content, $matches);

            foreach ($matches[1] as $target) {
                $resolved = dirname($path) . '/' . rawurldecode($target);
                self::assertFileExists($resolved, sprintf('Broken documentation link [%s] in [%s].', $target, $path));
            }
        }
    }
}
