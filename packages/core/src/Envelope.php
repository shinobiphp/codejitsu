<?php

declare(strict_types=1);

namespace Codejitsu;

use Codejitsu\Contracts\Crypto\Seal as SealContract;
use Codejitsu\Contracts\Crypto\Signature as SignatureContract;
use Codejitsu\Contracts\Envelope as EnvelopeContract;
use Codejitsu\Enums\Codecs as Codec;

class Envelope implements EnvelopeContract
{
    public function __construct(
        public string $name,
        public string $data,
        public Metadata $metadata,
        public ?SealContract $seal = null,
        public ?SignatureContract $signature = null,
        public Codec $codec = Codec::NEON,
    ) {}

    public bool $sealed {
        get => $this->seal !== null;
    }

    public bool $signed {
        get => $this->signature !== null;
    }
}   