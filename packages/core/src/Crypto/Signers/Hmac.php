<?php

declare(strict_types=1);

namespace Codejitsu\Crypto\Signers;

use Codejitsu\Contracts\Crypto\Signer as SignerContract;
use Codejitsu\Enums\Crypto\SignatureAlgorithms;

final class Hmac implements SignerContract
{
    public function __construct(
        protected string $algo = 'sha256',
    ) {}

    public function algorithm(): SignatureAlgorithms
    {
        return SignatureAlgorithms::HMAC_SHA256;
    }

    public function sign(
        string $payload,
        string $secretKey,
    ): string {
        if ($secretKey === '') {
            throw new \InvalidArgumentException(
                'HMAC signing key cannot be empty.',
            );
        }

        return hash_hmac(
            $this->algo,
            $payload,
            $secretKey,
        );
    }

    public function verify(
        string $payload,
        string $signature,
        string $publicKey,
    ): bool {
        if ($publicKey === '' || $signature === '') {
            return false;
        }

        return hash_equals(
            $this->sign($payload, $publicKey),
            $signature,
        );
    }
}