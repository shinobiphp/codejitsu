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

    /** @var array<string> */
    protected array $sources = [];

    public function __construct(array $itemsOrEnvelopes = [])
    {
        parent::__construct(Codecs::NEON, $itemsOrEnvelopes);
        $this->registerSource('default');
    }

    public function registerSource(string $source): static
    {
        $source = strtolower(trim($source));
        if ($source === '' || !preg_match('/^[a-z0-9][a-z0-9_.-]*$/', $source)) {
            throw new InvalidArgumentException(sprintf('Invalid Scroll source [%s].', $source));
        }

        if (!in_array($source, $this->sources, true)) {
            $this->sources[] = $source;
        }

        return $this;
    }

    public function load(string $root, ?string $source = null): static
    {
        $source ??= $this->sources[array_key_last($this->sources)] ?? 'default';
        $this->registerSource($source);

        foreach ((new ScrollDiscovery(new Neon()))->discover($root) as $scroll) {
            $this->registerScroll($scroll, $source);
        }

        return $this;
    }

    public function registerScroll(ScrollContract $scroll, ?string $source = null): static
    {
        $this->validate($scroll);
        $source ??= $this->sources[array_key_last($this->sources)] ?? 'default';
        $this->registerSource($source);

        $scroll->bind($this);
        $key = $this->identityKey($scroll);
        $this->scrolls[$source][$key] = $scroll;
        $this->items[$key] = $scroll;

        return $this;
    }

    public function query(array $criteria = []): array
    {
        $types = $criteria['type'] ?? null;
        $types = $types === null ? null : (array) $types;
        $types = $types === null
            ? null
            : array_map(
                static function (Types|string $type): Types {
                    $normalized = $type instanceof Types ? $type : Types::normalize($type, null);
                    if (!$normalized instanceof Types) {
                        throw new InvalidArgumentException(sprintf('Unknown Scroll type [%s].', (string) $type));
                    }
                    return $normalized;
                },
                $types,
            );

        $sources = $criteria['source'] ?? null;
        $sources = $sources === null ? array_reverse($this->sources) : (array) $sources;
        $sources = array_values(array_unique(array_map(
            static fn (string $source): string => strtolower(trim($source)),
            $sources,
        )));

        foreach ($sources as $source) {
            if (!in_array($source, $this->sources, true)) {
                throw new OutOfBoundsException(sprintf('Scroll source [%s] is not registered.', $source));
            }
        }

        $name = isset($criteria['name']) ? strtolower((string) $criteria['name']) : null;
        $path = isset($criteria['path']) ? trim(strtolower((string) $criteria['path']), '/') : null;
        $version = isset($criteria['version']) ? (string) $criteria['version'] : null;
        $tags = isset($criteria['tags']) ? array_map('strtolower', (array) $criteria['tags']) : [];
        $attributes = isset($criteria['attributes']) && is_array($criteria['attributes'])
            ? $criteria['attributes']
            : [];

        $entries = [];
        foreach ($sources as $source) {
            foreach ($this->scrolls[$source] ?? [] as $scroll) {
                if (!$scroll instanceof ScrollContract) {
                    continue;
                }

                $scrollType = $this->scrollType($scroll);
                if ($types !== null && !in_array($scrollType, $types, true)) {
                    continue;
                }

                $scrollName = strtolower(trim($scroll->name, '/'));
                if ($name !== null && $scrollName !== $name) {
                    continue;
                }

                if ($path !== null && $scrollName !== $path) {
                    continue;
                }

                if ($version !== null && $scroll->version !== $version) {
                    continue;
                }

                if ($tags !== [] && array_diff($tags, array_map('strtolower', $scroll->tags)) !== []) {
                    continue;
                }

                $data = $scroll->toArray();
                $metadata = array_diff_key($data, array_flip(['name', 'type', 'version', 'tags']));
                if ($attributes !== [] && !$this->matchesAttributes($metadata, $attributes)) {
                    continue;
                }

                $entries[] = new IndexEntry(
                    $scrollType->value,
                    $scroll->name,
                    $scroll->version,
                    $source,
                    $scroll->tags,
                    $metadata,
                    Uri::make(sprintf(
                        '%s%s@%s#%s',
                        $scrollType->scheme(),
                        $scroll->name,
                        $source,
                        $scroll->version,
                    )),
                );
            }
        }

        return $entries;
    }

    public function discover(StoreContract $store, array $discovered, ?string $source = null): static
    {
        $source ??= $this->sources[array_key_last($this->sources)] ?? 'default';
        $this->registerSource($source);

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
                    && strtolower(trim($scroll->name, '/')) === strtolower(trim($value, '/')),
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

        $name = strtolower(trim($parsed->resourcePath, '/'));
        if ($name === '') {
            throw new InvalidArgumentException(sprintf('Scroll URI [%s] has no logical path.', $uri));
        }

        $version = $parsed->version;
        $sources = $this->sourceCascade($parsed);

        foreach ($sources as $source) {
            foreach ($this->scrolls[$source] ?? [] as $scroll) {
                if (!$scroll instanceof ScrollContract || $this->scrollType($scroll) !== $type) {
                    continue;
                }

                if (strtolower(trim($scroll->name, '/')) !== $name) {
                    continue;
                }

                if ($version === null || $scroll->version === $version) {
                    return $scroll->bind($this);
                }
            }
        }

        throw new OutOfBoundsException(sprintf('Scroll [%s] not found in Codex.', $uri));
    }

    public function resolveTyped(Types $type, string $name): ScrollContract
    {
        return $this->resolve($type->scheme() . trim($name, '/'));
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

    /** @return array<string> */
    private function sourceCascade(Uri $uri): array
    {
        if ($uri->sources !== []) {
            foreach ($uri->sources as $source) {
                if (!in_array($source, $this->sources, true)) {
                    throw new OutOfBoundsException(sprintf('Scroll source [%s] is not registered.', $source));
                }
            }

            return $uri->sources;
        }

        return array_reverse($this->sources);
    }

    /** @param array<string, mixed> $metadata */
    private function matchesAttributes(array $metadata, array $criteria): bool
    {
        foreach ($criteria as $key => $expected) {
            if (!array_key_exists($key, $metadata) || $metadata[$key] !== $expected) {
                return false;
            }
        }

        return true;
    }

    private function identityKey(ScrollContract $scroll): string
    {
        return strtolower(sprintf(
            '%s:%s#%s',
            $this->scrollType($scroll)->value,
            trim($scroll->name, '/'),
            $scroll->version,
        ));
    }

    protected function validate(mixed $scroll): void
    {
        if (!$scroll instanceof ScrollContract) {
            throw new InvalidArgumentException('Invalid Scroll instance.');
        }

        if (!preg_match('/^[a-z0-9][a-z0-9_.\/-]*$/i', trim($scroll->name, '/'))) {
            throw new InvalidArgumentException(sprintf('Invalid Scroll logical path [%s].', $scroll->name));
        }
    }
}
