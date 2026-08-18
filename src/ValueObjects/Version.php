<?php

declare(strict_types=1);

namespace Codejitsu\ValueObjects;

use JsonSerializable;
use Stringable;
use InvalidArgumentException;

final class Version implements Stringable, JsonSerializable
{
    public int $major {
        set {
            $this->assertPart($value, 'major');
            $this->major = $value;
        }
    }

    public int $minor {
        set {
            $this->assertPart($value, 'minor');
            $this->minor = $value;
        }
    }

    public int $patch {
        set {
            $this->assertPart($value, 'patch');
            $this->patch = $value;
        }
    }

    public int $build {
        set {
            $this->assertPart($value, 'build');
            $this->build = $value;
        }
    }

    /**
     * Get or replace the complete version.
     *
     * Examples:
     *
     * $version->version = '2.4.1+7';
     * $version->version = 'v2.4.1';
     */
    public string $version {
        get => (string) $this;

        set {
            $parsed = self::parse($value);

            $this->major = $parsed->major;
            $this->minor = $parsed->minor;
            $this->patch = $parsed->patch;
            $this->build = $parsed->build;
        }
    }

    public function __construct(
        int $major = 0,
        int $minor = 1,
        int $patch = 0,
        int $build = 0,
    ) {
        $this->major = $major;
        $this->minor = $minor;
        $this->patch = $patch;
        $this->build = $build;
    }

    public static function fromString(string $version): self
    {
        return self::parse($version);
    }

    /**
     * Compare this version against another version.
     *
     * Returns:
     * -1 if this < other
     *  0 if this == other
     *  1 if this > other
     */
    public function compareTo(self|string $other): int
    {
        $other = is_string($other)
            ? self::parse($other)
            : $other;

        return [
            $this->major,
            $this->minor,
            $this->patch,
            $this->build,
        ] <=> [
            $other->major,
            $other->minor,
            $other->patch,
            $other->build,
        ];
    }

    public function isGreaterThan(self|string $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    public function isLessThan(self|string $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    public function equals(self|string $other): bool
    {
        return $this->compareTo($other) === 0;
    }

    public static function parse(string|int $version): self
    {
        if (is_int($version)) {
            if ($version < 0) {
                throw new InvalidArgumentException(
                    'Version build number cannot be negative.',
                );
            }

            return new self(build: $version);
        }

        if (!preg_match(
            '/^v?(\d+)(?:\.(\d+))?(?:\.(\d+))?(?:\+(\d+))?$/i',
            trim($version),
            $matches,
        )) {
            throw new InvalidArgumentException(
                sprintf('Invalid version string: "%s".', $version),
            );
        }

        return new self(
            major: (int) $matches[1],
            minor: isset($matches[2]) ? (int) $matches[2] : 0,
            patch: isset($matches[3]) ? (int) $matches[3] : 0,
            build: isset($matches[4]) ? (int) $matches[4] : 0,
        );
    }

    public function toArray(): array
    {
        return [
            'major' => $this->major,
            'minor' => $this->minor,
            'patch' => $this->patch,
            'build' => $this->build,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return sprintf(
            '%d.%d.%d%s',
            $this->major,
            $this->minor,
            $this->patch,
            $this->build > 0 ? "+{$this->build}" : '',
        );
    }

    private function assertPart(int $value, string $part): void
    {
        if ($value < 0) {
            throw new InvalidArgumentException(
                sprintf('%s version component cannot be negative.', ucfirst($part)),
            );
        }
    }

    public function next(string $part = 'build'): self
    {
        return match ($part) {
            'major' => new self($this->major + 1),
            'minor' => new self($this->major, $this->minor + 1),
            'patch' => new self($this->major, $this->minor, $this->patch + 1),
            'build' => new self($this->major, $this->minor, $this->patch, $this->build + 1),
            default => throw new InvalidArgumentException(
                sprintf('Invalid version increment: "%s".', $part),
            ),
        };
    }
}