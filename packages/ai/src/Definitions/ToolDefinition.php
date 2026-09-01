<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Definitions;

use Codejitsu\Ai\Exceptions\DefinitionException;

final readonly class ToolDefinition
{
    public function __construct(
        public string $name,
        public string $description,
        public string $capability,
        public array $inputSchema,
        public bool $consequential,
        public int $maxRuns,
        public array $metadata,
    ) {}

    public static function fromArray(array $data): self
    {
        $name = DefinitionData::string($data, 'name', 'Tool', true);
        $owner = sprintf('Tool [%s]', $name);
        $schema = $data['inputSchema'] ?? null;
        if (!is_array($schema) || $schema === []) throw new DefinitionException($owner . ' inputSchema must be a non-empty map.');
        $consequential = $data['consequential'] ?? true;
        if (!is_bool($consequential)) throw new DefinitionException($owner . ' consequential must be boolean.');
        $maxRuns = $data['maxRuns'] ?? 1;
        if (!is_int($maxRuns) || $maxRuns < 1) throw new DefinitionException($owner . ' maxRuns must be an integer of at least 1.');
        return new self(
            $name,
            DefinitionData::string($data, 'description', $owner, true),
            DefinitionData::string($data, 'capability', $owner, true),
            $schema,
            $consequential,
            $maxRuns,
            is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }
}
