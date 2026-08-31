<?php

declare(strict_types=1);

namespace Codejitsu\Discovery;

use Codejitsu\Enums\Scrolls\Types as ScrollTypes;

final readonly class DiscoveredScroll
{
    public function __construct(
        public string $name,
        public ScrollTypes|string $type,
        public string $path,
        public string $extension,
    ) {}
}
