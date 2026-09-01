<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Runtime;
interface AiRuntime { public function run(AiRequest $request): AiResponse; }
