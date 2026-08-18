<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls\Stores;

use Codejitsu\Contracts\Scrolls\Envelope as EnvelopeContract;
use Codejitsu\Contracts\Scrolls\Store as StoreContract;
use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use Codejitsu\Scrolls\Envelope;

class Filesystem implements StoreContract
{
    public function __construct(
        protected string $baseDir,
        protected ?string $extension = null
    ) {
        $this->baseDir = rtrim($baseDir, '/\\');
    }

    public function getDirectory(ScrollTypes $type): string
    {
        $subDir = method_exists($type, 'plural') ? $type->plural() : $type->value;
        return $this->baseDir . DIRECTORY_SEPARATOR . strtolower($subDir);
    }

    /**
     * Resolve the file extension to use for the given ScrollType.
     */
    public function resolveExtension(ScrollTypes $type): string
    {
        if ($this->extension !== null) {
            return $this->extension;
        }

        return method_exists($type, 'extension') ? $type->extension() : 'json';
    }

    public function has(ScrollTypes $type, string $name): bool
    {
        $filePath = $this->resolvePath($type, $name);
        return file_exists($filePath);
    }

    public function get(ScrollTypes $type, string $name): ?EnvelopeContract
    {
        $filePath = $this->resolvePath($type, $name);

        if (!file_exists($filePath)) {
            return null;
        }

        return Envelope::fromFile($filePath);
    }

    public function all(ScrollTypes $type): array
    {
        $dir = $this->getDirectory($type);
        if (!is_dir($dir)) {
            return [];
        }

        $envelopes = [];
        $ext = $this->resolveExtension($type);
        $files = glob($dir . DIRECTORY_SEPARATOR . "*." . $ext) ?: [];

        foreach ($files as $file) {
            $name = strtolower(pathinfo($file, PATHINFO_FILENAME));
            $envelopes[$name] = Envelope::fromFile($file);
        }

        return $envelopes;
    }

    protected function resolvePath(ScrollTypes $type, string $name): string
    {
        $dir = $this->getDirectory($type);
        $ext = $this->resolveExtension($type);

        return $dir . DIRECTORY_SEPARATOR . strtolower($name) . '.' . $ext;
    }
}