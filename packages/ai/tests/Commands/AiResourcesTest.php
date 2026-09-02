<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Tests\Commands;

use Codejitsu\Ai\Commands\AiResources;
use Codejitsu\Ai\Commands\MakeAi;
use Codejitsu\Ai\Scrolls\Spark;
use Codejitsu\ExecutionContext;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\TypeDefinition;
use Codejitsu\Scrolls\TypeRegistry;
use Codejitsu\Scrolls\Types\Skill;
use Codejitsu\Scrolls\Types\Command;
use PHPUnit\Framework\TestCase;

final class AiResourcesTest extends TestCase
{
    public function testMakerAndResourceCommandsShareDefinitions(): void
    {
        $root = sys_get_temp_dir() . '/codejitsu-ai-command-' . bin2hex(random_bytes(5)); mkdir($root);
        $cwd = getcwd(); chdir($root);
        try {
            $types = TypeRegistry::builtins(); $types->register(new TypeDefinition('spark', 'sparks', 'spark', 'spark://', Spark::class));
            $codex = new ScrollCodex(types: $types);
            self::assertStringContainsString('architect.spark', MakeAi::spark(new ExecutionContext(['architect', 'Review architecture.'], $codex)));
            $codex->load($root . '/scrolls', 'project');
            self::assertStringContainsString('architect', AiResources::sparkList(new ExecutionContext([], $codex)));
            self::assertStringContainsString('Review architecture.', AiResources::sparkShow(new ExecutionContext(['architect'], $codex)));
        } finally { chdir($cwd); $this->remove($root); }
    }

    public function testSkillRenderParsesNamedInputs(): void
    {
        $codex = new ScrollCodex();
        $codex->registerScroll((new Skill())->hydrate(['name' => 'review', 'version' => '1.0.0', 'prompt' => 'Review {{subject}}.', 'inputs' => ['subject' => ['type' => 'string', 'required' => true]]]));
        self::assertSame("Review Codejitsu.\n", AiResources::skillRender(new ExecutionContext(['review', '--input=subject=Codejitsu'], $codex)));
    }

    public function testPackageShipsMakerAndResourceCommandScrolls(): void
    {
        $types = TypeRegistry::builtins();
        foreach ([['spark','sparks','spark','spark://',Spark::class],['vessel','vessels','vessel','vessel://',\Codejitsu\Ai\Scrolls\Vessel::class],['tool','tools','tool','tool://',\Codejitsu\Ai\Scrolls\Tool::class],['toolset','toolsets','toolset','toolset://',\Codejitsu\Ai\Scrolls\Toolset::class]] as $type) $types->register(new TypeDefinition(...$type));
        $codex=(new ScrollCodex(types:$types))->load(dirname(__DIR__,2).'/resources','ai');
        $make=$codex->resolve('command://make@ai#1.0.0');
        self::assertInstanceOf(Command::class,$make);
        self::assertInstanceOf(Command::class,$make->child('spark'));
        self::assertInstanceOf(Command::class,$codex->resolve('command://spark@ai#1.0.0'));
    }

    public function testPackageCommandsBindExecutionContextHandlersAsCapabilities(): void
    {
        $codex = (new ScrollCodex())->load(dirname(__DIR__, 2) . '/resources', 'ai');

        foreach (['ai', 'make', 'model', 'provider', 'skill', 'spark', 'tool', 'toolset', 'vessel'] as $name) {
            $command = $codex->resolve(sprintf('command://%s@ai#1.0.0', $name));
            self::assertInstanceOf(Command::class, $command);

            foreach (array_keys($command->commands()) as $childName) {
                $child = $command->child((string) $childName);
                self::assertInstanceOf(Command::class, $child);
                self::assertNotNull($child->capability(), sprintf('%s:%s must bind through a capability.', $name, $child->name));
            }
        }
    }

    public function testAiRunDeclaresEveryRuntimeOptionForTheConsoleDriver(): void
    {
        $codex = (new ScrollCodex())->load(dirname(__DIR__, 2) . '/resources', 'ai');
        $command = $codex->resolve('command://ai@ai#1.0.0');
        self::assertInstanceOf(Command::class, $command);
        self::assertSame(
            ['spark', 'provider', 'model', 'skill', 'input', 'approve-tool'],
            $command->child('run')?->usageOptions(),
        );
    }

    private function remove(string $path): void { if (!is_dir($path)) return; foreach (scandir($path) ?: [] as $e) if (!in_array($e, ['.','..'], true)) { $c=$path.'/'.$e; is_dir($c)?$this->remove($c):unlink($c); } rmdir($path); }
}
