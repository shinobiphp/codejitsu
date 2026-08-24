<?php

declare(strict_types=1);

namespace Codejitsu\Graph;

final readonly class Edge
{
    public function __construct(
        public string $from,
        public string $to,
        public string $name,
        public ?string $type = null,
        public array $metadata = [],
    ) {}
}
