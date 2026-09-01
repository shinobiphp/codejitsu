<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Packages;

use Codejitsu\Packages\PackageCache;
use RuntimeException;
use PHPUnit\Framework\TestCase;

final class PackageCacheTest extends TestCase
{
    public function testBootstrapStoresCompiledCacheUnderProjectVarDirectory(): void
    {
        self::assertSame(
            '/project/var/cache/codejitsu/packages.json',
            \Codejitsu\Packages\PackageBootstrap::cachePath('/project'),
        );
    }

    public function testWritesReadsReportsAndClearsCache(): void
    {
        $root = sys_get_temp_dir() . '/codejitsu-cache-' . bin2hex(random_bytes(5));
        $path = $root . '/codejitsu/packages.json';
        $data = ['format' => 1, 'fingerprint' => str_repeat('a', 64), 'packages' => []];
        try {
            $cache = new PackageCache();
            $cache->write($path, $data);
            self::assertJson((string) file_get_contents($path));
            self::assertSame($data, $cache->read($path));
            self::assertSame(['exists' => true, 'format' => 1, 'fingerprint' => str_repeat('a', 64), 'packages' => 0], $cache->status($path));
            $cache->clear($path);
            self::assertNull($cache->read($path));
        } finally {
            @unlink($path);
            @rmdir(dirname($path));
            @rmdir($root);
        }
    }

    public function testWritingJsonCacheRemovesLegacyPhpCache(): void
    {
        $root = sys_get_temp_dir() . '/codejitsu-cache-migration-' . bin2hex(random_bytes(5));
        mkdir($root, 0755, true);
        $path = $root . '/packages.json';
        $legacy = $root . '/packages.php';
        file_put_contents($legacy, '<?php return [];');
        try {
            (new PackageCache())->write($path, ['format' => 1, 'fingerprint' => str_repeat('a', 64), 'packages' => []]);
            self::assertFileDoesNotExist($legacy);
        } finally {
            @unlink($path);
            @unlink($legacy);
            @rmdir($root);
        }
    }

    public function testRejectsMalformedCache(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'codejitsu-cache-');
        file_put_contents($path, '{"format":99}');
        try {
            $this->expectException(RuntimeException::class);
            (new PackageCache())->read($path);
        } finally {
            @unlink($path);
        }
    }
}
