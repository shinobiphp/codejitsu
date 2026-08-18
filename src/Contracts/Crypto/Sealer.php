<?php

declare(strict_types=1);

namespace Codejitsu\Contracts\Crypto;

use Codejitsu\Enums\Crypto\EncryptionAlgorithms as Algorithm;

interface Sealer
{
    /**
     * Get the encryption algorithm used by this sealer.
     */
    public function algorithm(): Algorithm;

    /**
     * Determine whether the payload is in this sealer's format.
     */
    public function isSealed(string $payload): bool;

    /**
     * Encrypt a raw string payload.
     *
     * The encryption key is supplied by the caller so that the sealer
     * remains stateless and reusable.
     */
    public function seal(
        string $payload,
        string $encryptionKey,
    ): string;

    /**
     * Decrypt an encrypted string payload.
     *
     * The decryption key is supplied by the caller so that the sealer
     * remains stateless and reusable.
     */
    public function unseal(
        string $sealedPayload,
        string $decryptionKey,
    ): string;
}