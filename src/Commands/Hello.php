<?php

declare(strict_types=1);

namespace Codejitsu\Commands;

use Codejitsu\ExecutionContext;

final class Hello
{
    public static function run(ExecutionContext $context): string
    {
        $arguments = $context->arguments;
        $name = is_array($arguments)
            ? ($arguments[0] ?? 'shinobi')
            : ($arguments ?: 'shinobi');

        return sprintf("Hello, %s!%s", (string) $name, PHP_EOL);
    }
}
