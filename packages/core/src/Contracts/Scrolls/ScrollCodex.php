<?php

declare(strict_types=1);

namespace Codejitsu\Contracts\Scrolls;

use Codejitsu\Contracts\EnvelopeCodex;
use Codejitsu\Enums\Scrolls\Types;
use Codejitsu\Scrolls\IndexEntry;

interface ScrollCodex extends EnvelopeCodex
{
    public function registerSource(string $source): static;

    public function registerScroll(Scroll $scroll, ?string $source = null): static;

    /**
     * Query indexed Scroll metadata without hydrating additional resources.
     *
     * @param array<string, mixed> $criteria
     * @return array<IndexEntry>
     */
    public function query(array $criteria = []): array;

    /**
     * Return a Codex containing only Scrolls of the specified type.
     */
    public function ofType(Types|string $type): static;

    /**
     * Return a Codex containing Scrolls having the specified tag.
     */
    public function withTag(string $tag): static;

    /**
     * Return a Codex containing Scrolls having all specified tags.
     *
     * @param array<string> $tags
     */
    public function withTags(array $tags): static;

    /**
     * Resolve a URI or local Scroll target.
     */
    public function resolve(string $uri): mixed;

    /**
     * Explicitly invoke a typed Scroll.
     */
    public function invoke(
        Types|string $type,
        string $name,
        mixed ...$args,
    ): mixed;

    public function __get(string $name): mixed;

    public function __isset(string $name): bool;

    public function __call(string $method, array $args): mixed;

    public function __invoke(string $target, mixed ...$args): mixed;
}
