<?php

declare(strict_types=1);

namespace Codejitsu\Uri;

use InvalidArgumentException;
use Stringable;

class Uri implements Stringable
{
    public const DEFAULT_TARGET = 'local';

    public private(set) ?string $tenant;
    public private(set) string $type;
    public private(set) string $target;
    public private(set) ?string $version;
    public private(set) ?string $path;

    /** @var array<string> */
    public private(set) array $sources;

    /** @var array<string, mixed> */
    public array $query = [];
    public string $resourcePath {
        get => trim(implode('/', array_filter([
            $this->target === self::DEFAULT_TARGET ? null : $this->target,
            $this->path,
        ], static fn (?string $part): bool => $part !== null && $part !== '')), '/');
    }
    
    public string $key {
        get => match (true) {
            str_ends_with($this->type, 'y') => substr($this->type, 0, -1) . 'ies',
            str_ends_with($this->type, 's') => $this->type,
            default                         => $this->type . 's',
        };
    }

    public bool $isLocal {
        get => $this->target === self::DEFAULT_TARGET;
    }

    public bool $isGlobal {
        get => $this->tenant === null;
    }

    public bool $isLatest {
        get => $this->version === null;
    }

    public function __construct(
        string|Stringable $uri,
        ?string $defaultTenant = null,
        ?string $defaultVersion = null
    ) {
        $uriString = (string) $uri;
        $fragment = null;
        $queryString = null;

        if (str_contains($uriString, '#')) {
            [$uriString, $fragment] = explode('#', $uriString, 2);
        }

        if (str_contains($uriString, '?')) {
            [$uriString, $queryString] = explode('?', $uriString, 2);
        }

        $sourceSelector = null;
        $sourceSeparator = strrpos($uriString, '@');
        if ($sourceSeparator !== false) {
            $candidate = substr($uriString, $sourceSeparator + 1);
            if ($candidate !== '' && preg_match('/^[A-Za-z0-9][A-Za-z0-9_.-]*$/', $candidate)) {
                $sourceSelector = $candidate;
                $uriString = substr($uriString, 0, $sourceSeparator);
            }
        }

        $parsed = parse_url($uriString);
        $scheme = $parsed['scheme'] ?? null;

        if (!$scheme) {
            throw new InvalidArgumentException("Invalid Codejitsu URI: missing scheme in [{$uri}]");
        }

        $this->type = $this->normalizeType(strtolower($scheme));

        $user = $parsed['user'] ?? null;
        $this->tenant = ($user !== null && $user !== '') ? strtolower($user) : $defaultTenant;

        $host = $parsed['host'] ?? null;
        $port = isset($parsed['port']) ? ":{$parsed['port']}" : '';
        $this->target = strtolower($host ? $host . $port : self::DEFAULT_TARGET);

        $this->path = isset($parsed['path']) && $parsed['path'] !== ''
            ? trim($parsed['path'], '/')
            : null;

        $this->sources = $sourceSelector === null
            ? []
            : array_values(array_filter(
                array_map('strtolower', explode('.', $sourceSelector)),
                static fn (string $source): bool => $source !== '',
            ));

        $this->version = ($fragment !== null && $fragment !== '') ? $fragment : $defaultVersion;

        if ($queryString !== null && $queryString !== '') {
            parse_str($queryString, $this->query);
        }
    }

    public static function make(
        string|self $uri,
        ?string $defaultTenant = null,
        ?string $defaultVersion = null
    ): self {
        return $uri instanceof self ? $uri : new self($uri, $defaultTenant, $defaultVersion);
    }

    public function __get(string $name): mixed
    {
        return $this->query[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->query[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->query[$name]);
    }

    public function __unset(string $name): void
    {
        unset($this->query[$name]);
    }

    public function withQuery(array $params): self
    {
        $clone = clone $this;
        $clone->query = array_merge($this->query, $params);
        return $clone;
    }

    protected function normalizeType(string $scheme): string
    {
        return match (true) {
            str_ends_with($scheme, 'ies') => substr($scheme, 0, -3) . 'y',
            str_ends_with($scheme, 'ss')  => $scheme,
            str_ends_with($scheme, 's')   => substr($scheme, 0, -1),
            default                       => $scheme,
        };
    }

    public function __toString(): string
    {
        $authority = $this->isGlobal ? '' : "{$this->tenant}@";
        $authority .= $this->target;

        $uri = "{$this->type}://{$authority}";

        if ($this->path !== null && $this->path !== '') {
            $uri .= "/{$this->path}";
        }

        if ($this->sources !== []) {
            $uri .= '@' . implode('.', $this->sources);
        }

        if ($this->query !== []) {
            $uri .= '?' . http_build_query($this->query);
        }

        if (!$this->isLatest) {
            $uri .= "#{$this->version}";
        }

        return $uri;
    }
}
