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
        public array $sparkPolicy,
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
        $hasAllowed = array_key_exists('allowedSparks', $data);
        $hasPolicy = array_key_exists('sparkPolicy', $data);
        if ($hasAllowed && $hasPolicy) throw new \Codejitsu\Ai\Exceptions\DefinitionException($owner . ' cannot define both allowedSparks and sparkPolicy.');
        $allowed = $hasAllowed ? DefinitionData::allowed([$spark], DefinitionData::strings($data, 'allowedSparks', $owner), 'Spark', $owner) : [];
        $policy = $hasPolicy ? self::policy($data['sparkPolicy'], $owner) : ($hasAllowed ? [] : [['effect' => 'allow', 'match' => '*']]);
        if ($policy !== [] && !self::evaluate($policy, $spark)) throw new \Codejitsu\Ai\Exceptions\DefinitionException(sprintf('%s default Spark [%s] is not allowed by sparkPolicy.', $owner, $spark));
        $provider = DefinitionData::string($data, 'provider', $owner, true);
        if (!str_starts_with($provider, 'provider://')) throw new \Codejitsu\Ai\Exceptions\DefinitionException($owner . ' provider must be a provider:// reference.');
        $memory = DefinitionData::string($data, 'memory', $owner) ?? 'session';
        if ($memory !== 'session') throw new \Codejitsu\Ai\Exceptions\DefinitionException($owner . ' memory must be [session].');
        return new self(
            $name,
            DefinitionData::string($data, 'runtime', $owner, true),
            $spark,
            $allowed,
            $policy,
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

    public function allowsSpark(string $reference): bool
    {
        if ($this->allowedSparks !== []) {
            foreach ($this->allowedSparks as $allowed) if (self::matches($allowed, $reference)) return true;
            return false;
        }
        return self::evaluate($this->sparkPolicy, $reference);
    }

    private static function policy(mixed $rules, string $owner): array
    {
        if (!is_array($rules) || !array_is_list($rules) || $rules === []) throw new \Codejitsu\Ai\Exceptions\DefinitionException($owner . ' sparkPolicy must be a non-empty list.');
        $result = [];
        foreach ($rules as $rule) {
            if (!is_array($rule) || !in_array($rule['effect'] ?? null, ['allow', 'deny'], true) || !is_string($rule['match'] ?? null) || trim($rule['match']) === '') {
                throw new \Codejitsu\Ai\Exceptions\DefinitionException($owner . ' sparkPolicy rules require effect [allow|deny] and a non-empty match.');
            }
            $result[] = ['effect' => $rule['effect'], 'match' => trim($rule['match'])];
        }
        return $result;
    }

    private static function evaluate(array $rules, string $reference): bool
    {
        $allowed = false;
        foreach ($rules as $rule) if ($rule['match'] === '*' || self::matches($rule['match'], $reference)) $allowed = $rule['effect'] === 'allow';
        return $allowed;
    }

    private static function matches(string $left, string $right): bool
    {
        $normalize = static fn (string $value): string => str_contains($value, '://') ? substr($value, strpos($value, '://') + 3) : $value;
        return $normalize($left) === $normalize($right);
    }
}
