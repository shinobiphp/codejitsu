<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Tools;

interface ToolApproval
{
    public function approve(ToolCall $call): bool;
}
