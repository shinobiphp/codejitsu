<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Tests\Neuron;

use Codejitsu\Ai\Neuron\ProviderFactory;
use Codejitsu\Ai\Definitions\ProviderDefinition;
use NeuronAI\Providers\OpenAI\OpenAI;
use NeuronAI\Providers\Ollama\Ollama;
use PHPUnit\Framework\TestCase;

final class ProviderFactoryTest extends TestCase
{
    public function testItBuildsOpenAiFromEnvironmentReferences(): void
    {
        putenv('CODEJITSU_TEST_AI_KEY=secret');
        $definition = ProviderDefinition::fromArray(['name'=>'test','adapter'=>'openai','model'=>'gpt-test','credentials'=>['apiKey'=>'env://CODEJITSU_TEST_AI_KEY']]);
        try { self::assertInstanceOf(OpenAI::class, (new ProviderFactory())->make($definition)); }
        finally { putenv('CODEJITSU_TEST_AI_KEY'); }
    }

    public function testItRejectsUnknownProvidersAndMissingEnvironment(): void
    {
        foreach ([
            ['name'=>'other','adapter'=>'other','model'=>'model','credentials'=>['apiKey'=>'env://ANY_KEY']],
            ['name'=>'missing','adapter'=>'openai','model'=>'model','credentials'=>['apiKey'=>'env://MISSING_CODEJITSU_KEY']],
        ] as $config) {
            try { (new ProviderFactory())->make(ProviderDefinition::fromArray($config)); self::fail('Invalid provider accepted.'); }
            catch (\RuntimeException) { self::assertTrue(true); }
        }
    }

    public function testConfigurationTestNeverMakesAProviderRequest(): void
    {
        putenv('CODEJITSU_TEST_AI_KEY=present');
        $definition = ProviderDefinition::fromArray(['name'=>'test','adapter'=>'openai','model'=>'gpt-test','credentials'=>['apiKey'=>'env://CODEJITSU_TEST_AI_KEY']]);
        try { self::assertSame(['adapter'=>'openai','model'=>'gpt-test','credentials'=>['apiKey'=>'available']], (new ProviderFactory())->test($definition)); }
        finally { putenv('CODEJITSU_TEST_AI_KEY'); }
    }

    public function testItBuildsOllamaWithoutCredentialsAndMapsOptions(): void
    {
        $definition = ProviderDefinition::fromArray([
            'name'=>'ollama/local',
            'adapter'=>'ollama',
            'model'=>'codejitsu:latest',
            'options'=>[
                'url'=>'http://ollama.test:11434/api',
                'parameters'=>['temperature'=>0.2],
            ],
        ]);

        $provider=(new ProviderFactory())->make($definition, 'override:latest');

        self::assertInstanceOf(Ollama::class,$provider);
        self::assertSame('http://ollama.test:11434/api',$this->property($provider,'url'));
        self::assertSame('override:latest',$this->property($provider,'model'));
        self::assertSame(['temperature'=>0.2],$this->property($provider,'parameters'));
        self::assertSame(
            ['adapter'=>'ollama','model'=>'codejitsu:latest','credentials'=>[]],
            (new ProviderFactory())->test($definition),
        );
    }

    private function property(object $object,string $name):mixed
    {
        return (new \ReflectionProperty($object,$name))->getValue($object);
    }
}
