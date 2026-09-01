<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Tests\Neuron;

use Codejitsu\Ai\Neuron\ProviderFactory;
use NeuronAI\Providers\OpenAI\OpenAI;
use PHPUnit\Framework\TestCase;

final class ProviderFactoryTest extends TestCase
{
    public function testItBuildsOpenAiFromEnvironmentReferences(): void
    {
        putenv('CODEJITSU_TEST_AI_KEY=secret');
        try { self::assertInstanceOf(OpenAI::class, (new ProviderFactory())->make(['name' => 'openai', 'keyEnv' => 'CODEJITSU_TEST_AI_KEY'], 'gpt-test')); }
        finally { putenv('CODEJITSU_TEST_AI_KEY'); }
    }

    public function testItRejectsUnknownProvidersAndMissingEnvironment(): void
    {
        foreach ([[['name' => 'other'], 'model'], [['name' => 'openai', 'keyEnv' => 'MISSING_CODEJITSU_KEY'], 'model']] as [$config, $model]) {
            try { (new ProviderFactory())->make($config, $model); self::fail('Invalid provider accepted.'); }
            catch (\RuntimeException) { self::assertTrue(true); }
        }
    }
}
