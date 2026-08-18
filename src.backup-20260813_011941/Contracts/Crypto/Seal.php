<?php
declare(strict_types=1);

namespace Codejitsu\Contracts\Crypto;

use Codejitsu\Enums\Crypto\EncryptionAlgorithms as Algorithm;

interface Seal
{
    public Algorithm $algorithm { get; }
    public string $keyId { get; }
    public string $nonce { get; }
    public string $tag { get; }
}