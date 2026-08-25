<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Substrate;

use Codejitsu\ExecutionContext;
use Codejitsu\Substrate\Wasm;
use PHPUnit\Framework\TestCase;

final class WasmTest extends TestCase
{
    public function testExecutesWasmModule(): void
    {
        $wasmtime = trim((string) shell_exec('command -v wasmtime'));
        if ($wasmtime === '') {
            self::markTestSkipped('Wasmtime is not installed.');
        }

	$module = 'AGFzbQEAAAABBQFgAAF/AwIBAAcHAQNydW4AAAoGAQQAQQML';
        self::assertSame(3, (new Wasm())->execute($module, new ExecutionContext()));
    }
}
