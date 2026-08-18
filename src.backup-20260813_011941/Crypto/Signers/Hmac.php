<?php

declare(strict_types=1);

namespace Codejitsu\Crypto\Signers;

use Codejitsu\Contracts\Crypto\Signer as SignerContract;
use InvalidArgumentException;

class Hmac implements SignerContract
{
    public function __construct(
        protected string $secretKey,
        protected string $algo = 'sha256'
    ) {}

    public function sign(string $data): string
    {
        return hash_hmac($this->algo, $data, $this->secretKey);
    }

    public function verify(string $data, string $signature): bool
    {
        $calculated = $this->sign($data);
        return hash_equals($calculated, $signature);
    }
}