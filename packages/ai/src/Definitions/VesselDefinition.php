<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Definitions;

final readonly class VesselDefinition
{
    public function __construct(
        public string $name,
        public string $runtime,
        public string $spark,
        public array $allowedSparks,
        public ?string $description,
        public string $provider,
        public ?string $model,
        public array $contexts,
        public array $capabilities,
        public array $tools,
        public array $toolsets,
        public array $limits,
        public string $memory,
        public array $metadata,
    ) {}

    public static function fromArray(array $data): self
    {
        $name = DefinitionData::string($data, 'name', 'Vessel', true);
        $owner = sprintf('Vessel [%s]', $name);
        $spark = DefinitionData::string($data, 'spark', $owner, true);
        $allowed = DefinitionData::strings($data, 'allowedSparks', $owner);
        $allowed = DefinitionData::allowed([$spark], $allowed, 'Spark', $owner);
        $provider = DefinitionData::string($data, 'provider', $owner, true);
        if (!str_starts_with($provider, 'provider://')) throw new \Codejitsu\Ai\Exceptions\DefinitionException($owner . ' provider must be a provider:// reference.');
        $memory = DefinitionData::string($data, 'memory', $owner) ?? 'session';
        if ($memory !== 'session') throw new \Codejitsu\Ai\Exceptions\DefinitionException($owner . ' memory must be [session].');
        return new self(
            $name,
            DefinitionData::string($data, 'runtime', $owner, true),
            $spark,
            $allowed,
            DefinitionData::string($data, 'description', $owner),
            $provider,
            DefinitionData::string($data, 'model', $owner),
            DefinitionData::strings($data, 'contexts', $owner),
            DefinitionData::strings($data, 'capabilities', $owner),
            DefinitionData::strings($data, 'tools', $owner),
            DefinitionData::strings($data, 'toolsets', $owner),
            is_array($data['limits'] ?? null) ? $data['limits'] : [],
            $memory,
            is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }
}
