<?php
declare(strict_types=1);

namespace Codejitsu\Contracts\Crypto;

use Codejitsu\Enums\Crypto\Algorithms as Algorithm;

interface Signer
{
    /**
     * Get the signature algorithm name (e.g., 'ed25519', 'hmac-sha256').
     */
    public function algorithm(): Algorithm;

    /**
     * Generate a signature for a raw payload.
     */
    public function sign(string $payload, string $secretKey): string;

    /**
     * Verify a signature against a raw payload.
     */
    public function verify(string $payload, string $signature, string $publicKey): bool;
}