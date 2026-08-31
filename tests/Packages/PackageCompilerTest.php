<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Packages;

use Codejitsu\Packages\InstalledPackage;
use Codejitsu\Packages\PackageCompiler;
use RuntimeException;
use PHPUnit\Framework\TestCase;

final class PackageCompilerTest extends TestCase
{
    public function testCompilesDeterministicPlainPackageData(): void
    {
        $root = $this->fixture('acme/ui', 'world', 'world');
        try {
            $installed = new InstalledPackage('acme/ui', '1.2.0', $root, $root . '/codejitsu.package');
            $first = (new PackageCompiler())->compile([$installed]);
            $second = (new PackageCompiler())->compile([$installed]);

            self::assertSame(1, $first['format']);
            self::assertSame($first, $second);
            self::assertSame('acme/ui', $first['packages'][0]['name']);
            self::assertSame($root, $first['packages'][0]['root']);
            self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $first['fingerprint']);
        } finally {
            $this->remove($root);
        }
    }

    public function testRejectsAggregateTypeConflictsWithPackageProvenance(): void
    {
        $alpha = $this->fixture('acme/alpha', 'world', 'world');
        $beta = $this->fixture('acme/beta', 'realm', 'world');
        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('acme/alpha');
            $this->expectExceptionMessage('acme/beta');
            (new PackageCompiler())->compile([
                new InstalledPackage('acme/alpha', '1.0.0', $alpha, $alpha . '/codejitsu.package'),
                new InstalledPackage('acme/beta', '1.0.0', $beta, $beta . '/codejitsu.package'),
            ]);
        } finally {
            $this->remove($alpha);
            $this->remove($beta);
        }
    }

    private function fixture(string $name, string $type, string $extension): string
    {
        $root = sys_get_temp_dir() . '/codejitsu-compile-' . bin2hex(random_bytes(5));
        mkdir($root . '/scrolls', 0777, true);
        file_put_contents($root . '/codejitsu.package', sprintf(
            "name: %s\nversion: 1.0.0\ntypes:\n  %s:\n    plural: %ss\n    extension: %s\n    scheme: %s://\n    class: Acme\\%s\n    codec: neon\nsources:\n  %s:\n    path: scrolls\n",
            $name, $type, $type, $extension, $type, ucfirst($type), $type,
        ));
        return $root;
    }

    private function remove(string $root): void
    {
        @unlink($root . '/codejitsu.package');
        @rmdir($root . '/scrolls');
        @rmdir($root);
    }
}
