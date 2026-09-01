<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Context;

use Codejitsu\Context\ContextMemory;
use Codejitsu\Scrolls\ScrollCodex;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ContextManagedSectionTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/codejitsu-context-' . bin2hex(random_bytes(5));
        mkdir($this->root, 0755, true);
        file_put_contents($this->root . '/state.ctx', "# State\n\n<!-- codejitsu:managed agent:start -->\nold\n<!-- codejitsu:managed agent:end -->\n");
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root . '/*') ?: [] as $file) @unlink($file);
        @rmdir($this->root);
    }

    public function testItOnlyUpdatesOneExistingManagedSection(): void
    {
        $memory = $this->memory();
        $memory->updateSection('state', 'agent', "new\nvalue");
        self::assertStringContainsString("agent:start -->\nnew\nvalue\n<!-- codejitsu:managed agent:end", $memory->show('state'));
        self::assertFileDoesNotExist($this->root . '/state.ctx.lock');
    }

    public function testMissingSectionLeavesOriginalUnchanged(): void
    {
        $memory = $this->memory();
        $before = file_get_contents($this->root . '/state.ctx');
        $this->expectException(RuntimeException::class);
        try { $memory->updateSection('state', 'missing', 'new'); }
        finally { self::assertSame($before, file_get_contents($this->root . '/state.ctx')); }
    }

    public function testItRejectsPathsAndEmptyContent(): void
    {
        $memory = $this->memory();
        foreach ([['../state', 'agent', 'new'], ['state', 'agent', '']] as $arguments) {
            try { $memory->updateSection(...$arguments); self::fail('Invalid update accepted.'); }
            catch (RuntimeException) { self::assertTrue(true); }
        }
    }

    private function memory(): ContextMemory
    {
        $codex = (new ScrollCodex())->registerSource('context')->load($this->root, 'context');
        return new ContextMemory($codex, $this->root);
    }
}
