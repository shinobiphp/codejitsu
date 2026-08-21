<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls\Types;

use Codejitsu\Scrolls\Types\Command;
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
}
