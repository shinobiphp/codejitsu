<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Uri;

use Codejitsu\Uri\Uri;
use PHPUnit\Framework\TestCase;

final class UriResourcePathTest extends TestCase
{
    public function testCombinesUriAuthorityAndPathIntoOneLogicalResourcePath(): void
    {
        $uri = Uri::make('context://architecture/scrolls@tenant.global#1.0.0');

        self::assertSame('architecture', $uri->target);
        self::assertSame('scrolls', $uri->path);
        self::assertSame('architecture/scrolls', $uri->resourcePath);
        self::assertSame(['tenant', 'global'], $uri->sources);
        self::assertSame('context://architecture/scrolls@tenant.global#1.0.0', (string) $uri);
    }

    public function testUsesTargetAsLogicalPathForRootResources(): void
    {
        $uri = Uri::make('app://archiq');

        self::assertSame('archiq', $uri->resourcePath);
    }
}
