<?php
declare(strict_types=1);

namespace Codejitsu\Contracts\Crypto;

use Codejitsu\Crypto\Key;

interface KeyStore
{
    public function add(Key $key): static;
    public function get(string $keyId): Key;
    public function has(string $keyId): bool;
}