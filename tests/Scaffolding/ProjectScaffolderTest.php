<?php
declare(strict_types=1);
namespace Codejitsu\Tests\Scaffolding;

use Codejitsu\Codecs\Neon;
use Codejitsu\Scaffolding\ProjectScaffolder;
use PHPUnit\Framework\TestCase;

final class ProjectScaffolderTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/codejitsu-scaffold-' . bin2hex(random_bytes(5));
        mkdir($this->root, 0755, true);
    }

    protected function tearDown(): void { $this->remove($this->root); }

    public function testCreatesProjectCatalog(): void
    {
        $path = (new ProjectScaffolder($this->root))->catalog('private');
        self::assertSame($this->root . '/catalogs/private.catalog', $path);
        $data = (new Neon())->decode((string) file_get_contents($path));
        self::assertSame('private', $data['name']);
        self::assertSame([], $data['entries']);
    }

    public function testCreatesUninstalledPackageAndAddsItToProjectCatalog(): void
    {
        $path = (new ProjectScaffolder($this->root))->package('codejitsu/ui', 'Astro UI integration');
        self::assertSame($this->root . '/packages/ui', $path);
        $composer = json_decode((string) file_get_contents($path . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('codejitsu-pkg', $composer['type']);
        self::assertSame('codejitsu.package', $composer['extra']['codejitsu']['manifest']);
        self::assertDirectoryExists($path . '/src');
        self::assertDirectoryExists($path . '/tests');

        $catalog = (new Neon())->decode((string) file_get_contents($this->root . '/catalogs/packages.catalog'));
        self::assertSame('package://codejitsu/ui#0.1.0', $catalog['entries'][0]['identifier']);
        self::assertSame('composer://codejitsu/ui', $catalog['entries'][0]['location']);
        self::assertFileDoesNotExist($this->root . '/composer.lock');
    }

    public function testRejectsInvalidNames(): void
    {
        $this->expectException(\RuntimeException::class);
        (new ProjectScaffolder($this->root))->catalog('../private');
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) return;
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $child = $path . '/' . $entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
