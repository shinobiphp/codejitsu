<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Vessels;

use Codejitsu\Ai\Definitions\DefinitionLoader;
use Codejitsu\Ai\Neuron\NeuronRuntime;
use Codejitsu\Ai\Prompt\PromptAssembler;
use Codejitsu\Ai\Prompt\SkillRenderer;
use Codejitsu\Ai\Runtime\RuntimeRegistry;
use Codejitsu\Ai\Tools\ToolPolicy;
use Codejitsu\Ai\Tools\ToolRegistry;
use Codejitsu\Scrolls\ScrollCodex;

final class VesselRunnerFactory
{
    public function make(ScrollCodex $codex): VesselRunner
    {
        $loader = new DefinitionLoader($codex);
        $runtimes = new RuntimeRegistry();
        $runtimes->register('neuron', new NeuronRuntime(new ToolRegistry($codex)));
        return new VesselRunner($loader, new PromptAssembler(new SkillRenderer()), new ToolPolicy($loader), $runtimes, $codex);
    }
}
