<?php
declare(strict_types=1);
namespace Codejitsu\Tests\Catalog;

use Codejitsu\Catalog\PackageCatalogActionProvider;
use Codejitsu\Contracts\Packages\InstalledPackages;
use Codejitsu\Contracts\ProcessRunner;
use Codejitsu\PackageManager;
use Codejitsu\Packages\InstalledPackage;
use Codejitsu\ProcessResult;
use PHPUnit\Framework\TestCase;

final class PackageCatalogActionProviderTest extends TestCase
{
    public function testOffersInstallForAvailablePackage(): void
    {
        $installed = new ActionInstalledPackages([]);
        $provider = new PackageCatalogActionProvider(new PackageManager(new ActionRunner(), 'composer', $installed), $installed);
        self::assertSame(['info', 'install'], array_keys($provider->actions($this->entry(), '/project')));
    }

    public function testOffersUpdateAndUninstallForInstalledPackage(): void
    {
        $installed = new ActionInstalledPackages([
            new InstalledPackage('codejitsu/ui', '0.1.0', '/packages/ui', '/packages/ui/codejitsu.package'),
        ]);
        $runner = new ActionRunner();
        $provider = new PackageCatalogActionProvider(new PackageManager($runner, 'composer', $installed), $installed);
        self::assertSame(['info', 'update', 'uninstall'], array_keys($provider->actions($this->entry(), '/project')));
        self::assertStringContainsString('Uninstalled', $provider->execute('uninstall', $this->entry(), '/project'));
        self::assertSame([['composer', 'remove', 'codejitsu/ui', '--no-interaction', '--no-progress']], $runner->commands);
    }

    private function entry(): array
    {
        return ['identifier' => 'package://codejitsu/ui#0.1.0', 'kind' => 'package', 'location' => 'composer://codejitsu/ui'];
    }
}

final readonly class ActionInstalledPackages implements InstalledPackages
{
    public function __construct(private array $packages) {}
    public function all(string $projectRoot): array { return $this->packages; }
}

final class ActionRunner implements ProcessRunner
{
    public array $commands = [];
    public function run(array $command, string $cwd): ProcessResult
    {
        $this->commands[] = $command;
        return new ProcessResult(0, '', '');
    }
}
