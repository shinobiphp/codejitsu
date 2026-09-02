<?php declare(strict_types=1); namespace Codejitsu\Ai\Console; use Codejitsu\Ai\Tools\ToolCall;
interface ConversationIO { public function ask(string $prompt):string; public function select(string $prompt,array $choices):string; public function write(string $message):void; public function confirmTool(ToolCall $call):bool; }
