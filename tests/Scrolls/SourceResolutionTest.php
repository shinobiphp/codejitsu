<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls;

use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Enums\Scrolls\Types;
use Codejitsu\Scrolls\Types\Config;
use PHPUnit\Framework\TestCase;

final class SourceResolutionTest extends TestCase
{
    public function testImplicitResolutionUsesSourcesInReverseRegistrationOrder(): void
    {
        $global = (new Config())->hydrate([
            'name' => 'app',
            'value' => 'global',
        ]);
        $tenant = (new Config())->hydrate([
            'name' => 'app',
            'value' => 'tenant',
        ]);

        $codex = new ScrollCodex();
        $codex->registerSource('global');
        $codex->registerSource('tenant');
        $codex->registerScroll($global, 'global');
        $codex->registerScroll($tenant, 'tenant');

        self::assertSame('tenant', $codex->resolve('config://app')->value);
        self::assertSame('global', $codex->resolve('config://app@global')->value);
    }

    public function testExplicitSourceCascadeIsEvaluatedLeftToRight(): void
    {
        $global = (new Config())->hydrate([
            'name' => 'app',
            'value' => 'global',
        ]);
        $tenant = (new Config())->hydrate([
            'name' => 'other',
            'value' => 'tenant',
        ]);

        $codex = new ScrollCodex();
        $codex->registerSource('global');
        $codex->registerSource('tenant');
        $codex->registerScroll($global, 'global');
        $codex->registerScroll($tenant, 'tenant');

        self::assertSame('global', $codex->resolve('config://app@tenant.global')->value);
    }

    public function testTypedResolutionAcceptsNamesSourceSelectorsAndFullUris(): void
    {
        $framework = (new Config())->hydrate(['name' => 'app', 'value' => 'framework']);
        $package = (new Config())->hydrate(['name' => 'app', 'value' => 'package']);
        $app = (new Config())->hydrate(['name' => 'app', 'value' => 'app']);

        $codex = new ScrollCodex();
        $codex->registerScroll($framework, 'framework');
        $codex->registerScroll($package, 'package');
        $codex->registerScroll($app, 'app');

        self::assertSame('app', $codex->resolveTyped(Types::CONFIG, 'app')->value);
        self::assertSame('package', $codex->resolveTyped('config', 'app@package')->value);
        self::assertSame('framework', $codex->resolveTyped('config', 'config://app@framework')->value);
    }
}
