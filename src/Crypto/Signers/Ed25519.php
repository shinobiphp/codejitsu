<?php

declare(strict_types=1);

namespace Codejitsu\Crypto\Signers;

use Codejitsu\Contracts\Crypto\Signer as SignerContract;
use Codejitsu\Enums\Crypto\SignatureAlgorithms;
use InvalidArgumentException;

final class Ed25519 implements SignerContract
{
    public function algorithm(): SignatureAlgorithms
    {
        return SignatureAlgorithms::ED25519;
    }

    public function sign(string $payload, string $secretKey): string
    {
        if (strlen($secretKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new InvalidArgumentException('Ed25519 secret key must be 64 bytes.');
        }

        return base64_encode(sodium_crypto_sign_detached($payload, $secretKey));
    }

    public function verify(string $payload, string $signature, string $publicKey): bool
    {
        if (strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new InvalidArgumentException('Ed25519 public key must be 32 bytes.');
        }

        $decoded = base64_decode($signature, true);
        if ($decoded === false || strlen($decoded) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return false;
        }

        return sodium_crypto_sign_verify_detached($decoded, $payload, $publicKey);
    }
}
