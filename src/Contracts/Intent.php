<?php

declare(strict_types=1);

namespace Codejitsu\Contracts;

use Codejitsu\Identity\Identity;
use Codejitsu\Metadata;

interface Intent
{
    /** Resolved route or action key in the Codex */
    public string $action { get; }

    /** Input parameters, parsed body, or CLI positional arguments */
    public array $payload { get; }

    /** Contextual metadata object holding Identity and collection items */
    public Metadata $metadata { get; }

    /** Quick proxy to metadata's identity */
    public Identity $identity { get; }

    public function withPayload(array $payload): static;
}