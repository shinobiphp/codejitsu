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
        $scroll->bind($this);
        $this->scrolls[$this->keyFor($scroll->type, $scroll->name)] = $scroll;
        return $this;
    }

    public function has(string $target): bool
    {
        if (parent::has($target)) {
            return true;
        }
        if (str_contains($target, '://')) {
            try {
                $key = $this->keyFromTarget($target);
            } catch (InvalidArgumentException) {
                return false;
            }
            return $key !== null && isset($this->scrolls[$key]);
        }
        return $this->findByName($target) !== null;
    }

    public function get(string $target): mixed
    {
        if (parent::has($target)) {
            return parent::get($target);
        }
        if (str_contains($target, '://')) {
            $key = $this->keyFromTarget($target);
            if ($key !== null && isset($this->scrolls[$key])) {
                return $this->scrolls[$key];
            }
        }
        $scroll = $this->findByName($target);
        if ($scroll !== null) {
            return $scroll;
        }
        throw new OutOfBoundsException(sprintf('Scroll [%s] not found in Codex.', $target));
    }

    public function all(bool $hydrateAll = false): array
    {
        $items = parent::all($hydrateAll);
        foreach ($this->scrolls as $key => $scroll) {
            $items[$key] = $scroll;
        }
        return $items;
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
            if ($scroll instanceof ScrollContract && in_array($tag, $scroll->tags, true)) {
                $result->registerScroll($scroll);
            }
        }
        return $result;
    }

    public function withTags(array $tags): static
    {
        $result = new static($this->codec);
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
        if (str_contains($value, '://')) {
            $parsed = Uri::make($value);
            $type = Types::normalize($parsed->type, null);
            if (!$type instanceof Types) {
                throw new InvalidArgumentException(sprintf('Unknown Scroll URI scheme [%s].', $parsed->type));
            }
            $name = $parsed->path ?? $parsed->target;
            $scroll = $this->scrolls[$this->keyFor($type, $name)] ?? null;
            if (!$scroll instanceof ScrollContract) {
                throw new OutOfBoundsException(sprintf('Scroll [%s] not found in Codex.', $uri));
            }
            return $scroll->bind($this);
        }
        $scroll = $this->findByName($value);
        if ($scroll === null) {
            throw new OutOfBoundsException(sprintf('Scroll [%s] not found in Codex.', $uri));
        }
        return $scroll->bind($this);
    }

    public function resolveTyped(Types $type, string $name): ScrollContract
    {
        $scroll = str_contains($name, '://')
            ? $this->resolve($name)
            : $this->resolve(sprintf('%s://%s', $type->value, $name));
        if (!$scroll instanceof ScrollContract) {
            throw new LogicException(sprintf('Resolved item [%s] is not a Scroll.', $name));
        }
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
        return $this->resolve($method)(...$args);
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

    protected function validate(mixed $scroll): void
    {
        if (!$scroll instanceof ScrollContract) {
            throw new InvalidArgumentException('Invalid Scroll instance.');
        }
        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $scroll->name)) {
            throw new InvalidArgumentException(sprintf('Invalid Scroll name [%s].', $scroll->name));
        }
    }

    private function keyFor(Types|string $type, string $name): string
    {
        $type = $type instanceof Types ? $type : Types::normalize($type, null);
        if (!$type instanceof Types) {
            throw new InvalidArgumentException(sprintf('Unknown Scroll type [%s].', (string) $type));
        }
        return strtolower($type->value . ':' . trim($name));
    }

    private function keyFromTarget(string $target): ?string
    {
        $uri = Uri::make($target);
        $type = Types::normalize($uri->type, null);
        if (!$type instanceof Types) {
            return null;
        }
        return $this->keyFor($type, $uri->path ?? $uri->target);
    }

    private function findByName(string $name): ?ScrollContract
    {
        $matches = array_values(array_filter(
            $this->scrolls,
            static fn (ScrollContract $scroll): bool => strcasecmp($scroll->name, trim($name)) === 0,
        ));
        return count($matches) === 1 ? $matches[0] : null;
    }
}
