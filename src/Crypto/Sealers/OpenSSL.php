<?php

declare(strict_types=1);

namespace Codejitsu\Crypto\Sealers;

use Codejitsu\Contracts\Crypto\Sealer as SealerContract;
use Codejitsu\Enums\Crypto\EncryptionAlgorithms;
use InvalidArgumentException;
use RuntimeException;

final class OpenSSL implements SealerContract
{
    protected const CIPHER = 'aes-256-gcm';

    protected const TAG_LENGTH = 16;

    protected const MINIMUM_PAYLOAD_LENGTH = 1;

    public function __construct(
        protected string $cipher = self::CIPHER,
    ) {
        if (
            openssl_cipher_iv_length($this->cipher) === false
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unsupported OpenSSL cipher [%s].',
                    $this->cipher,
                ),
            );
        }
    }

    public function algorithm(): EncryptionAlgorithms
    {
        return EncryptionAlgorithms::AES_256_GCM;
    }

    public function isSealed(string $payload): bool
    {
        $raw = base64_decode($payload, true);

        if ($raw === false) {
            return false;
        }

        $ivLength = openssl_cipher_iv_length(
            $this->cipher,
        );

        if ($ivLength === false) {
            return false;
        }

        return strlen($raw) >= (
            $ivLength
            + self::TAG_LENGTH
            + self::MINIMUM_PAYLOAD_LENGTH
        );
    }

    public function seal(
        string $payload,
        string $encryptionKey,
    ): string {
        $key = $this->normalizeKey($encryptionKey);

        $ivLength = openssl_cipher_iv_length(
            $this->cipher,
        );

        if ($ivLength === false) {
            throw new RuntimeException(
                sprintf(
                    'Unable to determine IV length for cipher [%s].',
                    $this->cipher,
                ),
            );
        }

        $iv = random_bytes($ivLength);
        $tag = '';

        $encrypted = openssl_encrypt(
            $payload,
            $this->cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH,
        );

        if ($encrypted === false) {
            throw new RuntimeException(
                'Failed to seal payload using OpenSSL.',
            );
        }

        return base64_encode(
            $iv
            . $tag
            . $encrypted,
        );
    }

    public function unseal(
        string $sealedPayload,
        string $decryptionKey,
    ): string {
        $key = $this->normalizeKey($decryptionKey);

        $raw = base64_decode(
            $sealedPayload,
            true,
        );

        if ($raw === false) {
            throw new RuntimeException(
                'Invalid base64 sealed payload.',
            );
        }

        $ivLength = openssl_cipher_iv_length(
            $this->cipher,
        );

        if ($ivLength === false) {
            throw new RuntimeException(
                sprintf(
                    'Unable to determine IV length for cipher [%s].',
                    $this->cipher,
                ),
            );
        }

        $minimumLength =
            $ivLength
            + self::TAG_LENGTH
            + self::MINIMUM_PAYLOAD_LENGTH;

        if (strlen($raw) < $minimumLength) {
            throw new RuntimeException(
                'Sealed payload is malformed or too short.',
            );
        }

        $iv = substr(
            $raw,
            0,
            $ivLength,
        );

        $tag = substr(
            $raw,
            $ivLength,
            self::TAG_LENGTH,
        );

        $encrypted = substr(
            $raw,
            $ivLength + self::TAG_LENGTH,
        );

        $decrypted = openssl_decrypt(
            $encrypted,
            $this->cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
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