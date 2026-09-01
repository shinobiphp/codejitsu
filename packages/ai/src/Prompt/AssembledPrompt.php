<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Prompt;

final readonly class AssembledPrompt
{
    public function __construct(
        public string $instructions,
        public string $context,
        public string $userInput,
        public array $skills,
        public array $contexts,
        public array $toolsets,
    ) {}
}
