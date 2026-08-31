<?php

declare(strict_types=1);

namespace Codejitsu\Contracts\Packages;

use Codejitsu\Packages\InstalledPackage;

interface InstalledPackages
{
    /** @return list<InstalledPackage> */
    public function all(string $projectRoot): array;
}
