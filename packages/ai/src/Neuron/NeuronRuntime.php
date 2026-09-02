<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Neuron;

use Codejitsu\Ai\Runtime\AiRequest;
use Codejitsu\Ai\Runtime\AiResponse;
use Codejitsu\Ai\Runtime\AiRuntime;
use Codejitsu\Ai\Tools\DenyConsequentialTools;
use Codejitsu\Ai\Tools\ToolRegistry;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;

final readonly class NeuronRuntime implements AiRuntime
{
    private \Closure $providers;

    public function __construct(private ToolRegistry $tools, ?\Closure $providers = null)
    {
        $this->providers = $providers ?? static fn ($configuration, ?string $model) => (new ProviderFactory())->make($configuration, $model);
    }

    public function run(AiRequest $request): AiResponse
    {
        $provider = ($this->providers)($request->metadata['provider'] ?? [], $request->model);
        $agent = Agent::make()->setAiProvider($provider)->setInstructions($request->instructions);
        $approval = $request->approval ?? new DenyConsequentialTools();
        $tools = $this->tools->withExecutionMetadata(is_array($request->metadata['toolExecution'] ?? null) ? $request->metadata['toolExecution'] : []);
        foreach ($request->tools as $tool) $agent->addTool(new NeuronToolAdapter($tool, $tools, $approval));
        $messages = array_map(static fn ($message) => match ($message->role) {
            'user' => new UserMessage($message->content),
            'assistant' => new AssistantMessage($message->content),
            default => throw new \RuntimeException(sprintf('Neuron runtime cannot import message role [%s].', $message->role)),
        }, $request->messages);
        $message = $agent->chat($messages)->getMessage();
        return new AiResponse((string) $message->getContent(), metadata: ['runtime' => 'neuron']);
    }
}
