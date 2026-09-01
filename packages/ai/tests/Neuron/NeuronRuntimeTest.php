<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Tests\Neuron;

use Codejitsu\Ai\Neuron\NeuronRuntime;
use Codejitsu\Ai\Runtime\AiRequest;
use Codejitsu\Ai\Runtime\ChatMessage;
use Codejitsu\Ai\Tools\DenyConsequentialTools;
use Codejitsu\Ai\Tools\ToolRegistry;
use Codejitsu\Scrolls\ScrollCodex;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Providers\OpenAI\OpenAI;
use PHPUnit\Framework\TestCase;

final class NeuronRuntimeTest extends TestCase
{
    public function testItAdaptsRequestAndResponseWithoutANetworkCall(): void
    {
        $provider = new FakeOpenAI();
        $runtime = new NeuronRuntime(new ToolRegistry(new ScrollCodex()), fn () => $provider);
        $response = $runtime->run(new AiRequest(
            'Be precise.', [new ChatMessage('user', 'Hello')], 'gpt-test', [], [],
            ['provider' => ['name' => 'openai', 'keyEnv' => 'IGNORED']], new DenyConsequentialTools(),
        ));
        self::assertSame('answer', $response->text);
        self::assertSame('Be precise.', $provider->system);
        self::assertSame('Hello', $provider->messages[0]->getContent());
    }
}

final class FakeOpenAI extends OpenAI
{
    public ?string $system = null;
    public array $messages = [];
    public function __construct() { parent::__construct('fake', 'fake'); }
    public function systemPrompt(?string $prompt): \NeuronAI\Providers\AIProviderInterface { $this->system = $prompt; return $this; }
    public function chat(Message ...$messages): Message { $this->messages = $messages; return new AssistantMessage('answer'); }
}
