<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls;

use Codejitsu\Contracts\Scrolls\Envelope as EnvelopeContract;
use Codejitsu\Contracts\Scrolls\Scroll as ScrollContract;
use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use InvalidArgumentException;
use LogicException;
use ReflectionClass;

abstract class Scroll implements ScrollContract
{
    /**
     * Override in concrete Scrolls.
     */
    public const ScrollTypes|string|null TYPE = null;

    /**
     * Optional explicit name.
     */
    public const ?string NAME = null;

    /**
     * Semantic version.
     */
    public const string VERSION = '1.0.0';

    /**
     * Static default tags.
     *
     * @var array<string>
     */
    public const array TAGS = [];

    protected ?EnvelopeContract $envelope = null;

    /**
     * Runtime attributes.
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    protected ?string $dynamicName = null;

    /**
     * Runtime tags.
     *
     * @var array<string>
     */
    protected array $dynamicTags = [];

    public string $name {
        get => $this->dynamicName
            ?? static::NAME
            ?? strtolower(
                (new ReflectionClass($this))->getShortName()
            );

        set(string $value) {
            $value = trim(strtolower($value));

            if ($value === '') {
                throw new InvalidArgumentException(
                    'Scroll name cannot be empty.'
                );
            }

            $this->dynamicName = $value;
        }
    }

    public string $version {
        get => static::VERSION;
    }

    public ScrollTypes|string $type {
        get {
            $type = static::TYPE;

            if ($type === null) {
                throw new LogicException(
                    sprintf(
                        'Scroll [%s] does not declare a TYPE.',
                        static::class,
                    ),
                );
            }

            return $type;
        }
    }

    /**
     * @return array<string>
     */
    public array $tags {
        get {
            if ($this->dynamicTags !== []) {
                return $this->dynamicTags;
            }

            return array_values(
                array_unique(
                    array_map(
                        static fn (string $tag): string =>
                            strtolower(trim($tag)),
                        static::TAGS,
                    ),
                ),
            );
        }
    }

    /**
     * Create and hydrate a Scroll from an Envelope.
     *
     * @param array<string, mixed> $data
     */
    public static function make(
        EnvelopeContract $envelope,
        array $data = [],
    ): static {
        $instance = new static();

        $instance->envelope = $envelope;

        $instance->hydrate($data);

        return $instance;
    }

    /**
     * Hydrate runtime state.
     *
     * @param array<string, mixed> $data
     */
    public function hydrate(array $data): static
    {
        if (isset($data['name'])) {
            if (!is_string($data['name'])) {
                throw new InvalidArgumentException(
                    'Scroll name must be a string.'
                );
            }

            $this->name = $data['name'];
        }

        if (isset($data['tags'])) {
            if (!is_array($data['tags'])) {
                throw new InvalidArgumentException(
                    'Scroll tags must be an array.'
                );
            }

            $this->dynamicTags = array_values(
                array_unique(
                    array_map(
                        static fn (mixed $tag): string =>
                            strtolower(trim((string) $tag)),
                        $data['tags'],
                    ),
                ),
            );
        }

        $this->attributes = array_merge(
            $this->attributes,
            $data,
        );

        return $this;
    }

    /**
     * Export the runtime Scroll state.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(
            [
                'name' => $this->name,
                'type' => $this->type instanceof ScrollTypes
                    ? $this->type->value
                    : $this->type,
                'version' => $this->version,
                'tags' => $this->tags,
            ],
            $this->attributes,
        );
    }

    public function getEnvelope(): ?EnvelopeContract
    {
        return $this->envelope;
    }

    public function __get(string $key): mixed
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        return $this->envelope?->metadata->get($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return array_key_exists($key, $this->attributes)
            || $this->envelope?->metadata->has($key) === true;
    }

    /**
     * Primary execution entry point.
     *
     * Concrete Scrolls should normally implement execute()
     * or handle().
     */
    public function __invoke(mixed ...$args): mixed
    {
        if (method_exists($this, 'execute')) {
            return $this->execute(...$args);
        }

        if (method_exists($this, 'handle')) {
            return $this->handle(...$args);
        }

        throw new LogicException(
            sprintf(
                'Scroll [%s] is not executable.',
                static::class,
            ),
        );
    }

    /**
     * Dynamic action dispatch.
     *
     * Example:
     *
     * $scroll->validate($input);
     */
    public function __call(
        string $method,
        array $args,
    ): mixed {
        if (!method_exists($this, $method)) {
            throw new LogicException(
                sprintf(
                    'Scroll [%s] has no action [%s].',
                    static::class,
                    $method,
                ),
            );
        }

        return $this->{$method}(...$args);
    }
}