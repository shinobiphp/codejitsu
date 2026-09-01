<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Vessels;

use Codejitsu\Ai\Runtime\AiRequest;
use Codejitsu\Ai\Runtime\AiResponse;
use Codejitsu\Ai\Runtime\AiRuntime;
use Codejitsu\Ai\Runtime\ChatMessage;
use Codejitsu\Ai\Tools\ToolApproval;
use InvalidArgumentException;

final class VesselSession
{
    private array $messages = [];
    public function __construct(private readonly AiRuntime $runtime, private readonly string $instructions, private readonly ?string $model, private readonly array $tools, private readonly array $limits, private readonly array $metadata, private readonly ?ToolApproval $approval = null) {}
    public function send(string $prompt): AiResponse
    {
        if (trim($prompt) === '') throw new InvalidArgumentException('Prompt cannot be empty.');
        $this->messages[] = new ChatMessage('user', $prompt);
        $response = $this->runtime->run(new AiRequest($this->instructions, $this->messages, $this->model, $this->tools, $this->limits, $this->metadata, $this->approval));
        $this->messages[] = new ChatMessage('assistant', $response->text);
        return $response;
    }
    public function clear(): void { $this->messages = []; }
    public function messages(): array { return $this->messages; }
    public function metadata(): array { return $this->metadata; }
}
