<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Tools;

use Codejitsu\Ai\Definitions\ToolDefinition;

final readonly class ToolCall
{
    public function __construct(public ToolDefinition $tool, public array $arguments) {}
}
