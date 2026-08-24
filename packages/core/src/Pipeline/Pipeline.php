<?php

declare(strict_types=1);

namespace Codejitsu\Pipeline;

use Closure;
use RuntimeException;

final class Pipeline
{
    /**
     * @var list<callable>
     */
    private array $pipes = [];

    /**
     * @var mixed
     */
    private mixed $passable = null;

    private ?Closure $destination = null;

    public function send(
        mixed $passable,
        ?callable $destination = null,
    ): mixed {
        $this->passable = $passable;

        if ($destination !== null) {
            return $this->then($destination);
        }

        return $this;
    }

    public function through(
        callable ...$pipes,
    ): static {
        foreach ($pipes as $pipe) {
            $this->pipe($pipe);
        }

        return $this;
    }

    public function pipe(
        callable $pipe,
    ): static {
        $this->pipes[] = $pipe;

        return $this;
    }

    public function then(
        callable $destination,
    ): mixed {
        $this->destination = Closure::fromCallable(
            $destination,
        );

        $next = $this->destination;

        foreach (
            array_reverse($this->pipes)
            as $pipe
        ) {
            $next = $this->wrap(
                $pipe,
                $next,
            );
        }

        return $next($this->passable);
    }

    public function process(): mixed
    {
        if ($this->destination === null) {
            throw new RuntimeException(
                'Pipeline destination has not been configured.',
            );
        }

        return $this->then(
            $this->destination,
        );
    }

    public function clear(): static
    {
        $this->pipes = [];
        $this->passable = null;
        $this->destination = null;

        return $this;
    }

    private function wrap(
        callable $pipe,
        Closure $next,
    ): Closure {
        return static function (
            mixed $passable,
        ) use ($pipe, $next): mixed {
            return $pipe(
                $passable,
                $next,
            );
        };
    }
}