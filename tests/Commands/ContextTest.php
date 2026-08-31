<?php
declare(strict_types=1);
namespace Codejitsu\Tests\Commands;

use Codejitsu\Commands\Contexts;
use Codejitsu\ExecutionContext;
use Codejitsu\Scrolls\ScrollCodex;
use PHPUnit\Framework\TestCase;

final class ContextTest extends TestCase
{
    public function testListSearchShowAndResumeUseBoundCodex(): void
    {
        $root = dirname(__DIR__, 2);
        $codex = (new ScrollCodex())->load($root . '/.context', 'context');
        self::assertStringContainsString('current-state', Contexts::list(new ExecutionContext([], $codex)));
        self::assertStringContainsString('Codejitsu', Contexts::show(new ExecutionContext(['current-state'], $codex)));
        self::assertStringContainsString('architecture', Contexts::search(new ExecutionContext(['codex'], $codex)));
        self::assertStringContainsString('Current State', Contexts::resume(new ExecutionContext([], $codex)));
    }
}
