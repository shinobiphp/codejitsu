<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls;

use Codejitsu\EnvelopeCodex;
use Codejitsu\Contracts\Scrolls\Envelope as ScrollEnvelope;
use Codejitsu\Contracts\Scrolls\Scroll as ScrollContract;
use Codejitsu\Contracts\Scrolls\ScrollCodex as ScrollCodexContract;
use Codejitsu\Contracts\Scrolls\Store as StoreContract;
use Codejitsu\Enums\Scrolls\Types;
use Codejitsu\Uri\Resolved;
use Codejitsu\Uri\Uri;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;

class ScrollCodex extends EnvelopeCodex implements ScrollCodexContract
{
    protected array $scrolls = [];

    public function registerScroll(ScrollContract $scroll): static
    {
        $this->validate($scroll);
        $this->scrolls[strtolower($scroll->name)] = $scroll;
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

        $result = new static($this->codec);
        foreach ($this->envelopes as $name => $envelope) {
            if ($envelope->scrollType === $target) {
                $result->loadEnvelope($envelope, $name);
            }
        }
        foreach ($this->scrolls as $scroll) {
            if ($this->scrollType($scroll) === $target) {
                $result->registerScroll($scroll);
            }
        }
        return $result;
    }

    public function withTag(string $tag): static
    {
        $result = new static($this->codec);
        foreach ($this->all(true) as $scroll) {
            if (in_array($tag, $scroll->tags, true)) {
                $result->registerScroll($scroll);
            }
        }
        return $result;
    }

    public function withTags(array $tags): static
    {
        $result = new static($this->codec);
        foreach ($this->all(true) as $scroll) {
            if (empty(array_diff($tags, $scroll->tags))) {
                $result->registerScroll($scroll);
            }
        }
        return $result;
    }

    public function resolve(string $uri): mixed
    {
        $parsed = Uri::make($uri);
        $key = strtolower($parsed->path ?? $parsed->target);

        if ($key === '') {
            throw new InvalidArgumentException('Scroll URI cannot be empty.');
        }

        if ($this->has($key)) {
            $scroll = $this->get($key);
            if (!$scroll instanceof ScrollContract) {
                throw new LogicException(sprintf('Resolved item [%s] is not a Scroll.', $uri));
            }
            return $scroll;
        }

        throw new OutOfBoundsException(sprintf('Scroll [%s] not found in Codex.', $uri));
    }

    public function resolveTyped(Types $type, string $name): ScrollContract
    {
        $scroll = $this->resolve($name);
        if ($this->scrollType($scroll) !== $type) {
            throw new InvalidArgumentException(sprintf(
                'Scroll [%s] is type [%s], expected [%s].',
                $name,
                $this->scrollType($scroll)->value,
                $type->value,
            ));
        }
        return $scroll;
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
        $scroll = $this->resolve((string) $uri);
        return Resolved::fromUri($uri, $scroll);
    }

    public function __get(string $name): mixed { return $this->resolve($name); }

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

        return $envelope->scrollType->make(
            $envelope,
            $this->decodeEnvelope($envelope),
        );
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