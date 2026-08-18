<?php

declare(strict_types=1);

namespace Codejitsu\Discovery\Cache;

use Codejitsu\Contracts\Discovery\Cache as CacheContract;

final class FileCache implements CacheContract
{
    public function __construct(
        private string $cacheDir,
        private bool $debug = false
    ) {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function get(string $cacheKey): ?array
    {
        if ($this->debug) {
            return null; // Skip cache in debug/development mode
        }

        $file = $this->getFilePath($cacheKey);
        if (!file_exists($file)) {
            return null;
        }

        /** @var array<string> $classes */
        $classes = require $file;
        return is_array($classes) ? $classes : null;
    }

    public function put(string $cacheKey, array $classes, int $ttl = 0): void
    {
        $file = $this->getFilePath($cacheKey);
        $exported = var_export($classes, true);

        $content = "<?php\n\n// Auto-generated Codejitsu Discovery Cache\nreturn {$exported};\n";
        file_put_contents($file, $content, LOCK_EX);

        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($file, true);
        }
    }

    public function forget(string $cacheKey): void
    {
        $file = $this->getFilePath($cacheKey);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    private function getFilePath(string $cacheKey): string
    {
        return $this->cacheDir . '/' . md5($cacheKey) . '.discovery.php';
    }
}