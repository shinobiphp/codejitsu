<?php

declare(strict_types=1);

namespace Codejitsu\Contracts\Scrolls;

use Codejitsu\Contracts\Uri\Resolvable;
use Codejitsu\Enums\Scrolls\Types as ScrollTypes;

interface Scroll extends Resolvable
{
    public string $name { get; }

    public string $version { get; }

    public ScrollTypes|string $type { get; }

    /** @var array<string> */
    public array $tags { get; }

    /** @param array<string, mixed> $data */
    public function hydrate(array $data): static;

    /** @return array<string, mixed> */
    public function toArray(): array;

    public function ref(string $uri): Scroll;

    public function references(): array;

    public function __invoke(mixed ...$args): mixed;

    public function __call(string $method, array $args): mixed;

    public function __get(string $property): mixed;
}