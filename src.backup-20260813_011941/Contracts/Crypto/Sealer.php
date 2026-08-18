<?php
declare(strict_types=1);

namespace Codejitsu\Contracts\Crypto;

use Codejitsu\Enums\Crypto\Algorithms as Algorithm;

interface Sealer
{
    /**
     * Get the encryption algorithm name (e.g., 'aes-256-gcm', 'sodium-xchacha20poly1305').
     */
    public function algorithm(): Algorithm;

    public function isSealed(string $payload): bool;

    /**
     * Encrypt (seal) a raw string payload.
     */
    public function seal(string $payload, string $encryptionKey): string;

    /**
     * Decrypt (unseal) an encrypted string payload.
     */
    public function unseal(string $sealedPayload, string $decryptionKey): string;
}