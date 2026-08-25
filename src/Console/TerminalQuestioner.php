<?php

declare(strict_types=1);

namespace Codejitsu\Console;

use InvalidArgumentException;

final class TerminalQuestioner implements Questioner
{
    public function ask(string $question, string $default = ''): string
    {
        fwrite(STDOUT, $question);
        $value = fgets(STDIN);
        if ($value === false) {
            return $default;
        }

        $value = trim($value);
        return $value === '' ? $default : $value;
    }

    public function select(string $question, array $choices, int $default = 0): string
    {
        fwrite(STDOUT, PHP_EOL . "\033[1;36m{$question}\033[0m" . PHP_EOL);
        foreach ($choices as $index => $choice) {
            fwrite(STDOUT, sprintf("  \033[1;33m%d\033[0m) %s\n", $index + 1, $choice));
        }

        $selected = (int) $this->ask(sprintf('Selection [%d]: ', $default + 1), (string) ($default + 1)) - 1;
        if (!isset($choices[$selected])) {
            throw new InvalidArgumentException('Invalid selection.');
        }

        return $choices[$selected];
    }
}
