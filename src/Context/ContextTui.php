<?php
declare(strict_types=1);
namespace Codejitsu\Context;

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
        $lines[] = '│ context:show <name> · context:search <query> · context:check │';
        $lines[] = '└──────────────────────────────────────────────────────────────┘';
        return implode("\n", $lines) . "\n";
    }
}
