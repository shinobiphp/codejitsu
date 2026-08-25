<?php

declare(strict_types=1);

namespace Codejitsu\Substrate;

use InvalidArgumentException;

final class Detector
{
    public function __construct(private readonly string $default = 'php')
    {
    }

    public function detect(string $source): string
    {
        $firstLine = trim(strtok($source, "\r\n") ?: '');

        if (str_starts_with($firstLine, '<?php')) {
            return 'php';
        }

        if (preg_match('/^#!\s*\/usr\/bin\/env\s+([a-zA-Z0-9._-]+)/', $firstLine, $matches) === 1) {
            return strtolower($matches[1]);
        }

        if (preg_match('/^#!\s*\/usr\/bin\/([a-zA-Z0-9._-]+)/', $firstLine, $matches) === 1) {
            return strtolower($matches[1]);
        }

        if ($this->default === '') {
            throw new InvalidArgumentException('Unable to detect a substrate.');
        }

        return $this->default;
    }
}
