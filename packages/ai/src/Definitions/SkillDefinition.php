<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Definitions;

final readonly class SkillDefinition
{
    public function __construct(
        public string $name,
        public string $prompt,
        public ?string $description,
        public array $inputs,
        public array $contexts,
        public array $capabilities,
        public array $metadata,
    ) {}

    public static function fromArray(array $data): self
    {
        $name = DefinitionData::string($data, 'name', 'Skill', true);
        $owner = sprintf('Skill [%s]', $name);
        $inputs = $data['inputs'] ?? [];
        $requires = $data['requires'] ?? [];
        if (!is_array($inputs) || !is_array($requires)) throw new \Codejitsu\Ai\Exceptions\DefinitionException($owner . ' inputs and requires must be maps.');
        return new self(
            $name,
            DefinitionData::string($data, 'prompt', $owner, true),
            DefinitionData::string($data, 'description', $owner),
            $inputs,
            DefinitionData::strings($requires, 'contexts', $owner),
            DefinitionData::strings($requires, 'capabilities', $owner),
            is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }
}
