<?php

declare(strict_types=1);

namespace Codejitsu\Packages;

use Codejitsu\Contracts\Packages\InstalledPackages;
use RuntimeException;

final class InstalledPackageDiscovery implements InstalledPackages
{
    /** @param null|array<string,array<string,mixed>> $metadata */
    public function __construct(private readonly ?array $metadata = null) {}

    public function all(string $projectRoot): array
    {
        $records = $this->metadata ?? $this->readInstalledMetadata($projectRoot);
        $packages = [];
        foreach ($records as $name => $record) {
            if (($record['type'] ?? null) !== 'codejitsu-pkg') {
                continue;
            }
            $manifest = $record['extra']['codejitsu']['manifest'] ?? null;
            if (!is_string($manifest) || !$this->relative($manifest)) {
                throw new RuntimeException(sprintf(
                    'Package [%s] has invalid or missing [extra.codejitsu.manifest].',
                    $name,
                ));
            }
            $root = realpath((string) ($record['install_path'] ?? ''));
            if ($root === false || !is_dir($root)) {
                throw new RuntimeException(sprintf('Package [%s] install path does not exist.', $name));
            }
            $path = realpath($root . DIRECTORY_SEPARATOR . $manifest);
            if ($path === false || !is_file($path) || !$this->within($path, $root)) {
                throw new RuntimeException(sprintf('Package [%s] manifest [%s] is missing or escapes its package root.', $name, $manifest));
            }
            $packages[] = new InstalledPackage(
                (string) $name,
                (string) ($record['pretty_version'] ?? $record['version'] ?? '0.0.0'),
                $root,
                $path,
            );
        }
        usort($packages, static fn (InstalledPackage $a, InstalledPackage $b): int => $a->name <=> $b->name);
        return $packages;
    }

    /** @return array<string,array<string,mixed>> */
    private function readInstalledMetadata(string $projectRoot): array
    {
        $manifest = json_decode((string) @file_get_contents($projectRoot . '/composer.json'), true);
        $vendor = is_array($manifest) ? ($manifest['config']['vendor-dir'] ?? 'vendor') : 'vendor';
        $path = $projectRoot . '/' . trim((string) $vendor, '/\\') . '/composer/installed.json';
        $data = json_decode((string) @file_get_contents($path), true);
        $rows = is_array($data) ? ($data['packages'] ?? $data) : [];
        $records = [];
        foreach ($rows as $row) {
            if (is_array($row) && isset($row['name'])) {
                $installPath = $row['install-path'] ?? null;
                if (is_string($installPath)) {
                    $row['install_path'] = dirname($path) . '/' . $installPath;
                }
                $records[(string) $row['name']] = $row;
            }
        }
        return $records;
    }

    private function relative(string $path): bool
    {
        $path = str_replace('\\', '/', trim($path));
        return $path !== '' && !str_starts_with($path, '/')
            && preg_match('/^[A-Za-z]:\//', $path) !== 1
            && !in_array('..', explode('/', $path), true);
    }

    private function within(string $path, string $root): bool
    {
        return $path === $root || str_starts_with($path, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
    }
}
