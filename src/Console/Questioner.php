<?php

declare(strict_types=1);

namespace Codejitsu\Console;

interface Questioner
{
    public function ask(string $question, string $default = ''): string;

    /** @param list<string> $choices */
    public function select(string $question, array $choices, int $default = 0): string;
}
