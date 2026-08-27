<?php

declare(strict_types=1);

namespace Codejitsu\Apps;

use Codejitsu\Console\Drivers\Symfony;
use Codejitsu\Contracts\App;
use Codejitsu\Contracts\Console\Driver;
use Codejitsu\Contracts\Middleware;
use Codejitsu\Kernel\Kernel;
use Codejitsu\Pipeline\Pipeline;
use Codejitsu\Scrolls\Types\Command;
use Closure;

final class Cli implements App
{
    private Pipeline $pipeline;

    public Kernel $kernel {
        get => $this->kernelInstance;
    }

    public function __construct(
        private readonly Kernel $kernelInstance,
        private Driver $driver = new Symfony(),
    ) {
        $this->pipeline = new Pipeline();
    }

    public function withDriver(Driver $driver): self
    {
        $clone = clone $this;
        $clone->driver = $driver;

        return $clone;
    }

    public function use(Middleware|Closure ...$middleware): self
    {
        $this->pipeline->pipe(...$middleware);
        return $this;
    }

    public function run(mixed ...$args): int
    {
        $argv = $args[0] ?? $_SERVER['argv'] ?? [];
        if (!is_array($argv)) {
            $argv = [];
        }

        return $this->driver->run(array_values($argv), $this->commands());
    }

    /** @return list<Command> */
    private function commands(): array
    {
        return array_values(array_filter(
            $this->kernelInstance->scrolls->all(true),
            static fn (mixed $scroll): bool => $scroll instanceof Command,
        ));
    }
}
