<?php

declare(strict_types=1);

namespace Codejitsu\Tests;

use Codejitsu\Contracts\ProcessRunner;
use Codejitsu\PackageManager;
use Codejitsu\ProcessResult;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PackageManagerTest extends TestCase
{
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
