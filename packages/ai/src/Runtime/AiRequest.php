<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Runtime;
final readonly class AiRequest
{
    public function __construct(public string $instructions, public array $messages, public ?string $model = null, public array $tools = [], public array $limits = [], public array $metadata = []) {}
}
