<?php
declare(strict_types=1);

namespace Codejitsu\Crypto\KeyStores;

use Codejitsu\Crypto\Key;
use Codejitsu\Crypto\KeyStore as BaseKeyStore;
use Codejitsu\Enums\Crypto\KeyType;
use RuntimeException;

class Filesystem extends BaseKeyStore
{
    public function __construct(
        protected string $keysDirectory
    ) {
        $this->keysDirectory = rtrim($keysDirectory, '/\\');
    }

    public function get(string $keyId): Key
    {
        // 1. Return from in-memory cache if registered
        if (parent::has($keyId)) {
            return parent::get($keyId);
        }

        // 2. Resolve from filesystem directory
        $filePath = $this->keysDirectory . '/' . $keyId;

        // Auto-detect extension fallback (.pub, .key, or raw name)
        if (!file_exists($filePath)) {
            if (file_exists($filePath . '.pub')) {
                $filePath .= '.pub';
            } elseif (file_exists($filePath . '.key')) {
                $filePath .= '.key';
            } else {
                throw new RuntimeException("Key file for [{$keyId}] not found in [{$this->keysDirectory}].");
            }
        }

        // Infer type from file extension
        $type = str_ends_with($filePath, '.pub') ? KeyType::PUBLIC : KeyType::SECRET;

        $key = Key::fromFile($keyId, $filePath, $type);
        $this->add($key);

        return $key;
    }

    public function has(string $keyId): bool
    {
        if (parent::has($keyId)) {
            return true;
        }

        $base = $this->keysDirectory . '/' . $keyId;
        return file_exists($base) || file_exists($base . '.pub') || file_exists($base . '.key');
    }
}