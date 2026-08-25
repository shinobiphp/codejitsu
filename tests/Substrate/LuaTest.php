<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Substrate;

use Codejitsu\ExecutionContext;
use Codejitsu\Substrate\Lua;
use PHPUnit\Framework\TestCase;

final class LuaTest extends TestCase
{
    public function testExecutesLuaSource(): void
    {
        if (!class_exists('LuaSandbox')) {
            self::markTestSkipped('LuaSandbox extension is not installed.');
        }

        self::assertSame('shinobi', (new Lua())->execute('return "shinobi"', new ExecutionContext()));
    }
}
