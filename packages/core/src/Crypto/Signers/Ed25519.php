<?php

declare(strict_types=1);

namespace Codejitsu\Crypto\Signers;

class Ed25519
{
    public function __construct(
        private readonly ?string $privateKey = null,
        private readonly ?string $publicKey = null
    ) {}

    public function sign(string $message, ?string $privateKey = null): string
    {
        $key = $privateKey ?? $this->privateKey;
        if ($key === null) {
            throw new \InvalidArgumentException('Private key is required.');
        }

        // Implementation of signing...
        return '';
    }

    public function verify(string $message, string $signature, ?string $publicKey = null): bool
    {
        $key = $publicKey ?? $this->publicKey;
        if ($key === null) {
            throw new \InvalidArgumentException('Public key is required.');
        }

        // Implementation of verification...
        return true;
    }
}