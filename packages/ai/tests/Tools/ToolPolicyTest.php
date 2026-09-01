<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Tests\Tools;

use Codejitsu\Ai\Definitions\DefinitionLoader;
use Codejitsu\Ai\Definitions\SparkDefinition;
use Codejitsu\Ai\Definitions\VesselDefinition;
use Codejitsu\Ai\Exceptions\DefinitionException;
use Codejitsu\Ai\Scrolls\Tool;
use Codejitsu\Ai\Scrolls\Toolset;
use Codejitsu\Ai\Tools\ToolPolicy;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\TypeDefinition;
use Codejitsu\Scrolls\TypeRegistry;
use PHPUnit\Framework\TestCase;

final class ToolPolicyTest extends TestCase
{
    public function testVesselNarrowsSparkAndToolsetsExpandInOrder(): void
    {
        $policy = $this->policy();
        $spark = SparkDefinition::fromArray([
            'name' => 'agent', 'instructions' => 'Act.',
            'tools' => ['tool://show'], 'allowedTools' => ['tool://show', 'tool://write'],
            'toolsets' => ['toolset://context'],
            'capabilities' => ['capability://show', 'capability://write'],
        ]);
        $vessel = VesselDefinition::fromArray([
            'name' => 'safe', 'runtime' => 'fake', 'spark' => 'spark://agent',
            'tools' => ['tool://show'], 'toolsets' => ['toolset://context'],
            'capabilities' => ['capability://show'],
        ]);

        $resolved = $policy->resolve($spark, $vessel, [], []);
        self::assertSame(['show'], array_map(fn ($tool) => $tool->name, $resolved));

        $this->expectException(DefinitionException::class);
        $policy->resolve($spark, $vessel, ['tool://write'], []);
    }

    private function policy(): ToolPolicy
    {
        $types = TypeRegistry::builtins();
        $types->register(new TypeDefinition('tool', 'tools', 'tool', 'tool://', Tool::class));
        $types->register(new TypeDefinition('toolset', 'toolsets', 'toolset', 'toolset://', Toolset::class));
        $codex = new ScrollCodex(types: $types);
        foreach ([
            ['name' => 'show', 'capability' => 'capability://show'],
            ['name' => 'write', 'capability' => 'capability://write'],
        ] as $item) {
            $codex->registerScroll((new Tool())->hydrate($item + ['version' => '1.0.0', 'description' => 'Tool.', 'inputSchema' => ['type' => 'object']]));
        }
        $codex->registerScroll((new Toolset())->hydrate(['name' => 'context', 'version' => '1.0.0', 'tools' => ['tool://show', 'tool://write']]));
        return new ToolPolicy(new DefinitionLoader($codex));
    }
}
