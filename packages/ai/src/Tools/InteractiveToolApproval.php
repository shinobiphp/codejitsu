<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Tools;

final readonly class InteractiveToolApproval implements ToolApproval
{
    public function __construct(private \Closure $confirm) {}

    public function approve(ToolCall $call): bool
    {
        return !$call->tool->consequential || (bool) ($this->confirm)($call);
    }
}
