<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Console;

use Codejitsu\Console\UsageRenderer;
use Codejitsu\Scrolls\Types\Command;
use PHPUnit\Framework\TestCase;

final class UsageRendererTest extends TestCase
{
    public function testTopLevelUsageGroupsNamespacesAndShowsChildren(): void
    {
        $renderer = new UsageRenderer();

        $scroll = $this->command('scroll', 'Manage and execute Scrolls.', [
            'run' => [
                'description' => 'Execute a Scroll by URI.',
                'usage' => 'scroll:run <uri> [arguments]',
            ],
            'sign' => [
                'description' => 'Sign one Scroll.',
                'usage' => 'scroll:sign <uri>',
            ],
        ]);

        $make = $this->command('make', 'Create framework resources.', [
            'scroll' => [
                'description' => 'Create a new Scroll.',
                'usage' => 'make:scroll <uri>',
            ],
        ]);

        $output = new UsageRenderer()->render([
            'scroll' => $scroll,
            'make' => $make,
        ]);

        self::assertStringContainsString('Scroll', $output);
        self::assertStringContainsString('scroll:run <uri> [arguments]', $output);
        self::assertStringContainsString('Execute a Scroll by URI.', $output);
        self::assertStringContainsString('Make', $output);
        self::assertStringContainsString('make:scroll <uri>', $output);
        self::assertStringContainsString('Run "codejitsu <command> --help" for more information.', $output);
        self::assertStringContainsString('<info>scroll:run <uri> [arguments]</info>', $output);
    }

    public function testNamespaceUsageShowsChildrenWithDescriptions(): void
    {
        $renderer = new UsageRenderer();
        $command = $this->command('scrolls', 'Manage Scroll resources.', [
            'list' => [
                'description' => 'List registered Scroll resources.',
                'usage' => 'scrolls:list',
            ],
        ]);

        $output = $renderer->renderNamespace($command);

        self::assertStringContainsString('Usage:', $output);
        self::assertStringContainsString('codejitsu scrolls:<subcommand> [arguments] [options]', $output);
        self::assertStringContainsString('scrolls:list', $output);
        self::assertStringContainsString('List registered Scroll resources.', $output);
    }

    /** @param array<string, array<string, mixed>> $children */
    private function command(string $name, string $description, array $children): Command
    {
        $command = new Command();
        $command->hydrate([
            'name' => $name,
            'type' => 'command',
            'description' => $description,
            'usage' => $name . ':<subcommand> [arguments] [options]',
            'commands' => $children,
        ]);

        return $command;
    }
}
