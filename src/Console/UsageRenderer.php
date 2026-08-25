<?php

declare(strict_types=1);

namespace Codejitsu\Console;

use Codejitsu\Scrolls\Types\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;

final class UsageRenderer
{
    public function __construct(?bool $decorated = null)
    {
        $this->formatter = new OutputFormatter(
            $decorated ?? (defined('STDOUT') && stream_isatty(STDOUT)),
        );
    }

    private readonly OutputFormatter $formatter;

    /** @param array<string, Command> $commands */
    public function render(array $commands): string
    {
        $groups = [];
        $standalone = [];

        foreach ($commands as $command) {
            if ($command->isNamespace()) {
                $groups[$command->name] = $command;
                continue;
            }

            $standalone[$command->name] = $command;
        }

        ksort($groups);
        ksort($standalone);

        $output = '<comment>Codejitsu</comment>' . PHP_EOL . PHP_EOL;
        $output .= '<info>Usage:</info>' . PHP_EOL;
        $output .= '  codejitsu <command> [arguments] [options]' . PHP_EOL . PHP_EOL;
        $output .= '<info>Available commands:</info>' . PHP_EOL . PHP_EOL;

        foreach ($groups as $command) {
            $output .= $this->group($command);
        }

        if ($standalone !== []) {
            $output .= '<info>Other</info>' . PHP_EOL;
            foreach ($standalone as $command) {
                $output .= $this->line($command->usage(), $command->description());
            }
            $output .= PHP_EOL;
        }

        $output .= 'Run "codejitsu <command> --help" for more information.' . PHP_EOL;

        return $this->formatter->format($output);
    }

    public function renderNamespace(Command $command): string
    {
        $output = '<info>Usage:</info>' . PHP_EOL;
        $output .= sprintf(
            '  codejitsu %s [arguments] [options]',
            $this->escape($command->name . ':<subcommand>'),
        ) . PHP_EOL . PHP_EOL;

        $children = $command->commands();
        ksort($children);

        if ($children !== []) {
            $output .= '<info>Available commands:</info>' . PHP_EOL;
            foreach ($children as $name => $definition) {
                if (!is_array($definition)) {
                    continue;
                }

                $qualified = $command->name . ':' . $name;
                $usage = is_string($definition['usage'] ?? null) && $definition['usage'] !== ''
                    ? $definition['usage']
                    : $qualified;
                $description = is_string($definition['description'] ?? null)
                    ? $definition['description']
                    : '';

                $output .= $this->line($usage, $description);
            }
        }

        return $this->formatter->format($output);
    }

    public function format(string $output): string
    {
        return $this->formatter->format($output);
    }

    private function group(Command $command): string
    {
        $output = '<info>' . $this->escape(ucfirst($command->name)) . '</info>' . PHP_EOL;
        $children = $command->commands();
        ksort($children);

        foreach ($children as $name => $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $qualified = $command->name . ':' . $name;
            $usage = is_string($definition['usage'] ?? null) && $definition['usage'] !== ''
                ? $definition['usage']
                : $qualified;
            $description = is_string($definition['description'] ?? null)
                ? $definition['description']
                : '';

            $output .= $this->line($usage, $description);
        }

        return $output . PHP_EOL;
    }

    private function line(string $usage, string $description): string
    {
        return sprintf(
            '  <info>%-34s</info> %s%s',
            $this->escape($usage),
            $this->escape($description),
            PHP_EOL,
        );
    }

    private function escape(string $value): string
    {
        return OutputFormatter::escape($value);
    }
}
