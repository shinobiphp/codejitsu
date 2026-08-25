<?php

declare(strict_types=1);

namespace Codejitsu\Apps;

use Codejitsu\Console\UsageRenderer;
use Codejitsu\Contracts\App;
use Codejitsu\Contracts\Intent;
use Codejitsu\Contracts\Middleware;
use Codejitsu\IO\CliIntent;
use Codejitsu\IO\Translators\Cli as CliTranslator;
use Codejitsu\Kernel\Kernel;
use Codejitsu\Pipeline\Pipeline;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Types\Command;
use Closure;
use OutOfBoundsException;

final class Cli implements App
{
    private Pipeline $pipeline;

    public Kernel $kernel {
        get => $this->kernelInstance;
    }

    public function __construct(
        private readonly Kernel $kernelInstance,
        private readonly UsageRenderer $usageRenderer = new UsageRenderer(),
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
        $argv = $args[0] ?? $_SERVER['argv'] ?? [];
        $intent = CliTranslator::translate($argv);
        $commands = $this->commands();

        if ($intent->action === '' || in_array($intent->action, ['help', '--help', '-h', 'list'], true)) {
            $this->renderUsage($commands);
            return 0;
        }

        $command = $this->resolve($intent->action);
        if (!$command instanceof Command) {
            fwrite(STDERR, sprintf("Unknown command [%s].%s", $intent->action, PHP_EOL));
            $this->renderUsage($commands, STDERR);
            return 1;
        }

        if ($this->isHelpRequest($argv)) {
            if ($command->isNamespace()) {
                $this->renderNamespaceUsage($command);
                return 0;
            }

            $this->renderCommandUsage($command);
            return 0;
        }

        if ($command->isNamespace() && $intent->payload === []) {
            $this->renderNamespaceUsage($command);
            return 0;
        }

        $result = $this->pipeline->send($intent, function (Intent $i) use ($command): mixed {
            return $i instanceof CliIntent
                ? $command->execute(...$i->payload)
                : $command->execute($i);
        });

        if (is_string($result) && $result !== '') {
            echo $result;
        }

        return is_int($result) ? $result : 0;
    }

    private function resolve(string $name): ?Command
    {
        $parts = array_values(array_filter(
            explode(':', strtolower(trim($name))),
            static fn (string $part): bool => $part !== '',
        ));
        if ($parts === []) {
            return null;
        }

        $codex = $this->kernelInstance->scrolls;
        $command = $this->resolveCommand($codex, array_shift($parts));
        if (!$command instanceof Command) {
            return null;
        }

        foreach ($parts as $part) {
            if (!$command->isNamespace()) {
                return null;
            }

            $command = $command->child($part);
            if (!$command instanceof Command) {
                return null;
            }
        }

        return $command;
    }

    private function resolveCommand(ScrollCodex $codex, string $name): ?Command
    {
        try {
            $command = $codex->resolve('command://' . trim($name, '/'));
        } catch (OutOfBoundsException) {
            return null;
        }

        return $command instanceof Command ? $command : null;
    }

    /** @return array<string, Command> */
    private function commands(): array
    {
        return array_reduce(
            $this->kernelInstance->scrolls->all(true),
            static function (array $commands, mixed $scroll): array {
                if ($scroll instanceof Command) {
                    $commands[$scroll->name] = $scroll;
                }
                return $commands;
            },
            [],
        );
    }

    private function renderUsage(array $commands, mixed $stream = STDOUT): void
    {
        $output = $this->usageRenderer->render($commands);

        if ($stream === STDOUT) {
            echo $output;
            return;
        }

        fwrite($stream, $output);
    }

    private function renderNamespaceUsage(Command $command, mixed $stream = STDOUT): void
    {
        $output = $this->usageRenderer->renderNamespace($command);

        if ($stream === STDOUT) {
            echo $output;
            return;
        }

        fwrite($stream, $output);
    }

    private function renderCommandUsage(Command $command, mixed $stream = STDOUT): void
    {
        $output = '<comment>Codejitsu</comment>' . PHP_EOL . PHP_EOL;
        $output .= '<info>Usage:</info>' . PHP_EOL;
        $output .= '  ' . $command->usage() . PHP_EOL . PHP_EOL;

        if ($command->description() !== '') {
            $output .= '<info>Description:</info>' . PHP_EOL;
            $output .= '  ' . $command->description() . PHP_EOL;
        }

        $output .= PHP_EOL . 'Run "codejitsu <command> --help" for more information.' . PHP_EOL;
        $output = (new \Symfony\Component\Console\Formatter\OutputFormatter(true))->format($output);

        if ($stream === STDOUT) {
            echo $output;
            return;
        }

        fwrite($stream, $output);
    }

    /** @param mixed $argv */
    private function isHelpRequest(mixed $argv): bool
    {
        if (!is_array($argv)) {
            return false;
        }

        return in_array('--help', $argv, true) || in_array('-h', $argv, true);
    }
}
