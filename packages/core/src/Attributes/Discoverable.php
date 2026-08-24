<?php

declare(strict_types=1);

namespace Codejitsu\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class Discoverable
{
    /** @var array<string> */
    public array $tags;

    public function __construct(
        public ?string $group = null,
        public ?string $alias = null,
        string|array $tags = [],
        public array $meta = []
    ) {
        $this->tags = (array) $tags;
    }
}