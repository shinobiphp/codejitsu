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
target: Codejitsu\Commands\Hello::run
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
}
