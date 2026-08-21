<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls;

use Codejitsu\Codecs\Neon;
use Codejitsu\EnvelopeCodex;
use Codejitsu\Contracts\Scrolls\Envelope as ScrollEnvelope;
use Codejitsu\Contracts\Scrolls\Scroll as ScrollContract;
use Codejitsu\Contracts\Scrolls\ScrollCodex as ScrollCodexContract;
use Codejitsu\Contracts\Scrolls\Store as StoreContract;
use Codejitsu\Enums\Codecs;
use Codejitsu\Enums\Scrolls\Types;
use Codejitsu\Uri\Resolved;
use Codejitsu\Uri\Uri;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;

class ScrollCodex extends EnvelopeCodex implements ScrollCodexContract
{
    protected array $scrolls = [];

    public function __construct(array $itemsOrEnvelopes = [])
    {
        parent::__construct(Codecs::NEON, $itemsOrEnvelopes);
    }

    public function load(string $root): static
    {
        foreach ((new ScrollDiscovery(new Neon()))->discover($root) as $scroll) {
            $this->registerScroll($scroll);
        }

        return $this;
    }

    public function registerScroll(ScrollContract $scroll): static
    {
        $this->validate($scroll);
        $scroll->bind($this);
        $key = $this->identityKey($scroll);
        $this->scrolls[$key] = $scroll;
        $this->items[$key] = $scroll;
        return $this;
    }

    public function discover(StoreContract $store, array $discovered): static
    {
        foreach ($discovered as $discoveredScroll) {
            $envelope = $store->getDiscovered($discoveredScroll);
            if ($envelope !== null) {
                $this->loadEnvelope($envelope);
            }
        }
        return $this;
    }

    public function ofType(Types|string $type): static
    {
        $target = $type instanceof Types ? $type : Types::normalize($type, null);
        if (!$target instanceof Types) {
            throw new InvalidArgumentException(sprintf('Unknown Scroll type [%s].', (string) $type));
        }

        $result = new static();
        foreach ($this->all(true) as $scroll) {
            if ($scroll instanceof ScrollContract && $this->scrollType($scroll) === $target) {
                $result->registerScroll($scroll);
            }
        }
        return $result;
    }

    public function withTag(string $tag): static
    {
        $result = new static();
        foreach ($this->all(true) as $scroll) {
            if ($scroll instanceof ScrollContract && in_array($tag, $scroll->tags, true)) {
                $result->registerScroll($scroll);
            }
        }
        return $result;
    }

    public function withTags(array $tags): static
    {
        $result = new static();
        foreach ($this->all(true) as $scroll) {
            if ($scroll instanceof ScrollContract && empty(array_diff($tags, $scroll->tags))) {
                $result->registerScroll($scroll);
            }
        }
        return $result;
    }

    public function resolve(string $uri): mixed
    {
        $value = trim($uri);
        if ($value === '') {
            throw new InvalidArgumentException('Scroll URI cannot be empty.');
        }

        if (!str_contains($value, '://')) {
            $matches = array_values(array_filter(
                $this->all(true),
                static fn (mixed $scroll): bool =>
                    $scroll instanceof ScrollContract
                    && strtolower($scroll->name) === strtolower($value),
            ));

            return match (count($matches)) {
                1 => $matches[0],
                0 => throw new OutOfBoundsException(sprintf('Scroll [%s] not found in Codex.', $uri)),
                default => throw new InvalidArgumentException(sprintf(
                    'Scroll name [%s] is ambiguous; resolve it by URI.',
                    $uri,
                )),
            };
        }

        $parsed = Uri::make($value);
        $type = Types::normalize($parsed->type, null);
        if (!$type instanceof Types) {
            throw new InvalidArgumentException(sprintf('Unknown Scroll URI scheme [%s].', $parsed->type));
        }

        $name = strtolower($parsed->path ?? $parsed->target ?? '');
        if ($name === '') {
            throw new InvalidArgumentException(sprintf('Scroll URI [%s] has no name.', $uri));
        }

        $version = $parsed->version;
        foreach ($this->all(true) as $scroll) {
            if (!$scroll instanceof ScrollContract || $this->scrollType($scroll) !== $type) {
                continue;
            }

            if (strtolower($scroll->name) !== $name) {
                continue;
            }

            if ($version === null || $scroll->version === $version) {
                return $scroll->bind($this);
            }
        }

        throw new OutOfBoundsException(sprintf('Scroll [%s] not found in Codex.', $uri));
    }

    public function resolveTyped(Types $type, string $name): ScrollContract
    {
        return $this->resolve($type->scheme() . $name);
    }

    public function invoke(Types|string $type, string $name, mixed ...$args): mixed
    {
        $target = $type instanceof Types ? $type : Types::normalize($type, null);
        if (!$target instanceof Types) {
            throw new InvalidArgumentException(sprintf('Unknown Scroll type [%s].', (string) $type));
        }
        return $this->resolveTyped($target, $name)(...$args);
    }

    public function resolveUri(Uri $uri): Resolved
    {
        return Resolved::fromUri($uri, $this->resolve((string) $uri));
    }

    public function __get(string $name): mixed
    {
        return $this->resolve($name);
    }

    public function __isset(string $name): bool
    {
        try {
            $this->resolve($name);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function __call(string $method, array $args): mixed
    {
        if ($this->has($method)) {
            return $this->resolve($method)(...$args);
        }
        throw new OutOfBoundsException(sprintf('Scroll or method [%s] not found.', $method));
    }

    public function __invoke(string $target, mixed ...$args): mixed
    {
        return $this->resolve($target)(...$args);
    }

    protected function hydrateFromEnvelope(\Codejitsu\Contracts\Envelope $envelope): mixed
    {
        if (!$envelope instanceof ScrollEnvelope) {
            throw new LogicException('ScrollCodex can only hydrate Scroll envelopes.');
        }

        $scroll = $envelope->scrollType->make($envelope, $this->decodeEnvelope($envelope));
        return $scroll->bind($this);
    }

    protected function scrollType(ScrollContract $scroll): Types
    {
        $type = $scroll->type;
        if ($type instanceof Types) {
            return $type;
        }
        $normalized = Types::normalize($type, null);
        if (!$normalized instanceof Types) {
            throw new InvalidArgumentException(sprintf('Invalid Scroll type [%s].', (string) $type));
        }
        return $normalized;
    }

    private function identityKey(ScrollContract $scroll): string
    {
        return strtolower(sprintf(
            '%s:%s#%s',
            $this->scrollType($scroll)->value,
            $scroll->name,
            $scroll->version,
        ));
    }

    protected function validate(mixed $scroll): void
    {
        if (!$scroll instanceof ScrollContract) {
            throw new InvalidArgumentException('Invalid Scroll instance.');
        }
        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $scroll->name)) {
            throw new InvalidArgumentException(sprintf('Invalid Scroll name [%s].', $scroll->name));
        }
    }
}
