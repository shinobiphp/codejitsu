<?php
declare(strict_types=1);
namespace Codejitsu\Catalog;

use Codejitsu\Contracts\Packages\InstalledPackages;
use Codejitsu\PackageManager;
use RuntimeException;

final readonly class PackageCatalogActionProvider implements CatalogActionProvider
{
    public function __construct(private PackageManager $manager, private InstalledPackages $installed) {}

    public function supports(array $entry): bool
    {
        return ($entry['kind'] ?? null) === 'package' && $this->name($entry) !== null;
    }

    public function actions(array $entry, string $root): array
    {
        $name = $this->requiredName($entry);
        $installed = array_any($this->installed->all($root), static fn ($package): bool => $package->name === $name);
        return $installed
            ? [
                'info' => ['label' => 'Info', 'consequential' => false],
                'update' => ['label' => 'Update', 'consequential' => true],
                'uninstall' => ['label' => 'Uninstall', 'consequential' => true],
            ]
            : [
                'info' => ['label' => 'Info', 'consequential' => false],
                'install' => ['label' => 'Install', 'consequential' => true],
            ];
    }

    public function execute(string $action, array $entry, string $root): string
    {
        $name = $this->requiredName($entry);
        return match ($action) {
            'info' => $this->manager->info($name, $root),
            'install' => $this->result('Installed', $name, $this->manager->install($name, $root)),
            'update' => $this->result('Updated', $name, $this->manager->update($name, $root)),
            'uninstall' => $this->result('Uninstalled', $name, $this->manager->uninstall($name, $root)),
            default => throw new RuntimeException(sprintf('Unknown package catalog action [%s].', $action)),
        };
    }

    private function result(string $verb, string $name, int $exit): string
    {
        if ($exit !== 0) throw new RuntimeException(sprintf('%s package [%s] failed with status %d.', $verb, $name, $exit));
        return sprintf("%s package [%s].\n", $verb, $name);
    }

    /** @param array<string,mixed> $entry */
    private function requiredName(array $entry): string
    {
        return $this->name($entry) ?? throw new RuntimeException('Catalog entry is not a package identifier.');
    }

    /** @param array<string,mixed> $entry */
    private function name(array $entry): ?string
    {
        return preg_match('~^package://([^#]+)(?:#|$)~', (string) ($entry['identifier'] ?? ''), $matches) === 1
            ? $matches[1]
            : null;
    }
}
