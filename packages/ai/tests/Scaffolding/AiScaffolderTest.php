<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Tests\Scaffolding;

use Codejitsu\Ai\Scaffolding\AiScaffolder;
use Codejitsu\Ai\Scrolls\Spark;
use Codejitsu\Ai\Scrolls\Tool;
use Codejitsu\Ai\Scrolls\Toolset;
use Codejitsu\Ai\Scrolls\Vessel;
use Codejitsu\Ai\Scrolls\Provider;
use Codejitsu\Scrolls\TypeDefinition;
use Codejitsu\Scrolls\TypeRegistry;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AiScaffolderTest extends TestCase
{
    private string $root;
    private AiScaffolder $scaffolder;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/codejitsu-ai-make-' . bin2hex(random_bytes(5));
        mkdir($this->root, 0755, true);
        $types = TypeRegistry::builtins();
        foreach ([
            ['spark', 'sparks', 'spark', 'spark://', Spark::class], ['vessel', 'vessels', 'vessel', 'vessel://', Vessel::class],
            ['tool', 'tools', 'tool', 'tool://', Tool::class], ['toolset', 'toolsets', 'toolset', 'toolset://', Toolset::class],
            ['provider', 'providers', 'provider', 'provider://', Provider::class],
        ] as $type) $types->register(new TypeDefinition(...$type));
        $this->scaffolder = new AiScaffolder($this->root, $types);
    }

    protected function tearDown(): void { $this->remove($this->root); }

    public function testItCreatesMinimalValidResourcesAtRegisteredPaths(): void
    {
        self::assertSame($this->root . '/scrolls/sparks/architect.spark', $this->scaffolder->spark('architect', 'Review architecture.'));
        self::assertSame($this->root . '/scrolls/vessels/workbench.vessel', $this->scaffolder->vessel('workbench', 'neuron', 'spark://architect', 'provider://openai'));
        self::assertSame($this->root . '/scrolls/skills/review.skill', $this->scaffolder->skill('review', 'Review {{subject}}.', ['subject' => ['type' => 'string', 'required' => true]]));
        self::assertSame($this->root . '/scrolls/tools/search.tool', $this->scaffolder->tool('search', 'Search context.', 'capability://search', ['type' => 'object']));
        self::assertSame($this->root . '/scrolls/toolsets/context.toolset', $this->scaffolder->toolset('context', ['tool://search'], 'Search first.'));
        self::assertSame($this->root . '/scrolls/providers/openai.provider', $this->scaffolder->provider('openai', 'openai', 'gpt-5', 'env://OPENAI_API_KEY'));
        self::assertStringContainsString('instructions: Review architecture.', file_get_contents($this->root . '/scrolls/sparks/architect.spark'));
        self::assertFileDoesNotExist($this->root . '/composer.lock');
    }

    public function testItRejectsTraversalAndOverwrite(): void
    {
        foreach (['../bad', '/bad'] as $name) {
            try { $this->scaffolder->spark($name, 'x'); self::fail('Invalid name accepted.'); } catch (RuntimeException) { self::assertTrue(true); }
        }
        $this->scaffolder->spark('same', 'x');
        $this->expectException(RuntimeException::class);
        $this->scaffolder->spark('same', 'y');
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) return;
        foreach (scandir($path) ?: [] as $entry) if (!in_array($entry, ['.', '..'], true)) { $child = $path . '/' . $entry; is_dir($child) ? $this->remove($child) : unlink($child); }
        rmdir($path);
    }
}
