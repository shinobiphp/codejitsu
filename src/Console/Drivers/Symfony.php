<?php

declare(strict_types=1);

namespace Codejitsu\Console\Drivers;

use Codejitsu\Contracts\Console\Driver;
use Codejitsu\Scrolls\Types\Command;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Symfony implements Driver
{
    public function run(array $argv, iterable $commands): int
    {
        $application = new Application('Codejitsu');
        $registered = [];

        foreach ($commands as $command) {
            if (!$command instanceof Command) {
                continue;
            }

            $this->register($application, $command, $registered);
        }

        return $application->run(new ArgvInput($argv));
    }

    /** @param array<string, bool> $registered */
    private function register(Application $application, Command $command, array &$registered): void
    {
        if (isset($registered[$command->name])) {
            return;
        }

        $console = new \Symfony\Component\Console\Command\Command($command->name);
        $console->setDescription($command->description());

        if ($command->isNamespace()) {
            foreach ($command->commands() as $name => $definition) {
                if (!is_array($definition)) {
                    continue;
                }

                $child = $command->child((string) $name);
                if ($child instanceof Command) {
                    $this->register($application, $child, $registered);
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
            $this->defineArguments($console, $command->usage());
            $console->setCode(function (InputInterface $input, OutputInterface $output) use ($command): int {
                $payload = [];
                foreach ($command->usageArguments() as $argument) {
                    $payload[] = $input->getArgument($argument);
                }

                $result = $command->execute(...$payload);
                if (is_string($result) && $result !== '') {
                    $output->write($result);
                }

                return is_int($result) ? $result : 0;
            });
        }

        $application->add($console);
        $registered[$command->name] = true;
    }

    private function defineArguments(\Symfony\Component\Console\Command\Command $console, string $usage): void
    {
        preg_match_all('/(<[^>]+>|\[[^\]]+\])/', $usage, $matches);

        foreach ($matches[1] as $index => $token) {
            $name = trim($token, '<>[]');
            $optional = $token[0] === '[';
            $array = $name === 'arguments';

            $mode = $optional ? InputArgument::OPTIONAL : InputArgument::REQUIRED;
            if ($array) {
                $mode |= InputArgument::IS_ARRAY;
            }

            $console->addArgument($name, $mode);
        }
    }
}
