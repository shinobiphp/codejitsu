<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls\Types;

use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use Codejitsu\Scrolls\Scroll;
use InvalidArgumentException;
use LogicException;

final class Command extends Scroll
{
    public const ScrollTypes TYPE = ScrollTypes::CAPABILITY;
    public const array TAGS = ['command'];

    public function description(): string
    {
        return (string) ($this->attributes['description'] ?? '');
    }

    public function usage(): string
    {
        return (string) ($this->attributes['usage'] ?? $this->name);
    }

    public function target(): callable
    {
        $target = $this->attributes['target'] ?? null;

        if (is_callable($target)) {
            return $target;
        }

        if (is_string($target) && is_callable($target)) {
            return $target;
        }

        throw new LogicException(sprintf('Command [%s] has no executable target.', $this->name));
    }

    public function execute(mixed ...$args): mixed
    {
        return ($this->target())(...$args);
    }

    public function hydrate(array $data): static
    {
        if (isset($data['description']) && !is_string($data['description'])) {
            throw new InvalidArgumentException('Command description must be a string.');
        }

        if (isset($data['usage']) && !is_string($data['usage'])) {
            throw new InvalidArgumentException('Command usage must be a string.');
        }

        if (isset($data['target']) && !is_callable($data['target']) && !is_string($data['target'])) {
            throw new InvalidArgumentException('Command target must be callable or a callable string.');
        }

        return parent::hydrate($data);
    }
}