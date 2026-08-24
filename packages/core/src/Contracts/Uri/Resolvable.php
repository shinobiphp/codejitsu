<?php
declare(strict_types=1);

namespace Codejitsu\Contracts\Uri;

use Codejitsu\Contracts\Uri\Resolved as ResolvedContract;

interface Resolvable
{
    public static function fromResolution(ResolvedContract $resolved): static;
}