<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls\Types;

use Codejitsu\Contracts\Invokable;
use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use Codejitsu\ExecutionContext;
use Codejitsu\Scrolls\Scroll;
use Codejitsu\Substrate\Detector;
use Codejitsu\Substrate\Php;
use InvalidArgumentException;
use LogicException;

final class Capability extends Scroll
{
    public const ScrollTypes TYPE = ScrollTypes::CAPABILITY;

    public function target(): Invokable|callable|string
    {
        $target = $this->attributes['target'] ?? null;

        if (is_string($target) && trim($target) !== '') {
            return trim($target);
        }

        if ($target instanceof Invokable || is_callable($target)) {
            return $target;
        }

        throw new LogicException(sprintf(
            'Capability [%s] has no executable target.',
            $this->name,
        ));
    }

    public function __invoke(mixed ...$args): mixed
    {
        $context = count($args) === 1 && $args[0] instanceof ExecutionContext
            ? $args[0]
            : new ExecutionContext($args, $this->codex);

        return $this->execute($context);
    }

    public function execute(ExecutionContext $context): mixed
    {
        if (isset($this->attributes['source'])) {
            $source = $this->attributes['source'];
            if (!is_string($source) || trim($source) === '') {
                throw new InvalidArgumentException('Capability source must be a non-empty string.');
            }

            $name = strtolower(trim((string) ($this->attributes['substrate'] ?? 'auto')));
            $name = $name === 'auto' ? (new Detector())->detect($source) : $name;

            return match ($name) {
                'php' => (new Php())->execute($source, $context),
                default => throw new LogicException(sprintf('Unsupported substrate [%s].', $name)),
            };
        }

        return ($this->target())($context);
    }

    public function hydrate(array $data): static
    {
        if (isset($data['target']) && !is_string($data['target']) && !$data['target'] instanceof Invokable && !is_callable($data['target'])) {
            throw new InvalidArgumentException('Capability target must be callable or a callable string.');
        }

        if (isset($data['source']) && !is_string($data['source'])) {
            throw new InvalidArgumentException('Capability source must be a string.');
        }

        if (isset($data['substrate']) && !is_string($data['substrate'])) {
            throw new InvalidArgumentException('Capability substrate must be a string.');
        }

        return parent::hydrate($data);
    }
}
