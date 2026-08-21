<?php

declare(strict_types=1);

namespace Codejitsu\Apps;

use Codejitsu\Contracts\App;
use Codejitsu\Contracts\Intent;
use Codejitsu\Contracts\Middleware;
use Codejitsu\IO\CliIntent;
use Codejitsu\IO\Translators\Cli as CliTranslator;
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
    ) {
        $this->pipeline = new Pipeline();
    }

    public function use(Middleware|Closure ...$middleware): self
    {
        $this->pipeline->pipe(...$middleware);
        return $this;
    }

    public function run(mixed ...$args): int
    {
        $rawArgv = $args[0] ?? $_SERVER['argv'] ?? [];
        $intent = CliTranslator::translate($rawArgv);
        $commands = $this->commands();

        if ($intent->action === '' || in_array($intent->action, ['help', '--help', '-h', 'list'], true)) {
            $this->renderUsage($commands);
            return 0;
        }

        $command = $commands[$intent->action] ?? null;
        if (!$command instanceof Command) {
            fwrite(STDERR, sprintf("Unknown command [%s].%s", $intent->action, PHP_EOL));
            $this->renderUsage($commands, STDERR);
            return 1;
        }

        $result = $this->pipeline->send($intent, function (Intent $i) use ($command): mixed {
            return $i instanceof CliIntent
                ? $command->execute(...$i->payload)
                : $command->execute($i);
        });

        return is_int($result) ? $result : 0;
    }

    /** @return array<string, Command> */
    private function commands(): array
    {
        $commands = [];

        foreach ($this->kernelInstance->scrolls->all(true) as $scroll) {
            if ($scroll instanceof Command) {
                $commands[$scroll->name] = $scroll;
            }
        }

        return $commands;
    }

    /** @param resource $stream */
    private function renderUsage(array $commands, mixed $stream = STDOUT): void
    {
        fwrite($stream, "Codejitsu\n\n");
        fwrite($stream, "Usage: ./codejitsu <command> [arguments] [options]\n\n");

        if ($commands === []) {
            fwrite($stream, "No command Scrolls are currently available.\n");
            return;
        }

        fwrite($stream, "Available commands:\n");
        foreach ($commands as $command) {
            fwrite($stream, sprintf("  %-20s %s%s", $command->name, $command->description(), PHP_EOL));
        }
    }
}