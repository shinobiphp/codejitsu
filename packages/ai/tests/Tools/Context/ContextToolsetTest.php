<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Tests\Tools\Context;

use Codejitsu\Ai\Tools\Context\ContextCapabilities;
use Codejitsu\ExecutionContext;
use Codejitsu\Scrolls\ScrollCodex;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ContextToolsetTest extends TestCase
{
    private string $root;
    private ScrollCodex $codex;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/codejitsu-ai-context-' . bin2hex(random_bytes(5));
        mkdir($this->root . '/.context', 0755, true);
        file_put_contents($this->root . '/.context/state.ctx', "# State\n\n<!-- codejitsu:managed agent:start -->\nold\n<!-- codejitsu:managed agent:end -->\n");
        file_put_contents($this->root . '/.context/private.ctx', "# Private\n\nsecret\n");
        $this->codex = (new ScrollCodex())->registerSource('context')->load($this->root . '/.context', 'context');
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root . '/.context/*') ?: [] as $file) @unlink($file);
        @rmdir($this->root . '/.context'); @rmdir($this->root);
    }

    public function testReadToolsOnlyExposeAllowedContexts(): void
    {
        $context = $this->context([]);
        self::assertSame(['state'], array_column(ContextCapabilities::list($context), 'name'));
        self::assertStringContainsString('# State', ContextCapabilities::show($this->context(['name' => 'state'])));
        self::assertCount(1, ContextCapabilities::search($this->context(['query' => 'State'])));

        $this->expectException(RuntimeException::class);
        ContextCapabilities::show($this->context(['name' => 'private']));
    }

    public function testUpdateOnlyChangesAnAllowedManagedSection(): void
    {
        ContextCapabilities::updateSection($this->context(['name' => 'state', 'section' => 'agent', 'content' => 'remember this']));
        self::assertStringContainsString('remember this', file_get_contents($this->root . '/.context/state.ctx'));
    }

    private function context(array $arguments): ExecutionContext
    {
        return new ExecutionContext($arguments + ['_codejitsu' => [
            'contextRoot' => $this->root . '/.context',
            'allowedContexts' => ['context://state@context#1.0.0'],
        ]], $this->codex);
    }
}
