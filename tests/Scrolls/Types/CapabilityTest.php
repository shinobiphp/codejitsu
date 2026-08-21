<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls\Types;

use Codejitsu\Scrolls\Types\Capability;
use PHPUnit\Framework\TestCase;

final class CapabilityTest extends TestCase
{
    public function testItInvokesItsCallableTarget(): void
    {
        $capability = (new Capability())->hydrate([
            'name' => 'add',
            'target' => static fn (int $left, int $right): int => $left + $right,
        ]);

        self::assertSame(5, $capability(2, 3));
        self::assertSame(5, $capability->execute(2, 3));
    }
}
