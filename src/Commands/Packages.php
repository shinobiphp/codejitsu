<?php

declare(strict_types=1);

namespace Codejitsu\Commands;

use Codejitsu\ExecutionContext;
use Codejitsu\PackageManager;
use RuntimeException;

final class Packages
{
    public static function list(ExecutionContext $context): string
    {
        return self::manager()->list(self::root($context));
    }

    public static function info(ExecutionContext $context): string
    {
        $package = self::argument($context);
        return self::manager()->info($package, self::root($context));
    }

    public static function install(ExecutionContext $context): int
    {
        return self::manager()->install(self::argument($context), self::root($context));
    }

    public static function remove(ExecutionContext $context): int
    {
        return self::manager()->remove(self::argument($context), self::root($context));
    }

    public static function update(ExecutionContext $context): int
    {
        $package = $context->arguments[0] ?? null;
        if ($package !== null && (!is_string($package) || !self::validPackage($package))) {
            throw new RuntimeException('A valid Composer package name is required.');
        }

        return self::manager()->update($package, self::root($context));
    }

    private static function manager(): PackageManager
    {
        return new PackageManager();
    }

    private static function root(ExecutionContext $context): string
    {
        $root = getcwd();
        if (isset($context->arguments[1]) && is_string($context->arguments[1]) && is_dir($context->arguments[1])) {
            $root = $context->arguments[1];
        }

        if ($root === false) {
            throw new RuntimeException('Unable to determine the project root.');
        }

        return $root;
    }

    private static function argument(ExecutionContext $context): string
    {
        $argument = $context->arguments[0] ?? null;
        if (is_string($argument) && self::validPackage($argument)) {
            return $argument;
        }

        throw new RuntimeException('A valid Composer package name is required.');
    }

    private static function validPackage(string $package): bool
    {
        return preg_match('/^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?\/[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$/i', $package) === 1;
    }
}
