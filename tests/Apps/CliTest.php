<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Apps;

use Codejitsu\Boot;
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

    public function testNoArgumentsRenderUsage(): void
    {
        [$code, $output] = $this->runCli(['codejitsu']);

        self::assertSame(0, $code);
        self::assertStringContainsString('Available commands:', $output);
        self::assertStringContainsString('hello', $output);
        self::assertStringContainsString('scrolls <command>', $output);
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
        self::assertStringContainsString('Usage: ./codejitsu scrolls <subcommand>', $output);
        self::assertStringContainsString('hello', $output);
        self::assertStringContainsString('Say hello through a nested Command Scroll.', $output);
    }

    public function testCommandExecutionResolvesSchemaAndCapabilityReferences(): void
    {
        [$code, $output] = $this->runCli(['codejitsu', 'hello', 'B']);

        self::assertSame(0, $code);
        self::assertSame("Hello, B!\n", $output);
    }

    public function testNamespaceCommandExecutionResolvesNestedReferences(): void
    {
        [$code, $output] = $this->runCli(['codejitsu', 'scrolls', 'hello', 'B']);

        self::assertSame(0, $code);
        self::assertSame("Hello, B!\n", $output);
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
