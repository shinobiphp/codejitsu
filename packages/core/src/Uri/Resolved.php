<?php

declare(strict_types=1);

namespace Codejitsu\Uri;

use Codejitsu\Contracts\Uri\Resolvable;
use Codejitsu\Contracts\Uri\Resolved as ResolvedContract;
use Codejitsu\Contracts\Uri\Uri as UriContract;
use Codejitsu\Enums\Environment;
use InvalidArgumentException;

final readonly class Resolved implements ResolvedContract
{
    public function __construct(
        public UriContract $uri,
        public mixed $target,
        public ?string $tenant = null,
        public string $node = Uri::DEFAULT_TARGET,
        public ?int $port = null,
        public ?string $path = null,
        public ?string $version = null,
        public array $params = [],
        public ?string $transport = null,
    ) {}

    public static function fromUri(
        UriContract $uri,
        mixed $target,
        ?string $transport = null,
    ): self {
        return new self(
            uri: $uri,
            target: $target,
            tenant: $uri->tenant,
            node: $uri->target,
            path: $uri->path,
            version: $uri->version,
            params: $uri->query,
            transport: $transport,
        );
    }

    public function get(): Resolvable
    {
        return $this();
    }

    public function __invoke(): Resolvable
    {
        try {
            return $this->materialize();
        } catch (\Throwable $exception) {
            Environment::error($exception);

            throw $exception;
        }
    }

    private function materialize(): Resolvable
    {
        if ($this->target instanceof Resolvable) {
            return $this->target;
        }

        if (!is_string($this->target)) {
            throw new InvalidArgumentException(
                'Resolution target must be a Resolvable instance or class name.'
            );
        }

        if (!class_exists($this->target)) {
            throw new InvalidArgumentException(
                "Resolution target [{$this->target}] does not exist."
            );
        }

        if (!is_a($this->target, Resolvable::class, true)) {
            throw new InvalidArgumentException(
                "Resolution target [{$this->target}] must implement " .
                Resolvable::class . '.'
            );
        }

        return $this->target::fromResolution($this);
    }

    public function toArray(): array
    {
        return [
            'uri'       => (string) $this->uri,
            'tenant'    => $this->tenant,
            'node'      => $this->node,
            'port'      => $this->port,
            'path'      => $this->path,
            'version'   => $this->version,
            'params'    => $this->params,
            'transport' => $this->transport,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return (string) $this->uri;
    }
}