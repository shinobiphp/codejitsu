<?php

declare(strict_types=1);

namespace Codejitsu\Tests;

use Codejitsu\ExecutionContext;
use Codejitsu\Scrolls\ScrollCodex;
use PHPUnit\Framework\TestCase;

final class ExecutionContextTest extends TestCase
{
    public function testItCarriesArgumentsAndCodexIntoCapabilityExecution(): void
    {
        $codex = new ScrollCodex();
        $context = new ExecutionContext(['ninja'], $codex);

        self::assertSame(['ninja'], $context->arguments);
        self::assertSame($codex, $context->codex);
    }
}
