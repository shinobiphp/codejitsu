<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Definitions;

use Codejitsu\Ai\Exceptions\DefinitionException;
use Codejitsu\Ai\Scrolls\Spark;
use Codejitsu\Ai\Scrolls\Tool;
use Codejitsu\Ai\Scrolls\Toolset;
use Codejitsu\Ai\Scrolls\Vessel;
use Codejitsu\Ai\Scrolls\Provider;
use Codejitsu\Ai\Scrolls\Model;
use Codejitsu\Scrolls\Scroll;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Types\Skill;

final readonly class DefinitionLoader
{
    public function __construct(private ScrollCodex $codex) {}

    public function spark(string $reference): SparkDefinition { return SparkDefinition::fromArray($this->data($reference, 'spark', Spark::class)); }
    public function vessel(string $reference): VesselDefinition { return VesselDefinition::fromArray($this->data($reference, 'vessel', Vessel::class)); }
    public function provider(string $reference): ProviderDefinition { return ProviderDefinition::fromArray($this->data($reference, 'provider', Provider::class)); }
    public function model(string $reference): ModelDefinition { return ModelDefinition::fromArray($this->data($reference,'model',Model::class)); }
    public function skill(string $reference): SkillDefinition { return SkillDefinition::fromArray($this->data($reference, 'skill', Skill::class)); }
    public function tool(string $reference): ToolDefinition { return ToolDefinition::fromArray($this->data($reference, 'tool', Tool::class)); }
    public function toolset(string $reference): ToolsetDefinition { return ToolsetDefinition::fromArray($this->data($reference, 'toolset', Toolset::class)); }

    private function data(string $reference, string $type, string $class): array
    {
        $reference = trim($reference);
        if ($reference === '') throw new DefinitionException(ucfirst($type) . ' reference cannot be empty.');
        try {
            $scroll = $this->codex->resolveTyped($type, $reference);
        } catch (\Throwable $exception) {
            throw new DefinitionException(sprintf('%s [%s] was not found.', ucfirst($type), $reference), 0, $exception);
        }
        if (!$scroll instanceof $class || !$scroll instanceof Scroll) {
            throw new DefinitionException(sprintf('[%s] is not a %s Scroll.', $reference, ucfirst($type)));
        }
        return $scroll->toArray();
    }
}
