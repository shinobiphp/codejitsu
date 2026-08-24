<?php
declare(strict_types=1);

namespace Codejitsu\Contracts\Uri;

use Codejitsu\Contracts\Uri\Uri;
use Codejitsu\Contracts\Uri\Resolvable;

use JsonSerializable;
use Stringable;

interface Resolved extends Stringable, JsonSerializable
{
    public function __construct(
        Uri $uri,
        mixed $target,
        ?string $tenant = null,
        string $node = Uri::DEFAULT_TARGET,
        ?int $port = null,
        ?string $path = null,
        ?string $version = null,
        array $params = [],
        ?string $transport = null,
    );

    public static function fromUri(Uri $uri, mixed $target, ?string $transport = null): self;
    
    public function get(): Resolvable;
    public function toArray(): array;

    /**
     * Invoke the resolution container to materialize or retrieve the target as a Resolvable instance.
     */
    public function __invoke(): Resolvable;
    public function __toString(): string;
}