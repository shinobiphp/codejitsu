<?php
declare(strict_types=1);

namespace Codejitsu\Crypto;

use Codejitsu\Enums\Crypto\KeyType;

readonly class Key
{
    public function __construct(
        public string $id,
        public string $contents,
        public KeyType $type = KeyType::SECRET,
        public ?string $passphrase = null,
    ) {}

    public static function secret(string $id, string $secret): self
    {
        return new self($id, $secret, KeyType::SECRET);
    }

    public static function fromFile(string $id, string $filePath, KeyType $type, ?string $passphrase = null): self
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Key file not found at [{$filePath}].");
        }

        return new self($id, file_get_contents($filePath), $type, $passphrase);
    }
}