<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls\Types;

use Codejitsu\Contracts\Invokable;
use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use Codejitsu\ExecutionContext;
use Codejitsu\Scrolls\Scroll;
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
        return $this->execute(new ExecutionContext(
            count($args) === 1 ? $args[0] : $args,
            $this->codex,
        ));
    }

    public function execute(ExecutionContext $context): mixed
    {
        $target = $this->target();

        return $target($context);
    }

    public function hydrate(array $data): static
    {
        if (isset($data['target']) && !is_string($data['target']) && !$data['target'] instanceof Invokable && !is_callable($data['target'])) {
            throw new InvalidArgumentException('Capability target must be callable or a callable string.');
        }

        return parent::hydrate($data);
    }
}
