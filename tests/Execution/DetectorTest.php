<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Execution;

use Codejitsu\Substrate\Detector;
use PHPUnit\Framework\TestCase;

final class DetectorTest extends TestCase
{
    public function testItDetectsShebangSubstrates(): void
    {
        $detector = new Detector();

        self::assertSame('php', $detector->detect("#!/usr/bin/env php\nreturn true;"));
        self::assertSame('lua', $detector->detect("#!/usr/bin/env lua\nreturn true"));
        self::assertSame('javascript', $detector->detect("#!/usr/bin/node\nconsole.log('ok');"));
    }

    public function testItFallsBackToPhp(): void
    {
        self::assertSame('php', (new Detector())->detect('return true;'));
    }
}
