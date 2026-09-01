<?php
declare(strict_types=1);
namespace Codejitsu\Packages;

use JsonException;

final class PackageCache
{
    public function read(string $path): ?array
    {
        if (!is_file($path)) return null;
        try {
            $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PackageException(sprintf('Package cache [%s] is malformed.', $path), previous: $exception);
        }
        if (!is_array($data) || ($data['format'] ?? null) !== 1 || !is_string($data['fingerprint'] ?? null) || !is_array($data['packages'] ?? null)) {
            throw new PackageException(sprintf('Package cache [%s] is malformed.', $path));
        }
        return $data;
    }

    public function write(string $path, array $compiled): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new PackageException(sprintf('Cannot create package cache directory [%s].', $directory));
        }
        $temporary = $path . '.tmp.' . bin2hex(random_bytes(6));
        try {
            $payload = json_encode($compiled, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        } catch (JsonException $exception) {
            throw new PackageException('Cannot encode package cache.', previous: $exception);
        }
        if (file_put_contents($temporary, $payload, LOCK_EX) === false || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new PackageException(sprintf('Cannot atomically write package cache [%s].', $path));
        }
        $this->removeLegacyPhpCache($path);
    }

    public function clear(string $path): void
    {
        if (is_file($path) && !unlink($path)) throw new PackageException(sprintf('Cannot clear package cache [%s].', $path));
        $this->removeLegacyPhpCache($path);
    }

    public function status(string $path): array
    {
        $data = $this->read($path);
        return $data === null ? ['exists' => false, 'format' => null, 'fingerprint' => null, 'packages' => 0] : [
            'exists' => true, 'format' => $data['format'], 'fingerprint' => $data['fingerprint'], 'packages' => count($data['packages']),
        ];
    }

    private function removeLegacyPhpCache(string $path): void
    {
        if (!str_ends_with($path, '.json')) return;
        $legacy = substr($path, 0, -5) . '.php';
        if (is_file($legacy) && !unlink($legacy)) {
            throw new PackageException(sprintf('Cannot remove legacy package cache [%s].', $legacy));
        }
    }
}
