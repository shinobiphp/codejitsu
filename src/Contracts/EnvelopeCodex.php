<?php

declare(strict_types=1);

namespace Codejitsu\Contracts;

use Codejitsu\Enums\Codecs;

interface EnvelopeCodex extends Codex
{
    public ?Codecs $codec { get; set; }

    public function loadEnvelope(
        Envelope $envelope,
        ?string $name = null,
    ): static;
}