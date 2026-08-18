<?php
declare(strict_types=1);

namespace Codejitsu\Uri;

use InvalidArgumentException;
use Stringable;

class Uri implements Stringable
{
    public const DEFAULT_TARGET = 'local';

    /**
     * Tenant scope identifier, or null for global/system execution scope.
     */
    public private(set) ?string $tenant;

    /**
     * Canonical singular scroll type (e.g., 'app', 'config', 'capability').
     */
    public private(set) string $type;

    /**
     * Target node address or local identifier (e.g., 'local', 'archiq', '192.168.1.50:9000').
     */
    public private(set) string $target;

    /**
     * Scroll version constraint, or null if targeting the latest version.
     */
    public private(set) ?string $version;

    public private(set) ?string $path;

    /**
     * Query payload / metadata parameters array.
     * @var array<string, mixed>
     */
    public array $query = [];

    /**
     * Virtual property hook: plural Codex registry key (e.g., 'apps', 'configs', 'capabilities').
     */
    public string $key {
        get => match (true) {
            str_ends_with($this->type, 'y') => substr($this->type, 0, -1) . 'ies',
            str_ends_with($this->type, 's') => $this->type,
            default                         => $this->type . 's',
        };
    }

    /**
     * Virtual property hook: checks if target is local node.
     */
    public bool $isLocal {
        get => $this->target === self::DEFAULT_TARGET;
    }

    /**
     * Virtual property hook: checks if execution context is global (no tenant specified).
     */
    public bool $isGlobal {
        get => $this->tenant === null;
    }

    /**
     * Virtual property hook: checks if targeting unconstrained / latest version.
     */
    public bool $isLatest {
        get => $this->version === null;
    }

    public function __construct(
        string|Stringable $uri,
        ?string $defaultTenant = null,
        ?string $defaultVersion = null
    ) {
        $uriString = (string) $uri;
        $parsed = parse_url($uriString);

        $scheme      = $parsed['scheme'] ?? null;
        $user        = $parsed['user'] ?? null;
        $host        = $parsed['host'] ?? null;
        $port        = isset($parsed['port']) ? ":{$parsed['port']}" : '';
        $version     = $parsed['fragment'] ?? null;
        $path        = $parsed['path'] ?? null;
        $queryString = $parsed['query'] ?? null;

        if (!$scheme) {
            throw new InvalidArgumentException("Invalid Codejitsu URI: missing scheme in [{$uriString}]");
        }

        $this->type = $this->normalizeType(strtolower($scheme));
        $this->tenant = ($user !== null && $user !== '') ? strtolower($user) : $defaultTenant;

        $targetHost = $host ? ($host . $port) : self::DEFAULT_TARGET;
        $this->target = strtolower($targetHost);

        // Version: null if fragment omitted or empty
        $this->version = ($version !== null && $version !== '') ? $version : $defaultVersion;
        $this->path = $path ? trim($path, '/') : null;

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

        if (!empty($this->query)) {
            $uri .= '?' . http_build_query($this->query);
        }

        if (!$this->isLatest) {
            $uri .= "#{$this->version}";
        }

        return $uri;
    }
}