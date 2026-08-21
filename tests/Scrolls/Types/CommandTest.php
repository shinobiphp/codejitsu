<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls\Types;

use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Types\Capability;
use Codejitsu\Scrolls\Types\Command;
use Codejitsu\Scrolls\Types\Schema;
use PHPUnit\Framework\TestCase;

final class CommandTest extends TestCase
{
    public function testItCarriesCliMetadataAndExecutesItsTarget(): void
    {
        $command = (new Command())->hydrate([
            'name' => 'hello',
            'description' => 'Say hello.',
            'usage' => 'hello [name]',
            'target' => static fn (string $name): string => "Hello, {$name}!",
        ]);

        self::assertSame('Say hello.', $command->description());
        self::assertSame('hello [name]', $command->usage());
        self::assertSame('Hello, B!', $command->execute('B'));
    }

    public function testItResolvesReferencedSchemaAndCapabilityScrolls(): void
    {
        $codex = new ScrollCodex();

        $schema = (new Schema())->hydrate([
            'name' => 'hello',
            'definition' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
            ],
        ]);

        $capability = (new Capability())->hydrate([
            'name' => 'hello',
            'target' => static fn (array $arguments): string => sprintf(
                'Hello, %s!',
                $arguments[0] ?? 'shinobi',
            ),
        ]);

        $command = (new Command())->hydrate([
            'name' => 'hello',
            'schema' => 'schema://hello',
            'capability' => 'capability://hello',
        ]);

        $codex
            ->registerScroll($schema)
            ->registerScroll($capability)
            ->registerScroll($command);

        self::assertSame('schema://hello', $command->references()['schema']);
        self::assertSame('capability://hello', $command->references()['capability']);
        self::assertSame('Hello, B!', $command->execute('B'));
    }

    public function testSchemaReferenceRejectsInvalidPayload(): void
    {
        $codex = new ScrollCodex();

        $schema = (new Schema())->hydrate([
            'name' => 'names',
            'definition' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
            ],
        ]);

        $command = (new Command())->hydrate([
            'name' => 'names',
            'schema' => 'schema://names',
            'target' => static fn (array $payload): array => $payload,
        ]);

        $codex
            ->registerScroll($schema)
            ->registerScroll($command);

        $this->expectException(\Throwable::class);
        $command->execute(['valid', 123]);
    }

    public function testNamespaceStoresChildDefinitionsWithoutHydratingThem(): void
    {
        $command = (new Command())->hydrate([
            'name' => 'scrolls',
            'description' => 'Manage Scrolls.',
            'usage' => 'scrolls <command> [arguments] [options]',
            'commands' => [
                'hello' => [
                    'description' => 'Say hello.',
                    'usage' => 'scrolls hello [name]',
                    'schema' => 'schema://hello',
                    'capability' => 'capability://hello',
                ],
            ],
        ]);

        self::assertTrue($command->isNamespace());
        self::assertSame('Say hello.', $command->commands()['hello']['description']);
        self::assertSame('schema://hello', $command->commands()['hello']['schema']);
        self::assertNull($command->child('missing'));
    }

    public function testNamespaceExecutesChildUsingReferencedSchemaAndCapability(): void
    {
        $codex = new ScrollCodex();

        $schema = (new Schema())->hydrate([
            'name' => 'hello',
            'definition' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
            ],
        ]);

        $capability = (new Capability())->hydrate([
            'name' => 'hello',
            'target' => static fn (array $arguments): string => sprintf(
                'Hello, %s!',
                $arguments[0] ?? 'shinobi',
            ),
        ]);

        $command = (new Command())->hydrate([
            'name' => 'scrolls',
            'commands' => [
                'hello' => [
                    'schema' => 'schema://hello',
                    'capability' => 'capability://hello',
                ],
            ],
        ]);

        $codex
            ->registerScroll($schema)
            ->registerScroll($capability)
            ->registerScroll($command);

        self::assertSame('Hello, B!', $command->execute('hello', 'B'));
    }
}
