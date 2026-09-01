<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Neuron;

use Codejitsu\Ai\Definitions\ToolDefinition;
use Codejitsu\Ai\Tools\ToolApproval;
use Codejitsu\Ai\Tools\ToolRegistry;
use NeuronAI\Tools\Tool;

final class NeuronToolAdapter extends Tool
{
    public function __construct(private readonly ToolDefinition $definition, private readonly ToolRegistry $registry, private readonly ToolApproval $approval)
    {
        parent::__construct($definition->name, $definition->description, [], $definition->inputSchema);
        $this->setMaxRuns($definition->maxRuns);
    }

    public function execute(): void
    {
        $this->setResult($this->registry->execute($this->definition, $this->getInputs(), $this->approval)->value);
    }
}
