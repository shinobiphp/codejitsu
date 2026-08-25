<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Substrate;

use Codejitsu\Substrate\Detector;
use PHPUnit\Framework\TestCase;

final class DetectorTest extends TestCase
{
    public function testDetectsPhpTag(): void
    {
        self::assertSame('php', (new Detector())->detect('<?php return 1;'));
    }

    /** @dataProvider shebangs */
    public function testDetectsShebang(string $source, string $expected): void
    {
        self::assertSame($expected, (new Detector())->detect($source));
    }

    public static function shebangs(): iterable
    {
        yield ['#!/usr/bin/env lua\nreturn 1', 'lua'];
        yield ['#!/usr/bin/env node\n1 + 2', 'javascript'];
        yield ['#!/usr/bin/env javascript\n1 + 2', 'javascript'];
        yield ['#!/usr/bin/env js\n1 + 2', 'javascript'];
        yield ['#!/usr/bin/env wasmtime\n(module)', 'wasm'];
        yield ['#!/usr/bin/lua\nreturn 1', 'lua'];
        yield ['#!/usr/bin/node\n1 + 2', 'javascript'];
        yield ['#!/usr/bin/wasmtime\n(module)', 'wasm'];
    }

    public function testFallsBackToDefault(): void
    {
        self::assertSame('php', (new Detector())->detect('return 1;'));
    }
}
