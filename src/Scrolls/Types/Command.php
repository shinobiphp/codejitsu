<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls\Types;

use Codejitsu\ExecutionContext;
use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use Codejitsu\Scrolls\Scroll;
use LogicException;

final class Command extends Scroll
{
    public const ScrollTypes TYPE = ScrollTypes::COMMAND;

    public function target(): callable
    {
        $target = $this->attributes['target'] ?? null;

        if (is_callable($target)) {
            return $target;
        }

        throw new LogicException(sprintf('Command [%s] has no executable target.', $this->name));
    }

    public function execute(mixed ...$args): mixed
    {
        $payload = count($args) === 1 && is_array($args[0])
            ? $args[0]
            : $args;

        if (($schema = $this->schema()) !== null) {
            $this->ref($schema)($payload);
        }

        if (($capability = $this->capability()) !== null) {
            return $this->ref($capability)(new ExecutionContext($payload, $this->codex));
        }

        if ($this->isNamespace()) {
            $childName = $payload[0] ?? null;
            if (!is_string($childName)) {
                throw new LogicException(sprintf('Command namespace [%s] requires a subcommand.', $this->name));
            }

            $child = $this->child($childName);
            if ($child === null) {
                throw new LogicException(sprintf(
                    'Unknown subcommand [%s] for command namespace [%s].',
                    $childName,
                    $this->name,
                ));
            }

            return $child->execute(...array_slice($payload, 1));
        }

        return ($this->target())(...$args);
    }

    public function references(): array
    {
        $references = [];

        if (($schema = $this->schema()) !== null) {
            $references['schema'] = $schema;
        }

        if (($capability = $this->capability()) !== null) {
            $references['capability'] = $capability;
        }

        return $references;
    }
}
