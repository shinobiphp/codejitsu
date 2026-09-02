<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Tests\Scrolls;

use Codejitsu\Ai\Scrolls\Spark;
use Codejitsu\Ai\Scrolls\Tool;
use Codejitsu\Ai\Scrolls\Toolset;
use Codejitsu\Ai\Scrolls\Vessel;
use Codejitsu\Ai\Scrolls\Provider;
use Codejitsu\Ai\Scrolls\Model;
use Codejitsu\Codecs\Neon;
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
        self::assertSame(Provider::class, $types['provider']['class']);
        self::assertSame(Model::class, $types['model']['class']);
        self::assertFileExists($root . '/resources/catalogs/providers.catalog');
        $catalog=(new Neon())->decode((string)file_get_contents($root.'/resources/catalogs/ai-models.catalog'));
        self::assertSame('model://codejitsu/local#1.0.0',$catalog['entries'][0]['identifier']);
        self::assertFileExists($root.'/resources/providers/ollama/local.provider');
        self::assertFileExists($root.'/resources/providers/groq/free.provider');
        foreach (['engineer','scribe','product-designer','marketer','seo','security'] as $spark) {
            self::assertFileExists($root.'/resources/sparks/'.$spark.'.spark');
        }
        self::assertFileExists($root.'/resources/vessels/code.vessel');
        self::assertFileExists($root.'/resources/vessels/cognition.vessel');
    }
}
