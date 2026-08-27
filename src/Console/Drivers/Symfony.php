<?php

declare(strict_types=1);

namespace Codejitsu\Console\Drivers;

use Codejitsu\Contracts\Console\Driver;
use Codejitsu\Scrolls\Types\Command;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Symfony implements Driver
{
    public function run(array $argv, iterable $commands, callable $execute): int
    {
        $application = new Application('Codejitsu');
        $registered = [];

        foreach ($commands as $command) {
            if ($command instanceof Command) {
                $this->register($application, $command, $registered, $execute);
            }
        }

        return $application->run(new ArgvInput($argv));
    }

    /** @param array<string, bool> $registered */
    private function register(Application $application, Command $command, array &$registered, callable $execute): void
    {
        if (isset($registered[$command->name])) {
            return;
        }

        $console = new \Symfony\Component\Console\Command\Command($command->name);
        $console->setDescription($command->isNamespace()
            ? $command->description()
            : trim($command->usage() . ' — ' . $command->description(), ' —'));
        $console->setHelp($command->usage());

        if ($command->isNamespace()) {
            $console->setHelp($this->namespaceHelp($command));

            foreach ($command->commands() as $name => $definition) {
                if (is_array($definition) && ($child = $command->child((string) $name)) instanceof Command) {
                    $this->register($application, $child, $registered, $execute);
                }
            }

            $console->setCode(static function (InputInterface $input, OutputInterface $output) use ($command): int {
                $output->writeln('<info>Available commands:</info>');
                foreach ($command->commands() as $name => $definition) {
                    if (is_array($definition)) {
                        $output->writeln(sprintf('  %s:%s', $command->name, $name));
                    }
                }

                return 0;
            });
        } else {
            $this->defineArguments($console, $command);
            $console->setCode(function (InputInterface $input, OutputInterface $output) use ($command, $execute): int {
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

        $application->add($console);
        $registered[$command->name] = true;
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

    private function defineArguments(\Symfony\Component\Console\Command\Command $console, Command $command): void
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
