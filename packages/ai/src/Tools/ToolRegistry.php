<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Tools;

use Codejitsu\Ai\Definitions\ToolDefinition;
use Codejitsu\Ai\Exceptions\DefinitionException;
use Codejitsu\ExecutionContext;
use Codejitsu\Schema\JsonSchema;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Types\Capability;

final class ToolRegistry
{
    private array $runs = [];

    public function __construct(
        private readonly ScrollCodex $codex,
        private readonly JsonSchema $validator = new JsonSchema(),
        private readonly array $executionMetadata = [],
    ) {}

    public function execute(ToolDefinition $tool, array $arguments, ToolApproval $approval): ToolResult
    {
        $this->validator->validate($arguments, $tool->inputSchema);
        $call = new ToolCall($tool, $arguments);
        if (!$approval->approve($call)) throw new DefinitionException(sprintf('Tool [%s] was not approved.', $tool->name));
        $runs = $this->runs[$tool->name] ?? 0;
        if ($runs >= $tool->maxRuns) throw new DefinitionException(sprintf('Tool [%s] exceeded its maximum run count.', $tool->name));
        $capability = $this->codex->resolve($tool->capability);
        if (!$capability instanceof Capability) throw new DefinitionException(sprintf('Tool [%s] Capability [%s] was not found.', $tool->name, $tool->capability));
        $this->runs[$tool->name] = $runs + 1;
        $payload = $this->executionMetadata === [] ? $arguments : $arguments + ['_codejitsu' => $this->executionMetadata];
        return new ToolResult($capability->execute(new ExecutionContext($payload, $this->codex)));
    }
}
