<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Tools;

use Codejitsu\Ai\Definitions\DefinitionLoader;
use Codejitsu\Ai\Definitions\SparkDefinition;
use Codejitsu\Ai\Definitions\VesselDefinition;
use Codejitsu\Ai\Exceptions\DefinitionException;

final readonly class ToolPolicy
{
    public function __construct(private DefinitionLoader $loader) {}

    public function resolve(SparkDefinition $spark, VesselDefinition $vessel, array $requestedTools, array $requestedToolsets): array
    {
        $allowedTools = $this->intersection($spark->allowedTools, $vessel->tools);
        $allowedToolsets = $this->intersection($spark->allowedToolsets, $vessel->toolsets);
        $selectedTools = [...$spark->tools, ...$requestedTools];
        $selectedToolsets = [...$spark->toolsets, ...$requestedToolsets];
        foreach ($selectedTools as $reference) $this->requireAllowed($reference, $allowedTools, 'Tool');
        foreach ($selectedToolsets as $reference) $this->requireAllowed($reference, $allowedToolsets, 'Toolset');

        $expanded = $selectedTools;
        foreach (array_values(array_unique($selectedToolsets)) as $reference) {
            foreach ($this->loader->toolset($reference)->tools as $tool) $expanded[] = $tool;
        }
        $effectiveCapabilities = $this->intersection($spark->capabilities, $vessel->capabilities);
        $result = [];
        foreach (array_values(array_unique($expanded)) as $reference) {
            if (!in_array($reference, $allowedTools, true)) continue;
            $tool = $this->loader->tool($reference);
            if (!in_array($tool->capability, $effectiveCapabilities, true)) {
                throw new DefinitionException(sprintf('Tool [%s] Capability [%s] is outside the effective policy.', $tool->name, $tool->capability));
            }
            $result[] = $tool;
        }
        return $result;
    }

    private function intersection(array $spark, array $vessel): array
    {
        return $vessel === [] ? $spark : array_values(array_intersect($spark, $vessel));
    }

    private function requireAllowed(string $reference, array $allowed, string $kind): void
    {
        if (!in_array($reference, $allowed, true)) throw new DefinitionException(sprintf('%s [%s] is outside the effective policy.', $kind, $reference));
    }
}
