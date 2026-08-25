<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls\Types;

use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use Codejitsu\ExecutionContext;
use Codejitsu\Scrolls\Scroll;
use InvalidArgumentException;
use LogicException;

final class Command extends Scroll
{
    public const ScrollTypes TYPE = ScrollTypes::COMMAND;
    public const array TAGS = ['command'];

    public function description(): string
    {
        return (string) ($this->attributes['description'] ?? '');
    }

    public function usage(): string
    {
        return (string) ($this->attributes['usage'] ?? $this->name);
    }

    /** @return array<string, array<string, mixed>> */
    public function commands(): array
    {
        $commands = $this->attributes['commands'] ?? [];
        if (!is_array($commands)) {
            throw new LogicException(sprintf('Command [%s] commands must be an array.', $this->name));
        }

        return $commands;
    }

    public function isNamespace(): bool
    {
        return $this->commands() !== [];
    }

    public function child(string $name): ?self
    {
        $name = strtolower(trim($name));
        $definition = $this->commands()[$name] ?? null;
        if (!is_array($definition)) {
            return null;
        }

        $qualified = $this->name . ':' . $name;
        $usage = $definition['usage'] ?? null;
        if (is_string($usage) && $usage !== '') {
            $usage = preg_replace(
                '/^' . preg_quote($name, '/') . '(?=\s|$)/',
                $qualified,
                $usage,
                1,
            ) ?? $usage;
        } else {
            $usage = $qualified;
        }

        $child = new self();
        if ($this->codex !== null) {
            $child->bind($this->codex);
        }

        $child->hydrate([
            ...$definition,
            'name' => $qualified,
            'usage' => $usage,
        ]);

        return $child;
    }

    public function capability(): ?string
    {
        $reference = $this->attributes['capability'] ?? null;
        return is_string($reference) && trim($reference) !== '' ? trim($reference) : null;
    }

    public function schema(): ?string
    {
        $reference = $this->attributes['schema'] ?? null;
        return is_string($reference) && trim($reference) !== '' ? trim($reference) : null;
    }

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
            return $this->ref($capability)(new ExecutionContext($payload, $this->codex), $this->codex);
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

    public function hydrate(array $data): static
    {
        if (isset($data['description']) && !is_string($data['description'])) {
            throw new InvalidArgumentException('Command description must be a string.');
        }

        if (isset($data['usage']) && !is_string($data['usage'])) {
            throw new InvalidArgumentException('Command usage must be a string.');
        }

        if (isset($data['commands']) && !is_array($data['commands'])) {
            throw new InvalidArgumentException('Command commands must be an array.');
        }

        foreach (['capability', 'schema', 'target'] as $reference) {
            if (isset($data[$reference]) && !is_string($data[$reference]) && !is_callable($data[$reference])) {
                throw new InvalidArgumentException(sprintf('Command %s must be a URI or callable.', $reference));
            }
        }

        return parent::hydrate($data);
    }
}
