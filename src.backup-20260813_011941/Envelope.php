<?php

declare(strict_types=1);

namespace Codejitsu;

use Codejitsu\Contracts\Envelope as EnvelopeContract;
use Codejitsu\Contracts\Crypto\Seal as SealContract;
use Codejitsu\Contracts\Crypto\Signature as SignatureContract;

use Codejitsu\Enums\Codecs as Codec;

use Codejitsu\Metadata;

final class Envelope implements EnvelopeContract
{
    public function __construct(
        public string $data,
        public Metadata $metadata,
        public ?SealContract $seal = null,
        public ?SignatureContract $signature = null,
        public Codec $codec = Codec::NEON
    ) {}

    public bool $sealed {
        get => $this->seal !== null;
    }

    public bool $signed {
        get => $this->signature !== null;
    }
}