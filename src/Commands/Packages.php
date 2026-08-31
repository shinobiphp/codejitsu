<?php

declare(strict_types=1);

namespace Codejitsu\Commands;

use Codejitsu\Catalog\CatalogIndex;
use Codejitsu\ExecutionContext;
use Codejitsu\PackageManager;
use Codejitsu\Composer\PackageInstaller;
use Codejitsu\Packages\PackageBootstrap;
use Codejitsu\Packages\PackageCache;
use RuntimeException;

final class Packages
{
    public static function search(ExecutionContext $context): string
    {
        $query = trim((string) ($context->arguments[0] ?? ''));
        if ($query === '') throw new RuntimeException('A package search query is required.');
        return (new PackageManager())->search($query, self::root());
    }

    public static function list(ExecutionContext $context): string
    {
        $root = getcwd();
        if ($root === false) {
            throw new RuntimeException('Unable to determine the project root.');
        }

        $catalog = $context->codex === null ? null : new CatalogIndex($context->codex);
        return (new PackageManager(catalog: $catalog))->list($root);
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

    public static function uninstall(ExecutionContext $context): int
    {
        return self::remove($context);
    }

    public static function cacheStatus(ExecutionContext $context): string
    {
        return json_encode((new PackageCache())->status(PackageBootstrap::cachePath(self::root())), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n";
    }

    public static function cacheRebuild(ExecutionContext $context): string
    {
        $compiled = (new PackageInstaller())->rebuild(self::root());
        return sprintf("Rebuilt package cache for %d package(s).\n", count($compiled['packages']));
    }

    public static function cacheClear(ExecutionContext $context): string
    {
        (new PackageCache())->clear(PackageBootstrap::cachePath(self::root()));
        return "Package cache cleared.\n";
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

    private static function root(): string
    {
        return getcwd() ?: throw new RuntimeException('Unable to determine the project root.');
    }
}
