<?php

declare(strict_types=1);

namespace Codejitsu\IO;

use Codejitsu\Contracts\Intent;
use Codejitsu\Identity\Identity;
use Codejitsu\Metadata;

final readonly class HttpIntent implements Intent
{
    public Identity $identity {
        get => $this->metadata->identity();
    }

    public function __construct(
        public string $method,
        public string $path,
        public string $action,
        public array $payload = [],
        public ?Metadata $metadata = null,
        public array $headers = []
    ) {}

    public function withPayload(array $payload): static
    {
        return new self($this->method, $this->path, $this->action, $payload, $this->metadata, $this->headers);
    }
}