<?php
declare(strict_types=1);

namespace Codejitsu\Contracts\Uri;

use Codejitsu\Contracts\Uri\Resolved;
use Codejitsu\Contracts\Uri\ResolverDriver;
use Codejitsu\Contracts\Uri\Uri;
use Codejitsu\Contracts\Uri\Resolvable;

interface Resolver
{
    public function register(string $type, DriverContract $driver): void;
    public function resolve(Uri|string $uri): Resolved;    
    public function __invoke(Uri|string $uri): Resolvable;
}