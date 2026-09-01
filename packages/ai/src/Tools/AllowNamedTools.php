<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Tools;

final readonly class AllowNamedTools implements ToolApproval
{
    public function __construct(private array $names) {}

    public function approve(ToolCall $call): bool
    {
        return !$call->tool->consequential || in_array($call->tool->name, $this->names, true);
    }
}
