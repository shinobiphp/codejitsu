<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Tests\Console;

use Codejitsu\Ai\Console\AiTui;
use Codejitsu\Ai\Console\ConversationIO;
use Codejitsu\Ai\Definitions\DefinitionLoader;
use Codejitsu\Ai\Prompt\PromptAssembler;
use Codejitsu\Ai\Prompt\SkillRenderer;
use Codejitsu\Ai\Runtime\AiRequest;
use Codejitsu\Ai\Runtime\AiResponse;
use Codejitsu\Ai\Runtime\AiRuntime;
use Codejitsu\Ai\Runtime\RuntimeRegistry;
use Codejitsu\Ai\Scrolls\Model;
use Codejitsu\Ai\Scrolls\Provider;
use Codejitsu\Ai\Scrolls\Spark;
use Codejitsu\Ai\Scrolls\Tool;
use Codejitsu\Ai\Scrolls\Toolset;
use Codejitsu\Ai\Scrolls\Vessel;
use Codejitsu\Ai\Tools\ToolCall;
use Codejitsu\Ai\Tools\ToolPolicy;
use Codejitsu\Ai\Vessels\VesselRunner;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\TypeDefinition;
use Codejitsu\Scrolls\TypeRegistry;
use PHPUnit\Framework\TestCase;

final class AiTuiTest extends TestCase
{
    public function testItSelectsRuntimeResourcesBeforeStartingTheSession(): void
    {
        $types = TypeRegistry::builtins();
        foreach ([
            ['spark','sparks','spark','spark://',Spark::class],
            ['vessel','vessels','vessel','vessel://',Vessel::class],
            ['provider','providers','provider','provider://',Provider::class],
            ['model','models','model','model://',Model::class],
            ['tool','tools','tool','tool://',Tool::class],
            ['toolset','toolsets','toolset','toolset://',Toolset::class],
        ] as $type) $types->register(new TypeDefinition(...$type));
        $codex = (new ScrollCodex(types: $types))->load(dirname(__DIR__, 2) . '/resources', 'codejitsu-ai');
        $runtime = new TuiRuntime();
        $runtimes = new RuntimeRegistry();
        $runtimes->register('neuron', $runtime);
        $loader = new DefinitionLoader($codex);
        $runner = new VesselRunner($loader, new PromptAssembler(new SkillRenderer()), new ToolPolicy($loader), $runtimes, $codex);
        $io = new TuiIO(
            selections: ['code', 'product-designer', 'ollama/local', 'product-strategy'],
            answers: ['additional_context=Prioritize%20funding.', 'qwen:latest', 'context://contexts/architecture', '', '/exit'],
        );

        self::assertSame(0, (new AiTui($runner, $codex))->run($io), implode('', $io->output));
        self::assertStringContainsString('vessel=code', implode('', $io->output));
        self::assertStringContainsString('spark=product-designer', implode('', $io->output));
        self::assertStringContainsString('provider=ollama/local', implode('', $io->output));
        self::assertStringContainsString('skills=product-strategy', implode('', $io->output));
        self::assertContains('security', $io->choices[1]);
    }
}

final class TuiIO implements ConversationIO
{
    public array $output = [];
    public array $choices = [];
    public function __construct(private array $selections, private array $answers) {}
    public function ask(string $prompt): string { return (string) array_shift($this->answers); }
    public function select(string $prompt, array $choices): string
    {
        $this->choices[] = $choices;
        $answer = (string) array_shift($this->selections);
        TestCase::assertContains($answer, $choices);
        return $answer;
    }
    public function write(string $message): void { $this->output[] = $message; }
    public function confirmTool(ToolCall $call): bool { return false; }
}

final class TuiRuntime implements AiRuntime
{
    public function run(AiRequest $request): AiResponse { return new AiResponse('done'); }
}
