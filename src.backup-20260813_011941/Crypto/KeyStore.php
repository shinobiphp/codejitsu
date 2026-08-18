<?php

declare(strict_types=1);

namespace Codejitsu\Crypto;

use Codejitsu\Contracts\Crypto\KeyStore as KeyStoreContract;
use Codejitsu\Crypto\Key;

use RuntimeException;

class KeyStore implements KeyStoreContract
{
    /** @var array<string, Key> */
    protected array $keys = [];

    public function add(Key $key): static
    {
        $this->keys[$key->id] = $key;
        return $this;
    }

    public function get(string $keyId): Key
    {
        return $this->keys[$keyId] 
            ?? throw new RuntimeException("Key [{$keyId}] not registered in KeyStore.");
    }

    public function has(string $keyId): bool
    {
        return isset($this->keys[$keyId]);
    }
}