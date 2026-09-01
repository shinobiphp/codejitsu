<?php
declare(strict_types=1);
namespace Codejitsu\Tests\Context;

use Codejitsu\Context\ContextMemory;
use Codejitsu\Context\ContextTui;
use Codejitsu\Console\Editor;
use Codejitsu\Console\Questioner;
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
        self::assertStringContainsString('Create new Context', $screen);
    }

    public function testInteractiveMenuCreatesContextFromSelectedAction(): void
    {
        $root = sys_get_temp_dir() . '/codejitsu-context-tui-' . bin2hex(random_bytes(5));
        mkdir($root . '/.context', 0777, true);
        $memory = new ContextMemory((new ScrollCodex())->load($root . '/.context', 'context'), $root . '/.context');
        try {
            $result = (new ContextTui($memory))->run(
                new TuiQuestioner(['Create new…', 'notes/session']),
                new TuiEditor("# Session\n\nNotes.\n"),
            );
            self::assertStringContainsString('Created Context Scroll [notes/session]', $result);
            self::assertFileExists($root . '/.context/notes/session.ctx');
        } finally {
            @unlink($root . '/.context/notes/session.ctx');
            @rmdir($root . '/.context/notes');
            @rmdir($root . '/.context');
            @rmdir($root);
        }
    }
}

final class TuiQuestioner implements Questioner
{
    public function __construct(private array $answers) {}
    public function ask(string $question, string $default = ''): string { return (string) (array_shift($this->answers) ?? $default); }
    public function select(string $question, array $choices, int $default = 0): string { return (string) (array_shift($this->answers) ?? $choices[$default]); }
}

final class TuiEditor implements Editor
{
    public function __construct(private readonly string $contents) {}
    public function edit(string $initial = ''): string { return $this->contents; }
}
