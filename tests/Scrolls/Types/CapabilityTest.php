<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls\Types;

use Codejitsu\ExecutionContext;
use Codejitsu\Scrolls\Types\Capability;
use PHPUnit\Framework\TestCase;

final class CapabilityTest extends TestCase
{
    public function testItInvokesItsCallableTargetThroughExecutionContext(): void
    {
        $capability = (new Capability())->hydrate([
            'name' => 'add',
            'target' => static fn (ExecutionContext $context): int => $context->arguments[0] + $context->arguments[1],
        ]);

        self::assertSame(5, $capability(2, 3));
        self::assertSame(5, $capability->execute(new ExecutionContext([2, 3])));
    }
}
