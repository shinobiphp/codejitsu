<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Apps;

use Codejitsu\Boot;
use Codejitsu\Contracts\Console\Driver;
use Codejitsu\Kernel\Kernel;
use PHPUnit\Framework\TestCase;

final class CliTest extends TestCase
{
    private string $kernelName;

    protected function setUp(): void
    {
        $this->kernelName = 'cli-test-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        Kernel::destroy($this->kernelName);
    }

    public function testNoArgumentsRenderGroupedUsage(): void
    {
        [$code, $output] = $this->runCli(['codejitsu']);

        self::assertSame(0, $code);
        self::assertStringContainsString('Available commands:', $output);
        self::assertStringContainsString('Scroll', $output);
        self::assertStringContainsString('scroll:run <uri> [arguments]', $output);
        self::assertStringContainsString('Scrolls', $output);
        self::assertStringContainsString('scrolls:list', $output);
        self::assertStringContainsString('Make', $output);
        self::assertStringContainsString('make:scroll', $output);
    }

    public function testHelpFormsRenderUsage(): void
    {
        foreach ([['codejitsu', '--help'], ['codejitsu', '-h'], ['codejitsu', 'help']] as $argv) {
            [$code, $output] = $this->runCli($argv);

            self::assertSame(0, $code);
            self::assertStringContainsString('Available commands:', $output);
        }
    }

    public function testNamespaceHelpUsesStoredChildDefinitions(): void
    {
        [$code, $output] = $this->runCli(['codejitsu', 'scrolls']);

        self::assertSame(0, $code);
        self::assertStringContainsString('Usage:', $output);
        self::assertStringContainsString('codejitsu scrolls:<subcommand> [arguments] [options]', $output);
        self::assertStringContainsString('scrolls:hello', $output);
        self::assertStringContainsString('Say hello through a nested Command Scroll.', $output);
    }

    public function testCommandHelpRendersTheResolvedNamespace(): void
    {
        [$code, $output] = $this->runCli(['codejitsu', 'scroll', '--help']);

        self::assertSame(0, $code);
        self::assertStringContainsString('codejitsu scroll:<subcommand> [arguments] [options]', $output);
        self::assertStringContainsString('scroll:run <uri> [arguments]', $output);
        self::assertStringContainsString('Execute a Scroll by URI.', $output);
    }

    public function testCommandExecutionResolvesSchemaAndCapabilityReferences(): void
    {
        [$code, $output] = $this->runCli(['codejitsu', 'hello', 'B']);

        self::assertSame(0, $code);
        self::assertSame("Hello, B!\n", $output);
    }

    public function testNamespacedCommandExecutionResolvesNestedReferences(): void
    {
        [$code, $output] = $this->runCli(['codejitsu', 'scrolls:hello', 'B']);

        self::assertSame(0, $code);
        self::assertSame("Hello, B!\n", $output);
    }

    public function testCustomDriverCanReplaceConsoleImplementation(): void
    {
        $driver = new RecordingDriver();
        $app = Boot::cli($this->kernelName, rootDir: dirname(__DIR__, 2));
        $app = $app->withDriver($driver);

        self::assertSame(42, $app->run(['codejitsu', 'hello', 'B']));
        self::assertSame(['codejitsu', 'hello', 'B'], $driver->argv);
        self::assertNotEmpty($driver->commands);
    }

    /** @param list<string> $argv */
    private function runCli(array $argv): array
    {
        $app = Boot::cli($this->kernelName, rootDir: dirname(__DIR__, 2));

        ob_start();
        $code = $app->run($argv);
        $output = ob_get_clean() ?: '';

        return [$code, $output];
    }
}

final class RecordingDriver implements Driver
{
    /** @var list<string> */
    public array $argv = [];

    /** @var list<object> */
    public array $commands = [];

    public function run(array $argv, iterable $commands): int
    {
        $this->argv = $argv;
        $this->commands = iterator_to_array($commands, false);

        return 42;
    }
}
