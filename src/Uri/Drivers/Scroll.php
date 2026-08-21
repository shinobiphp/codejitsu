<?php

declare(strict_types=1);

namespace Codejitsu\Uri\Drivers;

use Codejitsu\Contracts\Uri\ResolverDriver;
use Codejitsu\Contracts\Uri\Resolved;
use Codejitsu\Contracts\Uri\Uri;
use Codejitsu\Scrolls\ScrollCodex;

final readonly class Scroll implements ResolverDriver
{
    public function __construct(
        private ScrollCodex $codex,
    ) {}

    public function resolve(Uri $uri): Resolved
    {
        return $this->codex->resolveUri($uri);
    }
}