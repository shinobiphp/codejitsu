<?php
declare(strict_types=1);

namespace Codejitsu\Uri;

use Codejitsu\Contracts\Uri\Resolver as ResolverContract;
use Codejitsu\Contracts\Uri\ResolverDriver as DriverContract;
use Codejitsu\Contracts\Uri\Resolved as ResolvedContract;
use Codejitsu\Contracts\Uri\Resolvable as ResolvableContract;
use Codejitsu\Contracts\Uri\Uri as UriContract;

use Codejitsu\Uri\Uri;
use Codejitsu\Enums\Environment;

class Resolver implements ResolverContract
{
    /** @var array<string, DriverContract> */
    protected array $drivers = [];
    
    protected ?DriverContract $defaultDriver = null;

    public function register(?string $type, DriverContract $driver): static
    {
        if ($type === null || empty($this->drivers)) {
            $this->defaultDriver = $driver;
        }

        if ($type !== null) {
            $this->drivers[strtolower($type)] = $driver;
        }

        return $this;
    }

    public function resolve(UriContract|string $uri): ResolvedContract
    {
        $uriObj = $uri instanceof UriContract ? $uri : Uri::make($uri);
        $type = strtolower($uriObj->type);

        $driver = $this->drivers[$type] ?? $this->defaultDriver;

        if ($driver === null) {
            return Environment::error(
                new \RuntimeException("No resolver driver registered for scheme type: [{$type}]")
            );
        }

        return $driver->resolve($uriObj);
    }

    public function __invoke(UriContract|string $uri): Resolvable
    {
        $resolved = $this->resolve($uri);
        return $resolved();
    }
}