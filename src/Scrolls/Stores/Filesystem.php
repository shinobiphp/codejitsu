<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls\Stores;

use Codejitsu\Contracts\Scrolls\Envelope as EnvelopeContract;
use Codejitsu\Contracts\Scrolls\Store as StoreContract;
use Codejitsu\Enums\Codecs;
use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use Codejitsu\Scrolls\Envelope;
use Codejitsu\Scrolls\TypeDefinition;
use Codejitsu\Scrolls\TypeRegistry;
use Codejitsu\Metadata;
use RuntimeException;

final class Filesystem implements StoreContract
{
    public function __construct(
        protected string $baseDir,
        protected ?string $extension = null,
        protected Codecs $codec = Codecs::NEON,
        protected ?TypeRegistry $types = null,
    ) {
        $this->baseDir = rtrim(
            $baseDir,
            '/\\',
        );
    }

    public function getDirectory(
        ScrollTypes|string $type,
    ): string {
        return $this->baseDir
            . DIRECTORY_SEPARATOR
            . $this->definition($type)->plural;
    }

    public function resolveExtension(
        ScrollTypes|string $type,
    ): string {
        if ($this->extension !== null) {
            return ltrim(
                $this->extension,
                '.',
            );
        }

        $builtin = $type instanceof ScrollTypes ? $type : ScrollTypes::tryFrom(strtolower($type));
        if ($builtin instanceof ScrollTypes && !in_array($builtin, [ScrollTypes::COMMAND, ScrollTypes::CONTEXT], true)) {
            return 'neon';
        }
        return $this->definition($type)->extension;
    }

    public function has(
        ScrollTypes|string $type,
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
        ScrollTypes|string $type,
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
        ScrollTypes|string $type,
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
        ScrollTypes|string $type,
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
        $codec = $this->definition($envelope->scrollType)->makeCodec();

        $data = [
            'name' => $envelope->name,
            'version' => $envelope->version,
            'scrollType' => $envelope->scrollType instanceof ScrollTypes
                ? $envelope->scrollType->value
                : $envelope->scrollType,
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
        ScrollTypes|string $expectedType,
        string $expectedName,
    ): EnvelopeContract {
        $codec = $this->definition($expectedType)->makeCodec();

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

        $scrollTypeName = strtolower(trim((string) ($decoded['scrollType'] ?? '')));
        if (!$this->registry()->has($scrollTypeName)) {
            throw new RuntimeException(
                sprintf(
                    'Invalid Scroll type in envelope [%s].',
                    $expectedName,
                ),
            );
        }

        $expectedTypeName = $expectedType instanceof ScrollTypes ? $expectedType->value : strtolower($expectedType);
        if ($scrollTypeName !== $expectedTypeName) {
            throw new RuntimeException(
                sprintf(
                    'Envelope [%s] is type [%s], expected [%s].',
                    $expectedName,
                    $scrollTypeName,
                    $expectedTypeName,
                ),
            );
        }

        $metadata = $decoded['metadata'] ?? null;

        if (is_array($metadata)) {
            $identity = new \Codejitsu\Identity\Identity(
                \Codejitsu\Enums\Identity\Types::Scroll,
                new \Codejitsu\Identity\Identifier($name),
                \Codejitsu\ValueObjects\Version::fromString((string) ($decoded['version'] ?? '1.0.0')),
            );
            $restored = new Metadata($identity);
            foreach ($metadata as $key => $value) {
                $restored->set($key, $value);
            }
            $metadata = $restored;
        }

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
            scrollType: ScrollTypes::tryFrom($scrollTypeName) ?? $scrollTypeName,
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
        ScrollTypes|string $type,
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

        $definition = $this->definition($scroll->type);
        $decoded = $definition->makeCodec()->decode($payload);

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
        $metadata->set('type', $definition->name);
        $metadata->set('path', $scroll->path);
        $metadata->set('extension', $scroll->extension);

        return new Envelope(
            name: $name,
            version: $version,
            scrollType: $scroll->type,
            data: $payload,
            metadata: $metadata,
            codec: $definition->codec,
        );
    }

    protected function definition(ScrollTypes|string $type): TypeDefinition
    {
        return $this->registry()->get($type instanceof ScrollTypes ? $type->value : $type);
    }

    protected function registry(): TypeRegistry
    {
        return $this->types ??= TypeRegistry::builtins();
    }
}
