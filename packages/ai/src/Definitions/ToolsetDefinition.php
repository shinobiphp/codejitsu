<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Definitions;

use Codejitsu\Ai\Exceptions\DefinitionException;

final readonly class ToolsetDefinition
{
    public function __construct(
        public string $name,
        public array $tools,
        public ?string $description,
        public ?string $guidelines,
        public array $metadata,
    ) {}

    public static function fromArray(array $data): self
    {
        $name = DefinitionData::string($data, 'name', 'Toolset', true);
        $owner = sprintf('Toolset [%s]', $name);
        $raw = $data['tools'] ?? [];
        if (is_array($raw) && count($raw) !== count(array_unique($raw, SORT_REGULAR))) {
            throw new DefinitionException($owner . ' contains a duplicate Tool reference.');
        }
        $tools = DefinitionData::strings($data, 'tools', $owner);
        if ($tools === []) throw new DefinitionException($owner . ' tools cannot be empty.');
        return new self(
            $name,
            $tools,
            DefinitionData::string($data, 'description', $owner),
            DefinitionData::string($data, 'guidelines', $owner),
            is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }
}
