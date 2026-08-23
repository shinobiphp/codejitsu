<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Uri;

use Codejitsu\Uri\Uri;
use PHPUnit\Framework\TestCase;

final class SourceSelectorTest extends TestCase
{
    public function testItParsesAResourceSourceCascade(): void
    {
        $uri = new Uri('app://archiq/analysis/php@tenant.global#1.2.0');

        self::assertSame('app', $uri->type);
        self::assertSame('archiq', $uri->target);
        self::assertSame('analysis/php', $uri->path);
        self::assertSame(['tenant', 'global'], $uri->sources);
        self::assertSame('1.2.0', $uri->version);
        self::assertSame('app://archiq/analysis/php@tenant.global#1.2.0', (string) $uri);
    }

    public function testItParsesAReverseSourceCascade(): void
    {
        $uri = new Uri('config://app@global.tenant');

        self::assertSame('app', $uri->target);
        self::assertNull($uri->path);
        self::assertSame(['global', 'tenant'], $uri->sources);
        self::assertSame('config://app@global.tenant', (string) $uri);
    }

    public function testItHasNoExplicitSourcesWhenSelectorIsOmitted(): void
    {
        $uri = new Uri('config://app#0.1.0');

        self::assertSame([], $uri->sources);
        self::assertSame('config://app#0.1.0', (string) $uri);
    }
}
