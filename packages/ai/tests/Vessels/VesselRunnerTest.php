<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Tests\Vessels;

use Codejitsu\Ai\Definitions\DefinitionLoader;
use Codejitsu\Ai\Prompt\PromptAssembler;
use Codejitsu\Ai\Prompt\SkillRenderer;
use Codejitsu\Ai\Runtime\AiRequest;
use Codejitsu\Ai\Runtime\AiResponse;
use Codejitsu\Ai\Runtime\AiRuntime;
use Codejitsu\Ai\Runtime\RuntimeRegistry;
use Codejitsu\Ai\Scrolls\Spark;
use Codejitsu\Ai\Scrolls\Tool;
use Codejitsu\Ai\Scrolls\Toolset;
use Codejitsu\Ai\Scrolls\Vessel;
use Codejitsu\Ai\Scrolls\Provider;
use Codejitsu\Ai\Scrolls\Model;
use Codejitsu\Ai\Tools\ToolPolicy;
use Codejitsu\Ai\Tools\DenyConsequentialTools;
use Codejitsu\Ai\Vessels\VesselRunner;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\TypeDefinition;
use Codejitsu\Scrolls\TypeRegistry;
use Codejitsu\Scrolls\Types\Context;
use Codejitsu\Scrolls\Types\Skill;
use PHPUnit\Framework\TestCase;

final class VesselRunnerTest extends TestCase
{
    public function testItResolvesAndRunsTheCompleteVesselPrompt(): void
    {
        [$runner, $runtime] = $this->runner();
        $session = $runner->start('workbench', skills: ['skill://review'], skillInputs: ['review' => ['subject' => 'Codejitsu']]);
        $response = $session->send('What changes?');

        self::assertSame('done', $response->text);
        self::assertStringContainsString('You are the architect.', $runtime->request->instructions);
        self::assertStringContainsString('Review Codejitsu.', $runtime->request->instructions);
        self::assertStringContainsString('Current architecture.', $runtime->request->instructions);
        self::assertSame('vessel-model', $runtime->request->model);
        self::assertSame('openai', $runtime->request->metadata['provider']->adapter);
        self::assertSame(['context://state'], $runtime->request->metadata['toolExecution']['allowedContexts']);
        self::assertSame(['skill://review'], $runtime->request->metadata['skills']);
        self::assertInstanceOf(DenyConsequentialTools::class, $runtime->request->approval);
        self::assertSame('architect', $session->metadata()['spark']);
    }

    public function testItRejectsAnIneligibleSparkOverride(): void
    {
        [$runner] = $this->runner();
        $this->expectExceptionMessage('is not allowed by Vessel');
        $runner->start('workbench', spark: 'spark://other');
    }

    public function testBundledLocalVesselsResolveTheirCompleteResourceGraph(): void
    {
        $types=TypeRegistry::builtins();
        foreach ([
            ['spark','sparks','spark','spark://',Spark::class],
            ['vessel','vessels','vessel','vessel://',Vessel::class],
            ['provider','providers','provider','provider://',Provider::class],
            ['model','models','model','model://',Model::class],
            ['tool','tools','tool','tool://',Tool::class],
            ['toolset','toolsets','toolset','toolset://',Toolset::class],
        ] as $type) $types->register(new TypeDefinition(...$type));
        $codex=(new ScrollCodex(types:$types))->load(dirname(__DIR__,2).'/resources','codejitsu-ai');
        $runtime=new RunnerRuntime();
        $runtimes=new RuntimeRegistry();
        $runtimes->register('neuron',$runtime);
        $runner=new VesselRunner(
            new DefinitionLoader($codex),
            new PromptAssembler(new SkillRenderer()),
            new ToolPolicy(new DefinitionLoader($codex)),
            $runtimes,
            $codex,
        );

        foreach ([
            'code'=>['engineer','codejitsu:latest'],
            'cognition'=>['scribe','codejitsu:latest'],
        ] as $vessel=>[$spark,$model]) {
            $session=$runner->start($vessel);
            $session->send('Inspect the project.');
            self::assertSame($spark,$runtime->request->metadata['spark']);
            self::assertSame('ollama',$runtime->request->metadata['provider']->adapter);
            self::assertSame($model,$runtime->request->model);
            self::assertNotEmpty($runtime->request->tools);
            self::assertStringContainsString('Codejitsu AI — Architecture',$runtime->request->instructions);
        }
    }

    private function runner(): array
    {
        $types = TypeRegistry::builtins();
        foreach ([
            ['spark', 'sparks', 'spark', 'spark://', Spark::class],
            ['vessel', 'vessels', 'vessel', 'vessel://', Vessel::class],
            ['tool', 'tools', 'tool', 'tool://', Tool::class],
            ['toolset', 'toolsets', 'toolset', 'toolset://', Toolset::class],
            ['provider', 'providers', 'provider', 'provider://', Provider::class],
        ] as $type) $types->register(new TypeDefinition(...$type));
        $codex = new ScrollCodex(types: $types);
        $codex->registerScroll((new Spark())->hydrate(['name' => 'architect', 'version' => '1.0.0', 'instructions' => 'You are the architect.', 'allowedSkills' => ['skill://review'], 'contexts' => ['context://state']]));
        $codex->registerScroll((new Spark())->hydrate(['name' => 'other', 'version' => '1.0.0', 'instructions' => 'Other.']));
        $codex->registerScroll((new Provider())->hydrate(['name'=>'openai/default','version'=>'1.0.0','adapter'=>'openai','model'=>'provider-model','credentials'=>['apiKey'=>'env://OPENAI_API_KEY']]));
        $codex->registerScroll((new Vessel())->hydrate(['name' => 'workbench', 'version' => '1.0.0', 'runtime' => 'fake', 'spark' => 'spark://architect', 'provider'=>'provider://openai/default', 'model' => 'vessel-model']));
        $codex->registerScroll((new Skill())->hydrate(['name' => 'review', 'version' => '1.0.0', 'prompt' => 'Review {{subject}}.', 'inputs' => ['subject' => ['type' => 'string', 'required' => true]]]));
        $codex->registerScroll((new Context())->hydrate(['name' => 'state', 'version' => '1.0.0', 'content' => 'Current architecture.']));
        $loader = new DefinitionLoader($codex);
        $runtime = new RunnerRuntime();
        $runtimes = new RuntimeRegistry();
        $runtimes->register('fake', $runtime);
        return [new VesselRunner($loader, new PromptAssembler(new SkillRenderer()), new ToolPolicy($loader), $runtimes, $codex), $runtime];
    }
}

final class RunnerRuntime implements AiRuntime
{
    public AiRequest $request;
    public function run(AiRequest $request): AiResponse { $this->request = $request; return new AiResponse('done'); }
}
