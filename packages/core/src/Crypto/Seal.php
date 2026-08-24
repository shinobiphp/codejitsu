<?php

declare(strict_types=1);

namespace Codejitsu\Crypto;

use Codejitsu\Contracts\Crypto\Seal as SealContract;
use Codejitsu\Enums\Crypto\EncryptionAlgorithms as Algorithm;

final class Seal implements SealContract
{
    public function __construct(
        public Algorithm $algorithm,
        public string $keyId,
        public string $nonce,
        public string $tag,
    ) {}
}