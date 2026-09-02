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
        ?string $provider = null,
        ?string $model = null,
        ?array $contexts = null,
    ): VesselSession {
        $vesselDefinition = $this->loader->vessel($vessel);
        $providerDefinition = $this->loader->provider($provider ?? $vesselDefinition->provider);
        $sparkReference = $spark ?? $vesselDefinition->spark;
        $sparkDefinition = $this->loader->spark($sparkReference);
        if (!$this->allows($vesselDefinition->allowedSparks, 'spark', $sparkDefinition->name)) {
            throw new DefinitionException(sprintf('Spark [%s] is not allowed by Vessel [%s].', $sparkReference, $vesselDefinition->name));
        }
        $selectedSkills = array_values(array_unique([...$sparkDefinition->skills, ...$skills]));
        $skillDefinitions = [];
        foreach ($selectedSkills as $reference) {
            $skillDefinition = $this->loader->skill($reference);
            if (!$this->allows($sparkDefinition->allowedSkills, 'skill', $skillDefinition->name)) throw new DefinitionException(sprintf('Skill [%s] is not allowed by Spark [%s].', $reference, $sparkDefinition->name));
            $skillDefinitions[] = $skillDefinition;
        }
        $contextReferences = [...$sparkDefinition->contexts, ...$vesselDefinition->contexts];
        foreach ($skillDefinitions as $skillDefinition) {
            $contextReferences = [...$contextReferences, ...$skillDefinition->contexts];
        }
        $contextReferences = array_values(array_unique($contextReferences));
        if ($contexts !== null) {
            $allowedContexts = $contextReferences;
            $contextReferences = [];
            foreach ($contexts as $reference) {
                if (!is_string($reference)) throw new DefinitionException('Context overrides must be string references.');
                $scroll = $this->codex->resolve($reference);
                if (!$scroll instanceof Context || !$this->allows($allowedContexts, 'context', $scroll->name)) {
                    throw new DefinitionException(sprintf('Context [%s] is not allowed by Vessel [%s].', $reference, $vesselDefinition->name));
                }
                foreach ($allowedContexts as $allowedContext) {
                    if ($allowedContext === $scroll->name || $allowedContext === 'context://' . $scroll->name) {
                        $contextReferences[] = $allowedContext;
                        break;
                    }
                }
            }
            $contextReferences = array_values(array_unique($contextReferences));
        }
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
            $model ?? $vesselDefinition->model ?? $sparkDefinition->model ?? $providerDefinition->model,
            $resolvedTools,
            $vesselDefinition->limits,
            [
                'vessel' => $vesselDefinition->name,
                'spark' => $sparkDefinition->name,
                'provider' => $providerDefinition,
                'contexts' => $contextReferences,
                'skills' => $selectedSkills,
                'tools' => array_map(static fn ($tool): string => $tool->name, $resolvedTools),
                'toolExecution' => ['root' => getcwd() ?: '.', 'allowedContexts' => $contextReferences],
            ],
            $approval ?? new DenyConsequentialTools(),
        );
    }

    private function allows(array $references, string $type, string $name): bool
    {
        foreach ($references as $reference) {
            if ($reference === $name || $reference === sprintf('%s://%s', $type, $name)) return true;
        }
        return false;
    }
}
