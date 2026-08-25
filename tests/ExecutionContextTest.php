<?php

declare(strict_types=1);

namespace Codejitsu\Tests;

use Codejitsu\ExecutionContext;
use Codejitsu\ExecutionPolicy;
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

    public function testDefaultPolicyDeniesExternalCapabilities(): void
    {
        $policy = ExecutionPolicy::defaults();

        self::assertFalse($policy->allowNetwork);
        self::assertFalse($policy->allowProcess);
        self::assertSame([], $policy->filesystemRoots);
        self::assertSame([], $policy->environment);
    }

    public function testExecutionContextCarriesPolicy(): void
    {
        $policy = ExecutionPolicy::defaults();
        $context = new ExecutionContext([], null, $policy);

        self::assertSame($policy, $context->policy);
    }
}
