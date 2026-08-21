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
                ? $this->dispatch($command, $i->payload)
                : $command->execute($i);
        });

        return is_int($result) ? $result : 0;
    }

    /** @param list<mixed> $payload */
    private function dispatch(Command $command, array $payload): mixed
    {
        if (!$command->isNamespace()) {
            return $command->execute(...$payload);
        }

        $subcommand = $payload[0] ?? null;
        if ($subcommand === null || in_array($subcommand, ['--help', '-h', 'help'], true)) {
            $this->renderNamespaceUsage($command);
            return 0;
        }

        if (!is_string($subcommand)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid subcommand for [%s].',
                $command->name,
            ));
        }

        $child = $command->child($subcommand);
        if (!$child instanceof Command) {
            fwrite(STDERR, sprintf(
                "Unknown subcommand [%s] for [%s].%s",
                $subcommand,
                $command->name,
                PHP_EOL,
            ));
            $this->renderNamespaceUsage($command, STDERR);
            return 1;
        }

        return $this->dispatch($child, array_slice($payload, 1));
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

    /** @param resource $stream */
    private function renderNamespaceUsage(Command $command, mixed $stream = STDOUT): void
    {
        fwrite($stream, sprintf(
            "Usage: ./codejitsu %s <subcommand> [arguments] [options]%s%s",
            $command->name,
            PHP_EOL,
            PHP_EOL,
        ));

        foreach ($command->commands() as $name => $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $description = is_string($definition['description'] ?? null)
                ? $definition['description']
                : '';

            fwrite($stream, sprintf("  %-20s %s%s", $name, $description, PHP_EOL));
        }
    }
}
