<?php

declare(strict_types=1);

namespace Codejitsu\Packages;

final readonly class InstalledPackage
{
    public function __construct(
        public string $name,
        public string $version,
        public string $root,
        public string $manifest,
    ) {}
}
