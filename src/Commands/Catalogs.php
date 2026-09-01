<?php
declare(strict_types=1);
namespace Codejitsu\Commands;

use Codejitsu\Catalog\CatalogIndex;
use Codejitsu\Catalog\CatalogManager;
use Codejitsu\Catalog\CatalogTui;
use Codejitsu\Catalog\PackageCatalogActionProvider;
use Codejitsu\Console\TerminalEditor;
use Codejitsu\Console\TerminalQuestioner;
use Codejitsu\ExecutionContext;
use Codejitsu\PackageManager;
use Codejitsu\Packages\InstalledPackageDiscovery;
use RuntimeException;

final class Catalogs
{
    public static function list(ExecutionContext $context): string
    {
        $catalogs = self::index($context)->catalogs();
        if ($catalogs === []) return "No catalogs found.\n";
        $root = getcwd() ?: throw new RuntimeException('Unable to determine the project root.');
        $manager = $context->codex === null ? null : new CatalogManager($root, $context->codex);
        return implode('', array_map(static fn (array $catalog): string => sprintf(
            "%-28s %-18s %d entries (%s)\n",
            $catalog['name'], $catalog['source'], $catalog['entries'],
            $catalog['source'] === 'project-catalogs' && $manager?->writable($catalog['name']) ? 'writable' : 'read-only',
        ), $catalogs));
    }

    public static function show(ExecutionContext $context): string
    {
        $catalog = self::index($context)->catalog(self::argument($context, 0, 'A Catalog name or URI is required.'));
        return json_encode($catalog->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }

    public static function search(ExecutionContext $context): string
    {
        $query = self::argument($context, 0, 'A Catalog search query is required.');
        $kind = trim((string) ($context->arguments[1] ?? '')) ?: null;
        $entries = self::index($context)->searchAll($query, $kind);
        if ($entries === []) return "No catalog entries found.\n";
        return implode('', array_map(static fn (array $entry): string => sprintf(
            "%-52s %-12s %s\n",
            $entry['identifier'], $entry['kind'], $entry['description'] ?? '',
        ), $entries));
    }

    public static function edit(ExecutionContext $context): string
    {
        if ($context->codex === null) throw new RuntimeException('Catalog commands require a bound ScrollCodex.');
        $root = getcwd() ?: throw new RuntimeException('Unable to determine the project root.');
        $index = new CatalogIndex($context->codex);
        $installed = new InstalledPackageDiscovery();
        $provider = new PackageCatalogActionProvider(
            new PackageManager(packages: $installed, catalog: $index),
            $installed,
        );
        return (new CatalogTui($index, new CatalogManager($root, $context->codex), [$provider]))
            ->run(new TerminalQuestioner(), new TerminalEditor());
    }

    private static function index(ExecutionContext $context): CatalogIndex
    {
        if ($context->codex === null) throw new RuntimeException('Catalog commands require a bound ScrollCodex.');
        return new CatalogIndex($context->codex);
    }

    private static function argument(ExecutionContext $context, int $index, string $message): string
    {
        $value = trim((string) ($context->arguments[$index] ?? ''));
        if ($value === '') throw new RuntimeException($message);
        return $value;
    }
}
