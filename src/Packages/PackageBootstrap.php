<?php
declare(strict_types=1);
namespace Codejitsu\Packages;

use Codejitsu\Contracts\Packages\InstalledPackages;
use Codejitsu\Scrolls\ScrollCodex;

final class PackageBootstrap
{
    public function __construct(
        private readonly InstalledPackages $discovery = new InstalledPackageDiscovery(),
        private readonly PackageCompiler $compiler = new PackageCompiler(),
        private readonly PackageCache $cache = new PackageCache(),
        private readonly PackageRegistry $registry = new PackageRegistry(),
    ) {}

    public function boot(string $projectRoot, ScrollCodex $codex): array
    {
        $path = self::cachePath($projectRoot);
        $compiled = $this->cache->read($path);
        if ($compiled === null) {
            $compiled = $this->compiler->compile($this->discovery->all($projectRoot));
            try { $this->cache->write($path, $compiled); } catch (PackageException) { /* read-only installs use memory */ }
        }
        $this->registry->apply($compiled, $codex);
        return $compiled;
    }

    public static function cachePath(string $root): string
    {
        $manifest = json_decode((string) @file_get_contents(rtrim($root, '/\\') . '/composer.json'), true);
        $vendor = is_array($manifest) ? ($manifest['config']['vendor-dir'] ?? 'vendor') : 'vendor';
        return rtrim($root, '/\\') . '/' . trim((string) $vendor, '/\\') . '/codejitsu/packages.php';
    }
}
