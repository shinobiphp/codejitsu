<?php
declare(strict_types=1);
namespace Codejitsu\Tests\Context;

use Codejitsu\Console\Editor;
use Codejitsu\Context\ContextMemory;
use Codejitsu\Scrolls\ScrollCodex;
use PHPUnit\Framework\TestCase;

final class ContextMemoryTest extends TestCase
{
    private string $root;
    private ContextMemory $memory;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/codejitsu-context-memory-' . bin2hex(random_bytes(5));
        mkdir($this->root . '/.context/roadmap', 0777, true);
        file_put_contents($this->root . '/.context/current-state.ctx', "# Current State\n\nReady for agents.\n\n<!-- codejitsu:managed verification:start -->\nold\n<!-- codejitsu:managed verification:end -->\n");
        file_put_contents($this->root . '/.context/roadmap/current.ctx', "# Roadmap\n\nShip Context.\n");
        file_put_contents($this->root . '/.context/todo.ctx', "# Todo\n\n- Context package\n");
        $codex = (new ScrollCodex())->load($this->root . '/.context', 'context');
        $this->memory = new ContextMemory($codex, $this->root . '/.context');
    }

    protected function tearDown(): void
    {
        @unlink($this->root . '/.context/roadmap/current.ctx');
        @unlink($this->root . '/.context/current-state.ctx');
        @unlink($this->root . '/.context/todo.ctx');
        @rmdir($this->root . '/.context/roadmap'); @rmdir($this->root . '/.context'); @rmdir($this->root);
    }

    public function testListsShowsAndSearchesIndexedContext(): void
    {
        self::assertSame(['current-state', 'roadmap/current', 'todo'], array_column($this->memory->list(), 'name'));
        self::assertStringContainsString('Ready for agents.', $this->memory->show('current-state'));
        self::assertSame(['roadmap/current'], array_column($this->memory->search('ship context'), 'name'));
    }

    public function testSyncOnlyReplacesExplicitManagedSection(): void
    {
        self::assertSame(1, $this->memory->sync('verification', "135 tests / 466 assertions\n"));
        $content = file_get_contents($this->root . '/.context/current-state.ctx');
        self::assertStringContainsString('Ready for agents.', $content);
        self::assertStringContainsString('135 tests / 466 assertions', $content);
        self::assertStringNotContainsString("\nold\n", $content);
    }

    public function testResumeCombinesCurrentMemory(): void
    {
        $resume = $this->memory->resume();
        self::assertStringContainsString('# Current State', $resume);
        self::assertStringContainsString('# Roadmap', $resume);
        self::assertStringContainsString('# Todo', $resume);
    }

    public function testCheckReportsBrokenLocalMarkdownLink(): void
    {
        file_put_contents($this->root . '/.context/todo.ctx', "[missing](missing.ctx)\n");
        self::assertStringContainsString('missing.ctx', implode("\n", $this->memory->check()));
    }

    public function testCreatesAndEditsProjectContextThroughEditor(): void
    {
        $created = $this->memory->create('architecture/runtime', new MemoryEditor("# Runtime\n\nCreated.\n"));
        self::assertSame($this->root . '/.context/architecture/runtime.ctx', $created);
        self::assertStringContainsString('Created.', (string) file_get_contents($created));

        $editor = new MemoryEditor("# Runtime\n\nUpdated.\n");
        self::assertSame($created, $this->memory->edit('architecture/runtime', $editor));
        self::assertStringContainsString('Created.', $editor->initial);
        self::assertStringContainsString('Updated.', (string) file_get_contents($created));
        unlink($created);
        rmdir(dirname($created));
    }

    public function testCreateRejectsTraversalAndDuplicates(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->memory->create('../outside', new MemoryEditor('invalid'));
    }

    public function testCreateRejectsAbsoluteLogicalNames(): void
    {
        $this->expectException(\RuntimeException::class);
        try {
            $this->memory->create('/outside', new MemoryEditor('invalid'));
        } finally {
            @unlink($this->root . '/.context/outside.ctx');
        }
    }
}

final class MemoryEditor implements Editor
{
    public string $initial = '';
    public function __construct(private readonly string $result) {}
    public function edit(string $initial = ''): string
    {
        $this->initial = $initial;
        return $this->result;
    }
}
