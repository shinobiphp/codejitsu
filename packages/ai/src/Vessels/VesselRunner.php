<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Vessels;

use Codejitsu\Ai\Definitions\DefinitionLoader;
use Codejitsu\Ai\Exceptions\DefinitionException;
use Codejitsu\Ai\Prompt\PromptAssembler;
use Codejitsu\Ai\Runtime\RuntimeRegistry;
use Codejitsu\Ai\Tools\ToolApproval;
use Codejitsu\Ai\Tools\ToolPolicy;
use Codejitsu\Ai\Tools\DenyConsequentialTools;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Types\Context;

final readonly class VesselRunner
{
    public function __construct(
        private DefinitionLoader $loader,
        private PromptAssembler $prompts,
        private ToolPolicy $tools,
        private RuntimeRegistry $runtimes,
        private ScrollCodex $codex,
    ) {}

    public function start(
        string $vessel,
        ?string $spark = null,
        array $skills = [],
        array $skillInputs = [],
        array $tools = [],
        array $toolsets = [],
        ?ToolApproval $approval = null,
    ): VesselSession {
        $vesselDefinition = $this->loader->vessel($vessel);
        $sparkReference = $spark ?? $vesselDefinition->spark;
        if (!in_array($sparkReference, $vesselDefinition->allowedSparks, true)) {
            throw new DefinitionException(sprintf('Spark [%s] is not allowed by Vessel [%s].', $sparkReference, $vesselDefinition->name));
        }
        $sparkDefinition = $this->loader->spark($sparkReference);
        $selectedSkills = array_values(array_unique([...$sparkDefinition->skills, ...$skills]));
        foreach ($selectedSkills as $reference) {
            if (!in_array($reference, $sparkDefinition->allowedSkills, true)) throw new DefinitionException(sprintf('Skill [%s] is not allowed by Spark [%s].', $reference, $sparkDefinition->name));
        }
        $skillDefinitions = array_map($this->loader->skill(...), $selectedSkills);
        $contextReferences = [...$sparkDefinition->contexts, ...$vesselDefinition->contexts];
        foreach ($skillDefinitions as $skillDefinition) {
            $contextReferences = [...$contextReferences, ...$skillDefinition->contexts];
        }
        $contextReferences = array_values(array_unique($contextReferences));
        $contexts = [];
        foreach ($contextReferences as $reference) {
            $scroll = $this->codex->resolve($reference);
            if (!$scroll instanceof Context) throw new DefinitionException(sprintf('Context [%s] was not found.', $reference));
            $contexts[$reference] = $scroll->content();
        }
        $selectedToolsetRefs = array_values(array_unique([...$sparkDefinition->toolsets, ...$toolsets]));
        $toolsetDefinitions = array_map($this->loader->toolset(...), $selectedToolsetRefs);
        $resolvedTools = $this->tools->resolve($sparkDefinition, $vesselDefinition, $tools, $toolsets);
        $assembled = $this->prompts->assemble($sparkDefinition, $skillDefinitions, $skillInputs, $contexts, $toolsetDefinitions, 'session');
        $instructions = $assembled->context === '' ? $assembled->instructions : $assembled->instructions . "\n\nContext:\n" . $assembled->context;
        return new VesselSession(
            $this->runtimes->get($vesselDefinition->runtime),
            $instructions,
            $vesselDefinition->model ?? $sparkDefinition->model,
            $resolvedTools,
            $vesselDefinition->limits,
            ['vessel' => $vesselDefinition->name, 'spark' => $sparkDefinition->name, 'provider' => $vesselDefinition->provider],
            $approval ?? new DenyConsequentialTools(),
        );
    }
}
