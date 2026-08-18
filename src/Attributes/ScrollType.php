<?php

declare(strict_types=1);

namespace Codejitsu\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class ScrollType
{
    public function __construct(
        public string $type, // e.g., 'capability', 'workflow', 'action'
        public array $schema = []
    ) {}
}