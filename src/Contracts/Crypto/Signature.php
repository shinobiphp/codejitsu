<?php
declare(strict_types=1);

namespace Codejitsu\Contracts\Crypto;

use Codejitsu\Enums\Crypto\SignatureAlgorithms as Algorithm;

interface Signature
{
    public Algorithm $algorithm { get; }
    public string $keyId { get; }
    public string $value { get; }
}