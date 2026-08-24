<?php

declare(strict_types=1);

namespace Codejitsu\Crypto\Sealers;

use Codejitsu\Contracts\Crypto\Sealer as SealerContract;
use Codejitsu\Enums\Crypto\EncryptionAlgorithms;
use InvalidArgumentException;
use RuntimeException;

final class Sodium implements SealerContract
{
    protected const KEY_LENGTH =
        SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES;

    protected const NONCE_LENGTH =
        SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;

    protected const TAG_LENGTH =
        SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_ABYTES;

    protected const MINIMUM_PAYLOAD_LENGTH = 1;

    public function __construct()
    {
        if (
            !extension_loaded('sodium')
            || !function_exists(
                'sodium_crypto_aead_xchacha20poly1305_ietf_encrypt',
            )
        ) {
            throw new RuntimeException(
                'The Sodium extension is required for XChaCha20-Poly1305.',
            );
        }
    }

    public function algorithm(): EncryptionAlgorithms
    {
        return EncryptionAlgorithms::XCHACHA20_POLY1305;
    }

    public function isSealed(
        string $payload,
    ): bool {
        $raw = base64_decode(
            $payload,
            true,
        );

        if ($raw === false) {
            return false;
        }

        return strlen($raw) >= (
            self::NONCE_LENGTH
            + self::TAG_LENGTH
            + self::MINIMUM_PAYLOAD_LENGTH
        );
    }

    public function seal(
        string $payload,
        string $encryptionKey,
    ): string {
        $key = $this->normalizeKey(
            $encryptionKey,
        );

        $nonce = random_bytes(
            self::NONCE_LENGTH,
        );

        $encrypted = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $payload,
            '',
            $nonce,
            $key,
        );

        return base64_encode(
            $nonce . $encrypted,
        );
    }

    public function unseal(
        string $sealedPayload,
        string $decryptionKey,
    ): string {
        $key = $this->normalizeKey(
            $decryptionKey,
        );

        $raw = base64_decode(
            $sealedPayload,
            true,
        );

        if ($raw === false) {
            throw new RuntimeException(
                'Invalid base64 sealed payload.',
            );
        }

        $minimumLength =
            self::NONCE_LENGTH
            + self::TAG_LENGTH
            + self::MINIMUM_PAYLOAD_LENGTH;

        if (strlen($raw) < $minimumLength) {
            throw new RuntimeException(
                'Sealed payload is malformed or too short.',
            );
        }

        $nonce = substr(
            $raw,
            0,
            self::NONCE_LENGTH,
        );

        $encrypted = substr(
            $raw,
            self::NONCE_LENGTH,
        );

        $decrypted =
            sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
                $encrypted,
                '',
                $nonce,
                $key,
            );

        if ($decrypted === false) {
            throw new RuntimeException(
                'Failed to unseal payload. Authentication failed or the encryption key is invalid.',
            );
        }

        return $decrypted;
    }

    protected function normalizeKey(
        string $encryptionKey,
    ): string {
        if ($encryptionKey === '') {
            throw new InvalidArgumentException(
                'Encryption key cannot be empty.',
            );
        }

        return hash(
            'sha256',
            $encryptionKey,
            true,
        );
    }
}