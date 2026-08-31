<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls;

use Codejitsu\Boot;
use Codejitsu\Kernel\Kernel;
use Codejitsu\Scrolls\Types\Context;
use PHPUnit\Framework\TestCase;

final class ContextSourceTest extends TestCase
{
    public function testBootRegistersProjectContextAsANamedSource(): void
    {
        $root = $this->fixtureRoot();
        mkdir($root . '/.context/architecture', 0777, true);
        file_put_contents($root . '/.context/architecture/codex.ctx', "# Codex\n");
        $kernelName = 'context-source-' . bin2hex(random_bytes(4));

        try {
            $codex = Boot::cli($kernelName, rootDir: $root)->kernel->scrolls;
            $scroll = $codex->resolve('context://architecture/codex@context#1.0.0');

            self::assertInstanceOf(Context::class, $scroll);
            self::assertSame('# Codex', trim($scroll->content()));
            self::assertSame('context', $codex->query([
                'type' => 'context',
                'source' => 'context',
            ])[0]->source);
        } finally {
            Kernel::destroy($kernelName);
            $this->remove($root);
        }
    }

    public function testMissingContextDirectoryDoesNotLoadTheWorkingDirectory(): void
    {
        $root = $this->fixtureRoot();
        $kernelName = 'missing-context-' . bin2hex(random_bytes(4));

        try {
            $codex = Boot::cli($kernelName, rootDir: $root)->kernel->scrolls;
            self::assertSame([], $codex->query(['type' => 'context', 'source' => 'context']));
        } finally {
            Kernel::destroy($kernelName);
            $this->remove($root);
        }
    }

    private function fixtureRoot(): string
    {
        $root = sys_get_temp_dir() . '/codejitsu-project-' . bin2hex(random_bytes(6));
        mkdir($root, 0777, true);
        return $root;
    }

    private function remove(string $root): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $path) {
            $path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());
        }
        rmdir($root);
    }
}
