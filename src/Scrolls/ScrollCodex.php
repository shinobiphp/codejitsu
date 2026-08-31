<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls;

use Codejitsu\EnvelopeCodex;
use Codejitsu\Contracts\Scrolls\Envelope as ScrollEnvelope;
use Codejitsu\Contracts\Scrolls\Scroll as ScrollContract;
use Codejitsu\Contracts\Scrolls\ScrollCodex as ScrollCodexContract;
use Codejitsu\Contracts\Scrolls\Store as StoreContract;
use Codejitsu\Contracts\Codec as CodecContract;
use Codejitsu\Contracts\Envelope as EnvelopeContract;
use Codejitsu\Enums\Codecs;
use Codejitsu\Enums\Scrolls\Types;
use Codejitsu\SubstrateRegistry;
use Codejitsu\Uri\Resolved;
use Codejitsu\Uri\Uri;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;

class ScrollCodex extends EnvelopeCodex implements ScrollCodexContract
{
    protected array $scrolls = [];

    /** @var array<string, array<string, IndexEntry>> */
    protected array $index = [];

    /** @var array<string, array<string, DiscoveredResource>> */
    protected array $resources = [];

    /** @var array<string> */
    protected array $sources = [];

    private SubstrateRegistry $substrates;
    private TypeRegistry $types;

    public function __construct(array $itemsOrEnvelopes = [], ?TypeRegistry $types = null)
    {
        parent::__construct(Codecs::NEON, $itemsOrEnvelopes);
        $this->substrates = new SubstrateRegistry();
        $this->types = $types ?? TypeRegistry::builtins();
        $this->registerSource('default');
    }

    public function types(): TypeRegistry
    {
        return $this->types;
    }

    public function substrates(): SubstrateRegistry
    {
        return $this->substrates;
    }

    public function withSubstrates(SubstrateRegistry $substrates): static
    {
        $this->substrates = $substrates;
        return $this;
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

        foreach ((new ScrollDiscovery($this->types))->discover($root) as $resource) {
            $this->registerResource($resource, $source);
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
        $this->index[$source][$key] = $this->indexEntryFromScroll($scroll, $source, $key);

        return $this;
    }

    public function query(array $criteria = []): array
    {
        $types = $criteria['type'] ?? null;
        $types = $types === null ? null : (array) $types;
        $types = $types === null ? null : array_map(
            fn (Types|string $type): string => $this->typeDefinition($type)->name,
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

        $name = isset($criteria['name']) ? strtolower(trim((string) $criteria['name'], '/')) : null;
        $path = isset($criteria['path']) ? trim(strtolower((string) $criteria['path']), '/') : null;
        $pathPrefix = isset($criteria['path_prefix'])
            ? trim(strtolower((string) $criteria['path_prefix']), '/')
            : null;
        $version = isset($criteria['version']) ? (string) $criteria['version'] : null;
        $tags = isset($criteria['tags']) ? array_map('strtolower', (array) $criteria['tags']) : [];
        $attributes = isset($criteria['attributes']) && is_array($criteria['attributes'])
            ? $criteria['attributes']
            : [];
        $references = isset($criteria['references'])
            ? array_map(static fn (mixed $uri): string => strtolower(trim((string) $uri)), (array) $criteria['references'])
            : [];
        $uri = isset($criteria['uri']) ? strtolower(trim((string) $criteria['uri'])) : null;

        $entries = [];
        foreach ($sources as $source) {
            foreach ($this->index[$source] ?? [] as $entry) {
                if ($types !== null && !in_array($entry->type, $types, true)) {
                    continue;
                }

                $entryName = strtolower(trim($entry->name, '/'));
                if ($name !== null && $entryName !== $name) {
                    continue;
                }

                if ($path !== null && $entryName !== $path) {
                    continue;
                }

                if ($pathPrefix !== null
                    && $entryName !== $pathPrefix
                    && !str_starts_with($entryName, $pathPrefix . '/')) {
                    continue;
                }

                if ($version !== null && $entry->version !== $version) {
                    continue;
                }

                if ($tags !== [] && array_diff($tags, array_map('strtolower', $entry->tags)) !== []) {
                    continue;
                }

                if ($attributes !== [] && !$this->matchesAttributes($entry->attributes, $attributes)) {
                    continue;
                }

                if ($references !== [] && array_diff($references, $entry->references) !== []) {
                    continue;
                }

                if ($uri !== null && strtolower((string) $entry->uri) !== $uri) {
                    continue;
                }

                $entries[] = $entry;
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
                $scroll = $this->hydrateFromEnvelope($envelope);
                if (!$scroll instanceof ScrollContract) {
                    throw new LogicException('Discovered Scroll envelope did not hydrate to a Scroll.');
                }
                $this->registerScroll($scroll, $source);
            }
        }

        return $this;
    }

    public function ofType(Types|string $type): static
    {
        $target = $this->typeDefinition($type);

        $result = new static(types: $this->types);
        foreach ($this->query(['type' => $target->name]) as $entry) {
            $result->registerScroll($this->resolve((string) $entry->uri), $entry->source);
        }
        return $result;
    }

    public function withTag(string $tag): static
    {
        $result = new static(types: $this->types);
        foreach ($this->query(['tags' => [$tag]]) as $entry) {
            $result->registerScroll($this->resolve((string) $entry->uri), $entry->source);
        }
        return $result;
    }

    public function withTags(array $tags): static
    {
        $result = new static(types: $this->types);
        foreach ($this->query(['tags' => $tags]) as $entry) {
            $result->registerScroll($this->resolve((string) $entry->uri), $entry->source);
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
            $matches = $this->query(['name' => $value]);

            return match (count($matches)) {
                1 => $this->hydrateEntry($matches[0]),
                0 => throw new OutOfBoundsException(sprintf('Scroll [%s] not found in Codex.', $uri)),
                default => throw new InvalidArgumentException(sprintf(
                    'Scroll name [%s] is ambiguous; resolve it by URI.',
                    $uri,
                )),
            };
        }

        $parsed = Uri::make($value);
        $type = $this->types->forScheme($parsed->type);
        if (!$type instanceof TypeDefinition) {
            throw new InvalidArgumentException(sprintf('Unknown Scroll URI scheme [%s].', $parsed->type));
        }

        $name = strtolower(trim($parsed->resourcePath, '/'));
        if ($name === '') {
            throw new InvalidArgumentException(sprintf('Scroll URI [%s] has no logical path.', $uri));
        }

        $version = $parsed->version;
        $sources = $this->sourceCascade($parsed);

        foreach ($sources as $source) {
            foreach ($this->index[$source] ?? [] as $entry) {
                if ($entry->type !== $type->name) {
                    continue;
                }

                if (strtolower(trim($entry->name, '/')) !== $name) {
                    continue;
                }

                if ($version === null || $entry->version === $version) {
                    return $this->hydrateEntry($entry);
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

        $typeName = $envelope->scrollType instanceof Types ? $envelope->scrollType->value : $envelope->scrollType;
        $scroll = $this->types->get($typeName)->make($envelope, $this->decodeEnvelope($envelope));
        return $scroll->bind($this);
    }

    protected function resolveCodec(EnvelopeContract $envelope): CodecContract
    {
        if ($envelope instanceof ScrollEnvelope) {
            $typeName = $envelope->scrollType instanceof Types ? $envelope->scrollType->value : $envelope->scrollType;
            return $this->types->get($typeName)->makeCodec();
        }
        return parent::resolveCodec($envelope);
    }

    protected function scrollType(ScrollContract $scroll): TypeDefinition
    {
        $type = $scroll->type;
        return $this->typeDefinition($type);
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

    /** @return array<string> */
    private function referenceUris(mixed $references): array
    {
        if (!is_array($references)) {
            return [];
        }

        $uris = [];
        foreach ($references as $reference) {
            $uri = is_string($reference) ? $reference : ($reference['uri'] ?? null);
            if (is_string($uri) && trim($uri) !== '') {
                $uris[] = strtolower(trim($uri));
            }
        }

        return array_values(array_unique($uris));
    }

    private function identityKey(ScrollContract $scroll): string
    {
        return strtolower(sprintf(
            '%s:%s#%s',
            $this->scrollType($scroll)->name,
            trim($scroll->name, '/'),
            $scroll->version,
        ));
    }

    private function registerResource(DiscoveredResource $resource, string $source): void
    {
        $key = $this->identityKeyFrom($resource->type->name, $resource->name, $resource->version);
        $this->resources[$source][$key] = $resource;
        $this->index[$source][$key] = new IndexEntry(
            $resource->type->name,
            $resource->name,
            $resource->version,
            $source,
            $resource->tags,
            $resource->attributes,
            Uri::make(sprintf('%s%s@%s#%s', $resource->type->scheme, $resource->name, $source, $resource->version)),
            $resource->references,
            $key,
            $resource->locator,
        );
    }

    private function indexEntryFromScroll(ScrollContract $scroll, string $source, string $key): IndexEntry
    {
        $type = $this->scrollType($scroll);
        $data = $scroll->toArray();
        $attributes = array_diff_key($data, array_flip(['name', 'type', 'version', 'tags']));
        return new IndexEntry(
            $type->name,
            $scroll->name,
            $scroll->version,
            $source,
            $scroll->tags,
            $attributes,
            Uri::make(sprintf('%s%s@%s#%s', $type->scheme, $scroll->name, $source, $scroll->version)),
            $this->referenceUris($attributes['references'] ?? []),
            $key,
        );
    }

    private function hydrateEntry(IndexEntry $entry): ScrollContract
    {
        if (($this->scrolls[$entry->source][$entry->key] ?? null) instanceof ScrollContract) {
            return $this->scrolls[$entry->source][$entry->key]->bind($this);
        }
        $resource = $this->resources[$entry->source][$entry->key] ?? null;
        if (!$resource instanceof DiscoveredResource) {
            throw new OutOfBoundsException(sprintf('Scroll [%s] has no registered loader.', $entry->uri));
        }
        $scroll = $resource->hydrate()->bind($this);
        $this->scrolls[$entry->source][$entry->key] = $scroll;
        $this->items[$entry->key] = $scroll;
        return $scroll;
    }

    private function identityKeyFrom(string $type, string $name, string $version): string
    {
        return strtolower(sprintf('%s:%s#%s', $type, trim($name, '/'), $version));
    }

    private function typeDefinition(Types|string $type): TypeDefinition
    {
        $name = $type instanceof Types ? $type->value : strtolower(trim($type));
        if (!$this->types->has($name)) {
            throw new InvalidArgumentException(sprintf('Unknown Scroll type [%s].', $name));
        }
        return $this->types->get($name);
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
