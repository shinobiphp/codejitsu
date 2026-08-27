<?php

declare(strict_types=1);

namespace Codejitsu\Console\Drivers;

use Codejitsu\Contracts\Console\Driver;
use Codejitsu\Scrolls\Types\Command;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;

final class Symfony implements Driver
{
    public function run(array $argv, iterable $commands, callable $execute): int
    {
        $application = new Application('Codejitsu');
        $application->setAutoExit(false);
        $registered = [];
        $scrolls = [];

        foreach ($commands as $command) {
            if ($command instanceof Command) {
                $scrolls[$command->name] = $command;
                $this->register($application, $command, $registered, $execute);
            }
        }

        if ($this->rendersRoot($argv)) {
            $this->renderRoot($scrolls);

            return 0;
        }

        if (($namespace = $this->namespaceFor($argv, $scrolls)) !== null) {
            $this->renderNamespace($namespace);

            return 0;
        }

        $output = new BufferedOutput();
        $status = $application->run(new ArgvInput($argv), $output);
        echo $output->fetch();

        return $status;
    }

    /** @param array<string, bool> $registered */
    private function register(Application $application, Command $command, array &$registered, callable $execute): void
    {
        if (isset($registered[$command->name])) {
            return;
        }

        if ($command->isNamespace()) {
            foreach ($command->commands() as $name => $definition) {
                if (is_array($definition) && ($child = $command->child((string) $name)) instanceof Command) {
                    $this->register($application, $child, $registered, $execute);
                }
            }

            $console = new SymfonyCommand($command->name);
            $console->setDescription($command->description());
            $console->setHelp($this->namespaceHelp($command));
            $console->setCode(static function (InputInterface $input, $output) use ($command): int {
                $output->writeln('<info>Available commands:</info>');
                foreach ($command->commands() as $name => $definition) {
                    if (is_array($definition)) {
                        $output->writeln(sprintf('  %s:%s', $command->name, $name));
                    }
                }

                return 0;
            });
        } else {
            $console = new SymfonyCommand($command->name);
            $console->setDescription(trim($command->usage() . ' — ' . $command->description(), ' —'));
            $console->setHelp($command->usage());
            $this->defineArguments($console, $command);
            $console->setCode(function (InputInterface $input, $output) use ($command, $execute): int {
                $payload = [];
                foreach ($command->usageArguments() as $argument) {
                    $payload[] = $input->getArgument($argument);
                }

                foreach ($command->usageOptions() as $option) {
                    $value = $input->getOption($option);
                    if ($value !== null && $value !== false) {
                        $payload[] = is_array($value)
                            ? sprintf('--%s=%s', $option, implode(',', $value))
                            : sprintf('--%s=%s', $option, $value);
                    }
                }

                $result = $execute($command, $payload);
                if (is_string($result) && $result !== '') {
                    $output->write($result);
                }

                return is_int($result) ? $result : 0;
            });
        }

        $application->addCommand($console);
        $registered[$command->name] = true;
    }

    /** @param list<string> $argv */
    private function rendersRoot(array $argv): bool
    {
        return count($argv) === 1
            || in_array($argv[1] ?? null, ['--help', '-h', 'help'], true);
    }

    /** @param array<string, Command> $scrolls */
    private function namespaceFor(array $argv, array $scrolls): ?Command
    {
        $name = $argv[1] ?? null;
        if (!is_string($name) || !isset($scrolls[$name]) || !$scrolls[$name]->isNamespace()) {
            return null;
        }

        $isHelp = count($argv) === 3 && in_array($argv[2], ['--help', '-h'], true);
        if (count($argv) === 2 || $isHelp) {
            return $scrolls[$name];
        }

        return null;
    }

    /** @param array<string, Command> $scrolls */
    private function renderRoot(array $scrolls): void
    {
        $output = "Codejitsu\n\nAvailable commands:\n";

        foreach ($scrolls as $command) {
            $output .= sprintf(" %s\n", ucfirst($command->name));
            if (!$command->isNamespace()) {
                $output .= sprintf("  %-14s %s\n", $command->name, $command->description());
                continue;
            }

            foreach ($command->commands() as $name => $definition) {
                if (!is_array($definition)) {
                    continue;
                }

                $child = $command->child((string) $name);
                if ($child === null) {
                    continue;
                }

                $output .= sprintf("  %-14s %s\n", $child->name, $child->usage());
                if ($child->description() !== '') {
                    $output .= sprintf("%-17s%s\n", '', $child->description());
                }
            }
        }

        echo $output;
    }

    private function renderNamespace(Command $command): void
    {
        $usage = 'codejitsu ' . $command->name . ':<subcommand> [arguments] [options]';
        $output = sprintf(
            "Description:\n  %s\n\nUsage:\n  %s\n\nAvailable commands:\n",
            $command->description(),
            $usage,
        );

        foreach ($command->commands() as $name => $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $child = $command->child((string) $name);
            if ($child === null) {
                continue;
            }

            $output .= sprintf("  %-18s%s\n", $child->name, $child->description());
        }

        echo $output;
    }

    private function namespaceHelp(Command $command): string
    {
        $help = $command->usage() . PHP_EOL . PHP_EOL;
        $help .= '<info>Available commands:</info>' . PHP_EOL;
        foreach ($command->commands() as $name => $definition) {
            if (is_array($definition)) {
                $help .= sprintf('  %s:%s', $command->name, $name) . PHP_EOL;
            }
        }

        return $help;
    }

    private function defineArguments(SymfonyCommand $console, Command $command): void
    {
        foreach ($command->usageArguments() as $argument) {
            $array = $argument === 'arguments';
            $console->addArgument(
                $argument,
                $array ? InputArgument::OPTIONAL | InputArgument::IS_ARRAY : InputArgument::OPTIONAL,
            );
        }

        foreach ($command->usageOptions() as $option) {
            $console->addOption($option, null, InputOption::VALUE_REQUIRED);
        }
    }
}
