<?php
declare(strict_types=1);

namespace Codejitsu\Buildshido;

use Codejitsu\Contracts\Packages\PackageSetup as PackageSetupContract;
use Codejitsu\Packages\SetupContext;

final class PackageSetup implements PackageSetupContract
{
    public function run(SetupContext $context): int
    {
        return 0;
    }
}
