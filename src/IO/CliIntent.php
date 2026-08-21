<?php

declare(strict_types=1);

namespace Codejitsu\IO;

use Codejitsu\Contracts\Intent;
use Codejitsu\Identity\Identity;
use Codejitsu\Metadata;

final class CliIntent implements Intent
{
    public Identity $identity {
        get => $this->metadata->identity();
    }

    public function __construct(
        public readonly string $action,
        public readonly array $payload = [],
        public readonly Metadata $metadata,
    ) {}

    public function withPayload(array $payload): static
    {
        return new self($this->action, $payload, $this->metadata);
    }
}
