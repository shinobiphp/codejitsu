<?php
declare(strict_types=1);
namespace Codejitsu\Tests\Packages;

use Codejitsu\Packages\PackageRegistry;
use Codejitsu\Scrolls\Scroll;
use Codejitsu\Scrolls\ScrollCodex;
use PHPUnit\Framework\TestCase;

final class PackageRegistryTest extends TestCase
{
    public function testRegistersTypesBeforeLoadingPackageSources(): void
    {
        $root = sys_get_temp_dir() . '/codejitsu-registry-' . bin2hex(random_bytes(5));
        mkdir($root . '/scrolls', 0777, true);
        file_put_contents($root . '/scrolls/demo.world', "name: demo\nversion: 1.0.0\ntitle: Demo\n");
        try {
            $compiled = ['format' => 1, 'fingerprint' => str_repeat('a', 64), 'packages' => [[
                'name' => 'acme/ui', 'root' => $root,
                'types' => ['world' => ['plural' => 'worlds', 'extension' => 'world', 'scheme' => 'world://', 'class' => WorldPackageFixture::class, 'codec' => 'neon']],
                'sources' => ['ui' => ['path' => 'scrolls']],
            ]]];
            $codex = new ScrollCodex();
            (new PackageRegistry())->apply($compiled, $codex);
            self::assertInstanceOf(WorldPackageFixture::class, $codex->resolve('world://demo@ui#1.0.0'));
        } finally {
            @unlink($root . '/scrolls/demo.world'); @rmdir($root . '/scrolls'); @rmdir($root);
        }
    }
}

final class WorldPackageFixture extends Scroll { public const string TYPE = 'world'; }
