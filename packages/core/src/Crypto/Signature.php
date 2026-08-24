<?php

declare(strict_types=1);

namespace Codejitsu\Crypto;

use Codejitsu\Contracts\Crypto\Signature as SignatureContract;
use Codejitsu\Enums\Crypto\SignatureAlgorithms as Algorithm;

final class Signature implements SignatureContract
{
    public function __construct(
        public Algorithm $algorithm,
        public string $keyId,
        public string $value,
    ) {}
}