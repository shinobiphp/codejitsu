<?php

declare(strict_types=1);

namespace Codejitsu\Crypto\Sealers;

use Codejitsu\Contracts\Crypto\Sealer as SealerContract;

use RuntimeException;

class OpenSSL implements SealerContract
{
    protected string $cipher = 'aes-256-gcm';

    public function __construct(
        protected string $masterKey
    ) {
        if (mb_strlen($this->masterKey, '8bit') !== 32) {
            // Ensure 256-bit key requirement for AES-256-GCM
            $this->masterKey = hash('sha256', $this->masterKey, true);
        }
    }

    public function seal(string $data): string
    {
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = random_bytes($ivLength);
        $tag = '';

        $encrypted = openssl_encrypt(
            $data,
            $this->cipher,
            $this->masterKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($encrypted === false) {
            throw new RuntimeException("Failed to seal data using OpenSSL.");
        }

        // Pack IV, Auth Tag, and Ciphertext together into a single storable binary blob
        return base64_encode($iv . $tag . $encrypted);
    }

    public function unseal(string $sealedData): string
    {
        $raw = base64_decode($sealedData, true);
        if ($raw === false) {
            throw new RuntimeException("Failed to decode base64 sealed data.");
        }

        $ivLength = openssl_cipher_iv_length($this->cipher);
        if (strlen($raw) < $ivLength + 16) {
            throw new RuntimeException("Sealed data payload is malformed or too short.");
        }

        $iv = substr($raw, 0, $ivLength);
        $tag = substr($raw, $ivLength, 16);
        $encrypted = substr($raw, $ivLength + 16);

        $decrypted = openssl_decrypt(
            $encrypted,
            $this->cipher,
            $this->masterKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            throw new RuntimeException("Failed to unseal data. Authentication tag verification failed or key is invalid.");
        }

        return $decrypted;
    }
}