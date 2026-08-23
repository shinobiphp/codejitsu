<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls;

use Codejitsu\Contracts\Scrolls\Scroll as ScrollContract;

final class Context implements ScrollContract
{
    public function __construct(
        private readonly string $id,
        private readonly string $uri,
        private readonly string $name,
        private readonly string $version,
        private readonly array $metadata,
        private readonly string $data,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public function data(): string
    {
        return $this->data;
    }
}
