<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls;

use Codejitsu\Uri\Uri;

final readonly class IndexEntry
{
    /**
     * @param array<string> $tags
     * @param array<string, mixed> $attributes
     * @param array<string> $references
     */
    public function __construct(
        public string $type,
        public string $name,
        public string $version,
        public string $source,
        public array $tags,
        public array $attributes,
        public Uri $uri,
        public array $references = [],
    ) {}
}
