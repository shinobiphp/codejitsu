<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Packages;

use Codejitsu\Packages\PackageCache;
use RuntimeException;
use PHPUnit\Framework\TestCase;

final class PackageCacheTest extends TestCase
{
    public function testWritesReadsReportsAndClearsCache(): void
    {
        $root = sys_get_temp_dir() . '/codejitsu-cache-' . bin2hex(random_bytes(5));
        $path = $root . '/codejitsu/packages.php';
        $data = ['format' => 1, 'fingerprint' => str_repeat('a', 64), 'packages' => []];
        try {
            $cache = new PackageCache();
            $cache->write($path, $data);
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

    public function testRejectsMalformedCache(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'codejitsu-cache-');
        file_put_contents($path, '<?php return ["format" => 99];');
        try {
            $this->expectException(RuntimeException::class);
            (new PackageCache())->read($path);
        } finally {
            @unlink($path);
        }
    }
}
