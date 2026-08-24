<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls\Stores;

use Codejitsu\Contracts\Scrolls\Envelope as EnvelopeContract;
use Codejitsu\Contracts\Scrolls\Store as StoreContract;
use Codejitsu\Enums\Codecs;
use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use Codejitsu\Scrolls\Envelope;
use Codejitsu\Metadata;
use RuntimeException;

final class Filesystem implements StoreContract
{
    public function __construct(
        protected string $baseDir,
        protected ?string $extension = null,
        protected Codecs $codec = Codecs::NEON,
    ) {
        $this->baseDir = rtrim(
            $baseDir,
            '/\\',
        );
    }

    public function getDirectory(
        ScrollTypes $type,
    ): string {
        $subDir = method_exists(
            $type,
            'plural',
        )
            ? $type->plural()
            : $type->value;

        return $this->baseDir
            . DIRECTORY_SEPARATOR
            . strtolower($subDir);
    }

    public function resolveExtension(
        ScrollTypes $type,
    ): string {
        if ($this->extension !== null) {
            return ltrim(
                $this->extension,
                '.',
            );
        }

        return match ($type) {
            ScrollTypes::APP,
            ScrollTypes::CAPABILITY,
            ScrollTypes::CONFIG,
            ScrollTypes::KATA,
            ScrollTypes::SCHEMA,
            ScrollTypes::SKILL => 'neon',
        };
    }

    public function has(
        ScrollTypes $type,
        string $name,
    ): bool {
        return is_file(
            $this->getPath(
                $type,
                $name,
            ),
        );
    }

    public function get(
        ScrollTypes $type,
        string $name,
    ): ?EnvelopeContract {
        $path = $this->getPath(
            $type,
            $name,
        );

        if (!is_file($path)) {
            return null;
        }

        $payload = file_get_contents($path);

        if ($payload === false) {
            throw new RuntimeException(
                sprintf(
                    'Unable to read Scroll envelope [%s].',
                    $path,
                ),
            );
        }

        return $this->decodeEnvelope(
            $payload,
            $type,
            $name,
        );
    }

    /**
     * @return array<string, EnvelopeContract>
     */
    public function all(
        ScrollTypes $type,
    ): array {
        $directory = $this->getDirectory($type);

        if (!is_dir($directory)) {
            return [];
        }

        $extension = $this->resolveExtension($type);

        $files = glob(
            $directory
                . DIRECTORY_SEPARATOR
                . '*.'
                . $extension,
        );

        if ($files === false) {
            return [];
        }

        $result = [];

        foreach ($files as $path) {
            $name = pathinfo(
                $path,
                PATHINFO_FILENAME,
            );

            $envelope = $this->get(
                $type,
                $name,
            );

            if ($envelope !== null) {
                $result[$name] = $envelope;
            }
        }

        return $result;
    }

    public function save(
        ScrollTypes $type,
        string $name,
        EnvelopeContract $envelope,
    ): void {
        $directory = $this->getDirectory($type);

        if (
            !is_dir($directory)
            && !mkdir(
                $directory,
                0755,
                true,
            )
            && !is_dir($directory)
        ) {
            throw new RuntimeException(
                sprintf(
                    'Unable to create Scroll directory [%s].',
                    $directory,
                ),
            );
        }

        $path = $this->getPath(
            $type,
            $name,
        );

        $payload = $this->encodeEnvelope(
            $envelope,
        );

        if (
            file_put_contents(
                $path,
                $payload,
                LOCK_EX,
            ) === false
        ) {
            throw new RuntimeException(
                sprintf(
                    'Unable to write Scroll envelope [%s].',
                    $path,
                ),
            );
        }
    }

    protected function encodeEnvelope(
        EnvelopeContract $envelope,
    ): string {
        $codec = $this->codec->make();

        $data = [
            'name' => $envelope->name,
            'version' => $envelope->version,
            'scrollType' => $envelope->scrollType->value,
            'data' => $envelope->data,
            'metadata' => $envelope->metadata,
            'codec' => $envelope->codec->value,
            'seal' => $envelope->seal,
            'signature' => $envelope->signature,
        ];

        return $codec->encode($data);
    }

    protected function decodeEnvelope(
        string $payload,
        ScrollTypes $expectedType,
        string $expectedName,
    ): EnvelopeContract {
        $codec = $this->codec->make();

        $decoded = $codec->decode($payload);

        if (!is_array($decoded)) {
            throw new RuntimeException(
                'Decoded filesystem envelope must be an array.',
            );
        }

        $name = (string) ($decoded['name'] ?? $expectedName);

        if (
            strtolower($name)
            !== strtolower($expectedName)
        ) {
            throw new RuntimeException(
                sprintf(
                    'Envelope name [%s] does not match requested Scroll [%s].',
                    $name,
                    $expectedName,
                ),
            );
        }

        $scrollType = ScrollTypes::normalize(
            $decoded['scrollType'] ?? null,
            null,
        );

        if (!$scrollType instanceof ScrollTypes) {
            throw new RuntimeException(
                sprintf(
                    'Invalid Scroll type in envelope [%s].',
                    $expectedName,
                ),
            );
        }

        if ($scrollType !== $expectedType) {
            throw new RuntimeException(
                sprintf(
                    'Envelope [%s] is type [%s], expected [%s].',
                    $expectedName,
                    $scrollType->value,
                    $expectedType->value,
                ),
            );
        }

        $metadata = $decoded['metadata'] ?? null;

        if (!$metadata instanceof Metadata) {
            throw new RuntimeException(
                sprintf(
                    'Envelope [%s] contains invalid metadata.',
                    $expectedName,
                ),
            );
        }

        return new Envelope(
            name: $name,
            version: (string) ($decoded['version'] ?? ''),
            scrollType: $scrollType,
            data: (string) ($decoded['data'] ?? ''),
            metadata: $metadata,
            seal: $decoded['seal'] ?? null,
            signature: $decoded['signature'] ?? null,
            codec: Codecs::tryFrom(
                (string) ($decoded['codec'] ?? ''),
            ) ?? Codecs::default(),
        );
    }

    protected function getPath(
        ScrollTypes $type,
        string $name,
    ): string {
        $name = trim($name);

        if ($name === '') {
            throw new \InvalidArgumentException(
                'Scroll name cannot be empty.',
            );
        }

        $name = str_replace(
            ['/', '\\', '..'],
            '_',
            $name,
        );

        return $this->getDirectory($type)
            . DIRECTORY_SEPARATOR
            . $name
            . '.'
            . $this->resolveExtension($type);
    }

    public function getDiscovered(
        \Codejitsu\Discovery\DiscoveredScroll $scroll,
    ): ?EnvelopeContract {
        if (!is_file($scroll->path)) {
            return null;
        }

        $payload = file_get_contents($scroll->path);

        if ($payload === false) {
            throw new RuntimeException(
                sprintf(
                    'Unable to read Scroll [%s].',
                    $scroll->path,
                ),
            );
        }

        $decoded = $this->codec
            ->make()
            ->decode($payload);

        if (!is_array($decoded)) {
            throw new RuntimeException(
                sprintf(
                    'Scroll [%s] must decode to an array.',
                    $scroll->name,
                ),
            );
        }

        $name = isset($decoded['name'])
            ? (string) $decoded['name']
            : $scroll->name;

        $version = isset($decoded['version'])
            ? (string) $decoded['version']
            : '1.0.0';

        if (strtolower($name) !== strtolower($scroll->name)) {
            throw new RuntimeException(
                sprintf(
                    'Scroll name [%s] does not match discovered name [%s].',
                    $name,
                    $scroll->name,
                ),
            );
        }

        $identity = new \Codejitsu\Identity\Identity(
            type: \Codejitsu\Enums\Identity\Types::Scroll,
            identifier: new \Codejitsu\Identity\Identifier($name),
            version: \Codejitsu\ValueObjects\Version::fromString($version),
        );

        $metadata = new Metadata($identity);

        $metadata->set('name', $name);
        $metadata->set('version', $version);
        $metadata->set('type', $scroll->type->value);
        $metadata->set('path', $scroll->path);
        $metadata->set('extension', $scroll->extension);

        return new Envelope(
            name: $name,
            version: $version,
            scrollType: $scroll->type,
            data: $payload,
            metadata: $metadata,
            codec: $this->codec,
        );
    }
}