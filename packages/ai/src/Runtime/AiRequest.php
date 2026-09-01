<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Runtime;
use Codejitsu\Ai\Tools\ToolApproval;
final readonly class AiRequest
{
    public function __construct(public string $instructions, public array $messages, public ?string $model = null, public array $tools = [], public array $limits = [], public array $metadata = [], public ?ToolApproval $approval = null) {}
}
