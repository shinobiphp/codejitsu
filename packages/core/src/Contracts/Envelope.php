<?php

declare(strict_types=1);

namespace Codejitsu\Contracts;

use Codejitsu\Contracts\Crypto\Seal;
use Codejitsu\Contracts\Crypto\Signature;
use Codejitsu\Enums\Codecs as Codec;
use Codejitsu\Metadata;

interface Envelope
{
    public string $name { get; }
    
    public string $data { get; }

    public Metadata $metadata { get; }

    public ?Seal $seal { get; }

    public ?Signature $signature { get; }

    public Codec $codec { get; }

    public bool $sealed { get; }

    public bool $signed { get; }
}