<?php
declare(strict_types=1);
namespace Codejitsu\Tests\Context;

use Codejitsu\Context\ContextMemory;
use Codejitsu\Context\ContextTui;
use Codejitsu\Scrolls\ScrollCodex;
use PHPUnit\Framework\TestCase;

final class ContextTuiTest extends TestCase
{
    public function testRendersTerminalMemoryBrowserFromIndexedContext(): void
    {
        $root = dirname(__DIR__, 2);
        $memory = new ContextMemory((new ScrollCodex())->load($root . '/.context', 'context'), $root . '/.context');
        $screen = (new ContextTui($memory))->render();
        self::assertStringContainsString('CODEJITSU CONTEXT', $screen);
        self::assertStringContainsString('current-state', $screen);
        self::assertStringContainsString('context:show', $screen);
    }
}
