<?php
declare(strict_types=1);

namespace Codejitsu\Config\Stores;

use Codejitsu\Config\FileConfig;

use Codejitsu\Contracts\Config\Config as ConfigContract;
use Codejitsu\Contracts\Config\Store as StoreContract;

use InvalidArgumentException;
use LogicException;

class File implements StoreContract
{
    public function __construct(protected string $filePath) {}

    public static function make(mixed ...$params): static
    {
        $filePath = $params[0] ?? throw new InvalidArgumentException("File store requires a file path.");
        return new static($filePath);
    }

    public function load(): ConfigContract
    {
        $data = [];
        if (file_exists($this->filePath)) {
            $content = file_get_contents($this->filePath);
            $data = json_decode($content, true) ?? [];
        }

        return new FileConfig($this, $data);
    }

    public function save(array $data): void
    {
        $directory = dirname($this->filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $result = file_put_contents(
            $this->filePath, 
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        if ($result === false) {
            throw new LogicException("Failed to write configuration data to path: {$this->filePath}");
        }
    }
}