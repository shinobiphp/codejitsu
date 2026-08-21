<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls;

use Codejitsu\Scrolls\CommandDiscovery;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Types\Command;
use PHPUnit\Framework\TestCase;

final class CommandDiscoveryTest extends TestCase
{
    public function testItDiscoversCommandScrollsFromNeonFiles(): void
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'codejitsu-command-scrolls-' . bin2hex(random_bytes(4));
        mkdir($directory, 0777, true);

        try {
            file_put_contents($directory . DIRECTORY_SEPARATOR . 'hello.neon', <<<'NEON'
name: hello
description: Say hello.
usage: hello [name]
type: command
target: Codejitsu\\Commands\\Hello::run
NEON);

            $codex = new ScrollCodex();
            self::assertSame(1, CommandDiscovery::fromDirectory($directory, $codex));

            $command = $codex->get('hello');
            self::assertInstanceOf(Command::class, $command);
            self::assertSame('hello', $command->name);
            self::assertSame('hello [name]', $command->usage());
        } finally {
            @unlink($directory . DIRECTORY_SEPARATOR . 'hello.neon');
            @rmdir($directory);
        }
    }
}
