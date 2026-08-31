<?php
declare(strict_types=1);
namespace Codejitsu\Composer;

use Codejitsu\Packages\InstalledPackageDiscovery;
use Codejitsu\Packages\PackageBootstrap;
use Codejitsu\Packages\PackageCache;
use Codejitsu\Packages\PackageCompiler;

final class PackageInstaller
{
    public function rebuild(string $projectRoot): array
    {
        $compiled = (new PackageCompiler())->compile((new InstalledPackageDiscovery())->all($projectRoot));
        (new PackageCache())->write(PackageBootstrap::cachePath($projectRoot), $compiled);
        return $compiled;
    }
}
