<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Tools;

final class DenyConsequentialTools implements ToolApproval
{
    public function approve(ToolCall $call): bool { return !$call->tool->consequential; }
}
