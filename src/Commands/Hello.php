<?php

declare(strict_types=1);

namespace Codejitsu\Commands;

final class Hello
{
    public static function run(mixed $arguments = []): int
    {
        $name = is_array($arguments)
            ? ($arguments[0] ?? 'shinobi')
            : ($arguments ?: 'shinobi');

        fwrite(STDOUT, sprintf("Hello, %s!%s", (string) $name, PHP_EOL));
        return 0;
    }
}