<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Tools;

final readonly class ToolResult
{
    public function __construct(public mixed $value) {}
}
