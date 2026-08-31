<?php

declare(strict_types=1);

namespace Codejitsu\Tests;

use Codejitsu\Catalog\CatalogIndex;
use Codejitsu\Contracts\ProcessRunner;
use Codejitsu\PackageManager;
use Codejitsu\ProcessResult;
use Codejitsu\Contracts\Packages\InstalledPackages;
use Codejitsu\Packages\InstalledPackage;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\TypeDefinition;
use Codejitsu\Scrolls\TypeRegistry;
use Codejitsu\Scrolls\Types\Catalog;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PackageManagerTest extends TestCase
{
    public function testListShowsOnlyInstalledCodejitsuPackagesWithStatus(): void
    {
        $packages = new FakeInstalledPackages([
            new InstalledPackage('codejitsu/context', '1.0.0', '/packages/context', '/packages/context/codejitsu.package'),
            new InstalledPackage('shinobiphp/codejitsu-ui', '0.1.0', '/packages/ui', '/packages/ui/codejitsu.package'),
        ]);
        $manager = new PackageManager(packages: $packages);

        self::assertSame(
            "codejitsu/context                        1.0.0        installed\nshinobiphp/codejitsu-ui                  0.1.0        installed\n",
            $manager->list('/project'),
        );
    }

    public function testListCombinesCatalogPackagesWithInstalledStatus(): void
    {
        $types = TypeRegistry::builtins();
        $types->register(new TypeDefinition('catalog', 'catalogs', 'catalog', 'catalog://', Catalog::class));
        $codex = new ScrollCodex(types: $types);
        $codex->registerScroll((new Catalog())->hydrate(['name' => 'packages', 'entries' => [
            ['identifier' => 'package://vendor/available#1.0.0', 'kind' => 'package', 'version' => '^1.0'],
            ['identifier' => 'package://vendor/installed#1.0.0', 'kind' => 'package', 'version' => '^1.0'],
        ]]), 'test');
        $installed = new FakeInstalledPackages([
            new InstalledPackage('vendor/installed', '1.2.0', '/packages/installed', '/packages/installed/codejitsu.package'),
        ]);
        $manager = new PackageManager(packages: $installed, catalog: new CatalogIndex($codex));

        self::assertSame(
            "vendor/available                         ^1.0         available\nvendor/installed                         1.2.0        installed\n",
            $manager->list('/project'),
        );
    }

    public function testInfoDelegatesToComposerAndReturnsStdout(): void
    {
        $runner = new FakeProcessRunner(new ProcessResult(0, '{"name":"vendor/pkg"}', ''));
        $manager = new PackageManager($runner, 'composer-test');

        self::assertSame('{"name":"vendor/pkg"}', $manager->info('vendor/pkg', '/project'));
        self::assertSame([['composer-test', 'show', 'vendor/pkg', '--format=json']], $runner->commands);
        self::assertSame(['/project'], $runner->directories);
    }

    public function testMutationsReturnExitCodesAndUseNonInteractiveComposerCommands(): void
    {
        $runner = new FakeProcessRunner(new ProcessResult(0, '', ''));
        $manager = new PackageManager($runner, 'composer');

        self::assertSame(0, $manager->install('vendor/pkg', '/project'));
        self::assertSame(0, $manager->remove('vendor/pkg', '/project'));
        self::assertSame(0, $manager->update(null, '/project'));
        self::assertSame([
            ['composer', 'require', 'vendor/pkg', '--no-interaction', '--no-progress'],
            ['composer', 'remove', 'vendor/pkg', '--no-interaction', '--no-progress'],
            ['composer', 'update', '--no-interaction', '--no-progress'],
        ], $runner->commands);
    }

    public function testInfoPropagatesComposerDiagnostics(): void
    {
        $manager = new PackageManager(new FakeProcessRunner(new ProcessResult(2, '', 'package not found')));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('package not found');
        $manager->info('vendor/missing', '/project');
    }

    public function testSearchAndUninstallUseComposerBoundary(): void
    {
        $runner = new FakeProcessRunner(new ProcessResult(0, '[]', ''));
        $manager = new PackageManager($runner, 'composer');
        self::assertSame('[]', $manager->search('shinobi', '/project'));
        self::assertSame(0, $manager->uninstall('vendor/pkg', '/project'));
        self::assertSame([
            ['composer', 'search', 'shinobi', '--format=json'],
            ['composer', 'remove', 'vendor/pkg', '--no-interaction', '--no-progress'],
        ], $runner->commands);
    }
}

final readonly class FakeInstalledPackages implements InstalledPackages
{
    /** @param list<InstalledPackage> $packages */
    public function __construct(private array $packages) {}
    public function all(string $projectRoot): array { return $this->packages; }
}

final class FakeProcessRunner implements ProcessRunner
{
    /** @var list<array<string>> */
    public array $commands = [];
    /** @var list<string> */
    public array $directories = [];

    public function __construct(private readonly ProcessResult $result) {}

    public function run(array $command, string $cwd): ProcessResult
    {
        $this->commands[] = $command;
        $this->directories[] = $cwd;
        return $this->result;
    }
}
