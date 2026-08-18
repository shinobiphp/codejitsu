<?php

declare(strict_types=1);

namespace Codejitsu\IO;

use Codejitsu\Contracts\Intent;
use Codejitsu\Identity\Identity;
use Codejitsu\Metadata;

final readonly class CliIntent implements Intent
{
    public Identity $identity {
        get => $this->metadata->identity();
    }

    public function __construct(
        public string $action,
        public array $payload = [],
        public ?Metadata $metadata = null
    ) {}

    public function withPayload(array $payload): static
    {
        return new self($this->action, $payload, $this->metadata);
    }
}