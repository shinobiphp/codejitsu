<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Runtime;
final readonly class AiResponse
{
    public function __construct(public string $text, public array $usage = [], public array $metadata = []) {}
}
