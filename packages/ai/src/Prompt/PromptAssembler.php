<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Prompt;

use Codejitsu\Ai\Definitions\SkillDefinition;
use Codejitsu\Ai\Definitions\SparkDefinition;
use Codejitsu\Ai\Definitions\ToolsetDefinition;
use Codejitsu\Ai\Exceptions\DefinitionException;

final readonly class PromptAssembler
{
    public function __construct(private SkillRenderer $renderer) {}

    /** @param list<SkillDefinition> $skills @param array<string,array> $skillInputs @param array<string,string> $contexts @param list<ToolsetDefinition> $toolsets */
    public function assemble(SparkDefinition $spark, array $skills, array $skillInputs, array $contexts, array $toolsets, string $userInput): AssembledPrompt
    {
        if (trim($userInput) === '') throw new DefinitionException('User input cannot be empty.');
        $instructions = [rtrim($spark->instructions)];
        $skillNames = [];
        foreach ($skills as $skill) {
            if (!$skill instanceof SkillDefinition) throw new DefinitionException('Prompt skills must be Skill definitions.');
            $instructions[] = rtrim($this->renderer->render($skill, $skillInputs[$skill->name] ?? []));
            $skillNames[] = $skill->name;
        }
        $toolsetNames = [];
        foreach ($toolsets as $toolset) {
            if (!$toolset instanceof ToolsetDefinition) throw new DefinitionException('Prompt toolsets must be Toolset definitions.');
            if ($toolset->guidelines !== null) $instructions[] = rtrim($toolset->guidelines);
            $toolsetNames[] = $toolset->name;
        }
        foreach ($contexts as $reference => $content) {
            if (!is_string($reference) || !is_string($content)) throw new DefinitionException('Prompt contexts must map references to content.');
        }
        return new AssembledPrompt(
            implode("\n\n", array_filter($instructions, fn (string $value): bool => $value !== '')),
            implode("\n\n", array_map('rtrim', array_values($contexts))),
            $userInput,
            $skillNames,
            array_keys($contexts),
            $toolsetNames,
        );
    }
}
