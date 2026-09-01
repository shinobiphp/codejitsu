<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Tests\Definitions;

use Codejitsu\Ai\Definitions\DefinitionLoader;
use Codejitsu\Ai\Definitions\SparkDefinition;
use Codejitsu\Ai\Definitions\ToolDefinition;
use Codejitsu\Ai\Definitions\ToolsetDefinition;
use Codejitsu\Ai\Definitions\VesselDefinition;
use Codejitsu\Ai\Exceptions\DefinitionException;
use Codejitsu\Ai\Scrolls\Spark;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\TypeDefinition;
use Codejitsu\Scrolls\TypeRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DefinitionLoaderTest extends TestCase
{
    public function testSparkDefaultsMustBeAllowed(): void
    {
        $this->expectException(DefinitionException::class);
        $this->expectExceptionMessage('default Skill [skill://review] is not allowed');

        SparkDefinition::fromArray([
            'name' => 'architect',
            'instructions' => 'Review code.',
            'skills' => ['skill://review'],
            'allowedSkills' => ['skill://write'],
        ]);
    }

    public function testItNormalizesValidDefinitions(): void
    {
        $spark = SparkDefinition::fromArray([
            'name' => 'architect',
            'instructions' => 'Review code.',
            'skills' => ['skill://review'],
            'contexts' => ['context://architecture'],
            'tools' => ['tool://context.show'],
        ]);
        $vessel = VesselDefinition::fromArray([
            'name' => 'workbench',
            'runtime' => 'neuron',
            'spark' => 'spark://architect',
            'provider' => ['name' => 'openai', 'keyEnv' => 'OPENAI_API_KEY'],
        ]);

        self::assertSame(['skill://review'], $spark->allowedSkills);
        self::assertSame(['tool://context.show'], $spark->allowedTools);
        self::assertSame(['spark://architect'], $vessel->allowedSparks);
        self::assertSame('openai', $vessel->provider['name']);
    }

    #[DataProvider('invalidTools')]
    public function testItRejectsInvalidTools(array $data, string $message): void
    {
        $this->expectException(DefinitionException::class);
        $this->expectExceptionMessage($message);
        ToolDefinition::fromArray($data);
    }

    public static function invalidTools(): iterable
    {
        yield 'missing capability' => [[
            'name' => 'search', 'description' => 'Search.', 'inputSchema' => ['type' => 'object'],
        ], 'capability'];
        yield 'invalid max runs' => [[
            'name' => 'search', 'description' => 'Search.', 'capability' => 'capability://search',
            'inputSchema' => ['type' => 'object'], 'maxRuns' => 0,
        ], 'maxRuns'];
        yield 'invalid consequential flag' => [[
            'name' => 'search', 'description' => 'Search.', 'capability' => 'capability://search',
            'inputSchema' => ['type' => 'object'], 'consequential' => 'no',
        ], 'consequential'];
    }

    public function testToolsetsRejectDuplicateTools(): void
    {
        $this->expectException(DefinitionException::class);
        $this->expectExceptionMessage('duplicate Tool');
        ToolsetDefinition::fromArray([
            'name' => 'context',
            'tools' => ['tool://context.show', 'tool://context.show'],
        ]);
    }

    public function testLoaderResolvesAUniqueTypedDefinition(): void
    {
        $types = TypeRegistry::builtins();
        $types->register(new TypeDefinition('spark', 'sparks', 'spark', 'spark://', Spark::class));
        $codex = new ScrollCodex(types: $types);
        $codex->registerScroll((new Spark())->hydrate([
            'name' => 'architect', 'version' => '1.0.0', 'instructions' => 'Review code.',
        ]), 'project');

        $definition = (new DefinitionLoader($codex))->spark('architect');

        self::assertSame('architect', $definition->name);
        self::assertSame('Review code.', $definition->instructions);
    }
}
