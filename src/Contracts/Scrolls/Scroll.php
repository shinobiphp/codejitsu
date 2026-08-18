<?php

declare(strict_types=1);

namespace Codejitsu\Contracts\Scrolls;

use Codejitsu\Enums\Scrolls\Types as ScrollTypes;

interface Scroll
{
    /**
     * Unique logical name of the Scroll.
     */
    public string $name { get; }

    /**
     * Semantic version of the Scroll.
     */
    public string $version { get; }

    /**
     * Scroll classification.
     */
    public ScrollTypes|string $type { get; }

    /**
     * Tags associated with this Scroll.
     *
     * @var array<string>
     */
    public array $tags { get; }

    /**
     * Hydrate Scroll state from decoded payload data.
     */
    public function hydrate(array $data): static;

    /**
     * Export the Scroll state.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Execute the Scroll.
     */
    public function __invoke(mixed ...$args): mixed;

    /**
     * Execute a named action on the Scroll.
     */
    public function __call(string $method, array $args): mixed;

    /**
     * Dynamic property access.
     */
    public function __get(string $property): mixed;
}