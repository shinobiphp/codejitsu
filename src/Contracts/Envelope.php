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

    public string $data { get; set; }

    public Metadata $metadata { get; }

    public ?Seal $seal { get; set; }

    public ?Signature $signature { get; set; }

    public Codec $codec { get; }

    public bool $sealed { get; }

    public bool $signed { get; }
}
