<?php

declare(strict_types=1);

namespace Codejitsu\Graph;

final readonly class Node
{
    public function __construct(
        public string $id,
        public mixed $value = null,
        public array $metadata = [],
    ) {}
}
