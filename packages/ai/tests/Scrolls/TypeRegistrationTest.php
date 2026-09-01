<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Tests\Scrolls;

use Codejitsu\Ai\Scrolls\Spark;
use Codejitsu\Ai\Scrolls\Tool;
use Codejitsu\Ai\Scrolls\Toolset;
use Codejitsu\Ai\Scrolls\Vessel;
use Codejitsu\Packages\InstalledPackage;
use Codejitsu\Packages\PackageCompiler;
use PHPUnit\Framework\TestCase;

final class TypeRegistrationTest extends TestCase
{
    public function testPackageRegistersItsScrollTypes(): void
    {
        $root = dirname(__DIR__, 2);
        $compiled = (new PackageCompiler())->compile([
            new InstalledPackage('codejitsu/ai', '0.1.0', $root, $root . '/codejitsu.package'),
        ]);
        $types = $compiled['packages'][0]['types'];

        self::assertSame(Spark::class, $types['spark']['class']);
        self::assertSame('spark://', $types['spark']['scheme']);
        self::assertSame(Vessel::class, $types['vessel']['class']);
        self::assertSame(Tool::class, $types['tool']['class']);
        self::assertSame(Toolset::class, $types['toolset']['class']);
    }
}
