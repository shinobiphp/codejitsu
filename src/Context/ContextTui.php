<?php
declare(strict_types=1);
namespace Codejitsu\Context;

use Codejitsu\Console\Editor;
use Codejitsu\Console\Questioner;

final readonly class ContextTui
{
    public function __construct(private ContextMemory $memory) {}

    public function render(): string
    {
        $lines = [
            '┌─ CODEJITSU CONTEXT ─────────────────────────────────────────┐',
            '│ Project memory                                              │',
            '├──────────────────────────────────────────────────────────────┤',
        ];
        foreach ($this->memory->list() as $item) {
            $lines[] = sprintf('│  %-58s │', mb_strimwidth($item['name'], 0, 58, '…'));
        }
        $lines[] = '├──────────────────────────────────────────────────────────────┤';
        $lines[] = '│ Select to edit · Create new Context · context:check          │';
        $lines[] = '└──────────────────────────────────────────────────────────────┘';
        return implode("\n", $lines) . "\n";
    }

    public function run(Questioner $questioner, Editor $editor): string
    {
        $choices = [...array_column($this->memory->list(), 'name'), 'Create new…', 'Quit'];
        $selected = $questioner->select('Context Scroll', $choices);
        if ($selected === 'Quit') return "No changes.\n";
        if ($selected === 'Create new…') {
            $name = trim($questioner->ask('Context name: '));
            $this->memory->create($name, $editor);
            return sprintf("Created Context Scroll [%s].\n", $name);
        }
        $this->memory->edit($selected, $editor);
        return sprintf("Updated Context Scroll [%s].\n", $selected);
    }
}
