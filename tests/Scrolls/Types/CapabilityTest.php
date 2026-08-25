<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls\Types;

use Codejitsu\ExecutionContext;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Types\Capability;
use Codejitsu\Substrate\Php;
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

    public function testSourceCapabilityUsesRegisteredSubstrate(): void
    {
        $codex = new ScrollCodex();
        $codex->substrates()->register('php', new Php());

        $capability = (new Capability())->hydrate([
            'name' => 'hello',
            'substrate' => 'php',
            'source' => '<?php return "hello";',
        ])->bind($codex);

        self::assertSame('hello', $capability->execute(new ExecutionContext()));
    }
}
