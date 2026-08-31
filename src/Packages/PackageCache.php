<?php
declare(strict_types=1);
namespace Codejitsu\Packages;

final class PackageCache
{
    public function read(string $path): ?array
    {
        if (!is_file($path)) return null;
        $data = require $path;
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
        $payload = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($compiled, true) . ";\n";
        if (file_put_contents($temporary, $payload, LOCK_EX) === false || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new PackageException(sprintf('Cannot atomically write package cache [%s].', $path));
        }
    }

    public function clear(string $path): void
    {
        if (is_file($path) && !unlink($path)) throw new PackageException(sprintf('Cannot clear package cache [%s].', $path));
    }

    public function status(string $path): array
    {
        $data = $this->read($path);
        return $data === null ? ['exists' => false, 'format' => null, 'fingerprint' => null, 'packages' => 0] : [
            'exists' => true, 'format' => $data['format'], 'fingerprint' => $data['fingerprint'], 'packages' => count($data['packages']),
        ];
    }
}
