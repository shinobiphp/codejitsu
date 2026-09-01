<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Definitions;

final readonly class SparkDefinition
{
    public function __construct(
        public string $name,
        public string $instructions,
        public ?string $description,
        public ?string $model,
        public array $skills,
        public array $allowedSkills,
        public array $contexts,
        public array $capabilities,
        public array $tools,
        public array $allowedTools,
        public array $toolsets,
        public array $allowedToolsets,
        public array $metadata,
    ) {}

    public static function fromArray(array $data): self
    {
        $name = DefinitionData::string($data, 'name', 'Spark', true);
        $owner = sprintf('Spark [%s]', $name);
        $skills = DefinitionData::strings($data, 'skills', $owner);
        $tools = DefinitionData::strings($data, 'tools', $owner);
        $toolsets = DefinitionData::strings($data, 'toolsets', $owner);
        return new self(
            $name,
            DefinitionData::string($data, 'instructions', $owner, true),
            DefinitionData::string($data, 'description', $owner),
            DefinitionData::string($data, 'model', $owner),
            $skills,
            DefinitionData::allowed($skills, DefinitionData::strings($data, 'allowedSkills', $owner), 'Skill', $owner),
            DefinitionData::strings($data, 'contexts', $owner),
            DefinitionData::strings($data, 'capabilities', $owner),
            $tools,
            DefinitionData::allowed($tools, DefinitionData::strings($data, 'allowedTools', $owner), 'Tool', $owner),
            $toolsets,
            DefinitionData::allowed($toolsets, DefinitionData::strings($data, 'allowedToolsets', $owner), 'Toolset', $owner),
            is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }
}
