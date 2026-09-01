<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls;

use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Types\Capability;
use Codejitsu\Scrolls\Types\Command;
use Codejitsu\Scrolls\Types\Schema;
use PHPUnit\Framework\TestCase;

final class ScrollCodexTest extends TestCase
{
    public function testItLoadsAllScrollTypesFromAResourceRoot(): void
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'codejitsu-scrolls-' . bin2hex(random_bytes(4));
        mkdir($root . '/commands', 0777, true);
        mkdir($root . '/schemas', 0777, true);
        mkdir($root . '/capabilities', 0777, true);

        file_put_contents($root . '/commands/hello.cmd', <<<'NEON'
name: hello
type: command
schema: schema://hello
capability: capability://hello
NEON);
        file_put_contents($root . '/schemas/hello.schema', <<<'NEON'
name: hello
type: schema
definition:
  type: array
  items:
    type: string
NEON);
        file_put_contents($root . '/capabilities/hello.capability', <<<'NEON'
name: hello
type: capability
target: Codejitsu\Tests\Scrolls\ScrollCodexTest::hello
NEON);

        try {
            $codex = (new ScrollCodex())->load($root);

            self::assertInstanceOf(Command::class, $codex->resolve('cmd://hello'));
            self::assertInstanceOf(Schema::class, $codex->resolve('schema://hello'));
            self::assertInstanceOf(Capability::class, $codex->resolve('capability://hello'));
            self::assertSame('schema://hello', $codex->resolve('cmd://hello')->references()['schema']);
        } finally {
            foreach (glob($root . '/*/hello.*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($root . '/commands');
            @rmdir($root . '/schemas');
            @rmdir($root . '/capabilities');
            @rmdir($root);
        }
    }

    public function testItResolvesAndInvokesACommandThroughItsCapabilityReference(): void
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'codejitsu-scrolls-' . bin2hex(random_bytes(4));
        mkdir($root . '/commands', 0777, true);
        mkdir($root . '/capabilities', 0777, true);

        file_put_contents($root . '/commands/hello.cmd', <<<'NEON'
name: hello
type: command
capability: capability://hello
NEON);
        file_put_contents($root . '/capabilities/hello.capability', <<<'NEON'
name: hello
type: capability
target: Codejitsu\Tests\Scrolls\ScrollCodexTest::hello
NEON);

        try {
            $codex = (new ScrollCodex())->load($root);
            $command = $codex->resolve('command://hello#1.0.0');

            self::assertInstanceOf(Command::class, $command);
            self::assertSame("Hello, ninja!" . PHP_EOL, $command('ninja'));
            self::assertSame("Hello, ninja!" . PHP_EOL, $codex->invoke('command', 'hello#1.0.0', 'ninja'));
        } finally {
            foreach (glob($root . '/*/hello.*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($root . '/commands');
            @rmdir($root . '/capabilities');
            @rmdir($root);
        }
    }

    public static function hello(\Codejitsu\ExecutionContext $context): string
    {
        return sprintf("Hello, %s!%s", (string) ($context->arguments[0] ?? 'shinobi'), PHP_EOL);
    }
}
