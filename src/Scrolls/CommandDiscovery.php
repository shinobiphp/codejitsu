<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls;

use Codejitsu\Contracts\Codec;
use Codejitsu\Enums\Codecs;
use Codejitsu\Enums\Scrolls\Types;
use Codejitsu\Scrolls\Types\Command;
use RuntimeException;

final class CommandDiscovery
{
    private function __construct(private readonly Codec $codec) {}

    public static function fromDirectory(string $directory, ScrollCodex $codex): int
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $discovery = new self(Codecs::NEON->make());
        $count = 0;

        $files = glob(rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . '*.neon');
        if ($files === false) {
            return 0;
        }

        foreach ($files as $file) {
            $data = $discovery->read($file);
            $command = Types::COMMAND->make(null, $data);

            if (!$command instanceof Command) {
                throw new RuntimeException(sprintf('Command resource [%s] did not hydrate as a Command Scroll.', $file));
            }

            $codex->registerScroll($command);
            ++$count;
        }

        return $count;
    }

    /** @return array<string, mixed> */
    private function read(string $file): array
    {
        $payload = file_get_contents($file);
        if ($payload === false) {
            throw new RuntimeException(sprintf('Unable to read command Scroll [%s].', $file));
        }

        return $this->codec->decode($payload);
    }
}