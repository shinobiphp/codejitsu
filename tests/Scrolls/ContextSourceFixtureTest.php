<?php

declare(strict_types=1);

namespace Tests\Scrolls;

use Codejitsu\Scrolls\ScrollDiscovery;
use Codejitsu\Scrolls\TypeRegistry;
use Codejitsu\Scrolls\Types\Context;
use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class ContextSourceFixtureTest extends TestCase
{
    public function testDurableProjectContextUsesContextScrollFiles(): void
    {
        $root = dirname(__DIR__, 2) . '/.context';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                self::assertSame('ctx', $file->getExtension(), $file->getPathname());
            }
        }
    }

    public function testNestedContextPathsBecomeNamesAndTagsWithoutChangingMarkdown(): void
    {
        $root = sys_get_temp_dir() . '/codejitsu-context-' . bin2hex(random_bytes(6));
        mkdir($root . '/architecture', 0777, true);
        $markdown = "# Codex\n\nThe resource index.\n";
        file_put_contents($root . '/architecture/codex.ctx', $markdown);

        try {
            $scrolls = (new ScrollDiscovery(TypeRegistry::builtins()))->discover($root);
            self::assertCount(1, $scrolls);
            self::assertInstanceOf(Context::class, $scrolls[0]);
            self::assertSame('architecture/codex', $scrolls[0]->name);
            self::assertSame(['architecture'], $scrolls[0]->tags);
            self::assertSame($markdown, $scrolls[0]->content());
        } finally {
            unlink($root . '/architecture/codex.ctx');
            rmdir($root . '/architecture');
            rmdir($root);
        }
    }
}
