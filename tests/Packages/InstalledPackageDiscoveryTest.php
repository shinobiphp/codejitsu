<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Packages;

use Codejitsu\Packages\InstalledPackageDiscovery;
use RuntimeException;
use PHPUnit\Framework\TestCase;

final class InstalledPackageDiscoveryTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/codejitsu-installed-' . bin2hex(random_bytes(5));
        mkdir($this->root . '/vendor/acme/alpha', 0777, true);
        mkdir($this->root . '/vendor/acme/zeta', 0777, true);
        file_put_contents($this->root . '/vendor/acme/alpha/codejitsu.package', "name: acme/alpha\n");
        file_put_contents($this->root . '/vendor/acme/zeta/codejitsu.package', "name: acme/zeta\n");
    }

    protected function tearDown(): void
    {
        foreach (['alpha', 'zeta'] as $name) {
            @unlink($this->root . '/vendor/acme/' . $name . '/codejitsu.package');
            @rmdir($this->root . '/vendor/acme/' . $name);
        }
        @rmdir($this->root . '/vendor/acme');
        @rmdir($this->root . '/vendor');
        @rmdir($this->root);
    }

    public function testSelectsExplicitCodejitsuPackagesInNameOrder(): void
    {
        $metadata = [
            'acme/zeta' => $this->record('zeta', 'codejitsu-pkg', '2.0.0', 'codejitsu.package'),
            'acme/library' => $this->record('alpha', 'library', '1.0.0', 'codejitsu.package'),
            'acme/alpha' => $this->record('alpha', 'codejitsu-pkg', '1.0.0', 'codejitsu.package'),
        ];

        $packages = (new InstalledPackageDiscovery($metadata))->all($this->root);

        self::assertSame(['acme/alpha', 'acme/zeta'], array_column($packages, 'name'));
        self::assertSame('1.0.0', $packages[0]->version);
        self::assertSame(realpath($this->root . '/vendor/acme/alpha/codejitsu.package'), $packages[0]->manifest);
    }

    public function testCodejitsuPackageRequiresManifestMetadata(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('acme/alpha');
        (new InstalledPackageDiscovery([
            'acme/alpha' => $this->record('alpha', 'codejitsu-pkg', '1.0.0', null),
        ]))->all($this->root);
    }

    public function testRejectsManifestTraversal(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('extra.codejitsu.manifest');
        (new InstalledPackageDiscovery([
            'acme/alpha' => $this->record('alpha', 'codejitsu-pkg', '1.0.0', '../codejitsu.package'),
        ]))->all($this->root);
    }

    /** @return array<string,mixed> */
    private function record(string $directory, string $type, string $version, ?string $manifest): array
    {
        return [
            'pretty_version' => $version,
            'install_path' => $this->root . '/vendor/acme/' . $directory,
            'type' => $type,
            'extra' => $manifest === null ? [] : ['codejitsu' => ['manifest' => $manifest]],
        ];
    }
}
