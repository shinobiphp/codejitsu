<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls;

use Codejitsu\Enums\Scrolls\Types;
use LogicException;

final readonly class TypedScrollProxy
{
    public function __construct(
        private ScrollCodex $codex,
        private Types $type,
    ) {}

    public function __get(string $name): mixed
    {
        return $this->codex->resolveTyped(
            $this->type,
            $name,
        );
    }

    public function __call(
        string $name,
        array $args,
    ): mixed {
        return $this->codex->invoke(
            $this->type,
            $name,
            ...$args,
        );
    }

    public function __invoke(
        string $name,
        mixed ...$args,
    ): mixed {
        return $this->codex->invoke(
            $this->type,
            $name,
            ...$args,
        );
    }

    public function __isset(string $name): bool
    {
        if (!$this->codex->has($name)) {
            return false;
        }

        try {
            $this->codex->resolveTyped(
                $this->type,
                $name,
            );

            return true;
        } catch (LogicException) {
            return false;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }
}