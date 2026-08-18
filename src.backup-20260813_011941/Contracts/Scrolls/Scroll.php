<?php

declare(strict_types=1);

namespace Codejitsu\Contracts\Scrolls;

use Codejitsu\Enums\ScrollTypes;

interface Scroll
{
    /**
     * Get the unique name or identifier for this Scroll.
     */
    public string $name { get; }

    /**
     * Get the version string of this Scroll.
     */
    public string $version { get; }

    /**
     * Scroll classification type.
     */
    public ScrollTypes|string $type { get; }

    /**
     * Hydrate state from an associative array into this scroll.
     */
    public function hydrate(array $data): static;

    /**
     * Export internal state as an array representation.
     */
    public function toArray(): array;

    /**
     * Callable execution entry point.
     */
    public function __invoke(mixed ...$args): mixed;

    /**
     * Dynamic action execution route.
     */
    public function __call(string $method, array $args): mixed;

    /**
     * Dynamic property getter.
     */
    public function __get(string $property): mixed;
}