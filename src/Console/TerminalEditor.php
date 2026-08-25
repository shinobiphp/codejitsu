<?php

declare(strict_types=1);

namespace Codejitsu\Console;

use RuntimeException;

final class TerminalEditor implements Editor
{
    public function __construct(private readonly ?string $command = null)
    {
    }

    public function edit(string $initial = ''): string
    {
        $editor = trim($this->command ?? ($_ENV['EDITOR'] ?? $_ENV['VISUAL'] ?? getenv('EDITOR') ?: getenv('VISUAL') ?: 'nano'));
        $path = tempnam(sys_get_temp_dir(), 'codejitsu-scroll-');
        if ($path === false) {
            throw new RuntimeException('Unable to create temporary editor file.');
        }

        try {
            if (file_put_contents($path, $initial, LOCK_EX) === false) {
                throw new RuntimeException('Unable to initialize temporary editor file.');
            }

            passthru($editor . ' ' . escapeshellarg($path), $exitCode);
            if ($exitCode !== 0) {
                throw new RuntimeException(sprintf('Editor exited with status %d.', $exitCode));
            }

            $contents = file_get_contents($path);
            if ($contents === false || trim($contents) === '') {
                throw new RuntimeException('Editor returned empty Scroll contents.');
            }

            return rtrim($contents, "\r\n") . "\n";
        } finally {
            @unlink($path);
        }
    }
}
