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
        $root = getcwd();
        if ($root === false) {
            throw new RuntimeException('Unable to determine the project root.');
        }

        return (new PackageManager())->list($root);
    }

    public static function info(ExecutionContext $context): string
    {
        $package = $context->arguments[0] ?? null;
        if (!is_string($package) || preg_match('/^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?\/[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$/i', $package) !== 1) {
            throw new RuntimeException('A valid Composer package name is required.');
        }

        $root = getcwd();
        if ($root === false) {
            throw new RuntimeException('Unable to determine the project root.');
        }

        return (new PackageManager())->info($package, $root);
    }

    public static function install(ExecutionContext $context): int
    {
        $package = $context->arguments[0] ?? null;
        if (!is_string($package) || preg_match('/^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?\/[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$/i', $package) !== 1) {
            throw new RuntimeException('A valid Composer package name is required.');
        }

        $root = getcwd();
        if ($root === false) {
            throw new RuntimeException('Unable to determine the project root.');
        }

        return (new PackageManager())->install($package, $root);
    }

    public static function remove(ExecutionContext $context): int
    {
        $package = $context->arguments[0] ?? null;
        if (!is_string($package) || preg_match('/^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?\/[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$/i', $package) !== 1) {
            throw new RuntimeException('A valid Composer package name is required.');
        }

        $root = getcwd();
        if ($root === false) {
            throw new RuntimeException('Unable to determine the project root.');
        }

        return (new PackageManager())->remove($package, $root);
    }

    public static function update(ExecutionContext $context): int
    {
        $package = $context->arguments[0] ?? null;
        if ($package !== null && (!is_string($package) || preg_match('/^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?\/[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$/i', $package) !== 1)) {
            throw new RuntimeException('A valid Composer package name is required.');
        }

        $root = getcwd();
        if ($root === false) {
            throw new RuntimeException('Unable to determine the project root.');
        }

        return (new PackageManager())->update($package, $root);
    }
}
