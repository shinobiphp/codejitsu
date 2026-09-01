<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls;

use Codejitsu\Scrolls\CommandDiscovery;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Types\Command;
use PHPUnit\Framework\TestCase;

final class CommandDiscoveryTest extends TestCase
{
    public function testItDiscoversCommandScrollsFromCmdFiles(): void
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'codejitsu-command-scrolls-' . bin2hex(random_bytes(4));
        mkdir($directory, 0777, true);

        try {
            file_put_contents($directory . DIRECTORY_SEPARATOR . 'hello.cmd', <<<'NEON'
name: hello
description: Say hello.
usage: 'hello [name]'
type: command
target: strlen
NEON);

            $codex = new ScrollCodex();
            self::assertSame(1, CommandDiscovery::fromDirectory($directory, $codex));

            $command = $codex->get('command:hello#1.0.0');
            self::assertInstanceOf(Command::class, $command);
            self::assertSame('hello', $command->name);
            self::assertSame('hello [name]', $command->usage());
        } finally {
            @unlink($directory . DIRECTORY_SEPARATOR . 'hello.cmd');
            @rmdir($directory);
        }
    }
}
