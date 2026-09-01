<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Runtime;
use InvalidArgumentException;
final readonly class ChatMessage
{
    public function __construct(public string $role, public string $content)
    {
        if (!in_array($role, ['user', 'assistant', 'tool'], true)) throw new InvalidArgumentException('Invalid chat message role.');
        if (trim($content) === '') throw new InvalidArgumentException('Chat message content cannot be empty.');
    }
}
