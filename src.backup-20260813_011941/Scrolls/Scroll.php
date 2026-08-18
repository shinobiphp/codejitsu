<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls;

use Codejitsu\Contracts\Scrolls\Envelope as EnvelopeContract;
use Codejitsu\Contracts\Scrolls\Scroll as ScrollContract;
use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use ReflectionClass;

abstract class Scroll implements ScrollContract
{
    /**
     * Override type in concrete classes using a constant:
     * public const TYPE = ScrollTypes::APP;
     */
    public const ScrollTypes|string|null TYPE = null;

    /**
     * Override name in concrete classes using a constant if desired:
     * public const NAME = 'custom_name';
     */
    public const ?string NAME = null;

    protected ?EnvelopeContract $envelope = null;

    /**
     * Arbitrary attributes attached to this scroll during hydration or execution.
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * Dynamic instance-level overrides if needed.
     */
    protected ?string $dynamicName = null;
    protected ScrollTypes|string|null $dynamicType = null;

    /**
     * PHP 8.4 Property Hook satisfying Contracts\Scrolls\Scroll::$type
     * Reads static::TYPE by default.
     */
    public ScrollTypes|string $type {
        get => $this->dynamicType ?? static::TYPE ?? ScrollTypes::APP;
        set (ScrollTypes|string $value) {
            $this->dynamicType = $value;
        }
    }

    /**
     * PHP 8.4 Property Hook satisfying Contracts\Scrolls\Scroll::$name
     * Reads static::NAME or falls back to lowercase short class name.
     */
    public string $name {
        get => $this->dynamicName ?? static::NAME ?? strtolower((new ReflectionClass($this))->getShortName());
        set (string $value) {
            $this->dynamicName = strtolower($value);
        }
    }

    /**
     * Static factory method to instantiate and hydrate from an envelope and payload data.
     */
    public static function make(EnvelopeContract $envelope, array $data = []): static
    {
        $instance = new static();
        $instance->envelope = $envelope;
        $instance->hydrate($data);

        return $instance;
    }

    /**
     * Hydrate internal scroll state using unsealed/decoded payload data.
     */
    public function hydrate(array $data): static
    {
        if (isset($data['name']) && is_string($data['name'])) {
            $this->name = $data['name'];
        }

        if (isset($data['type'])) {
            if ($data['type'] instanceof ScrollTypes) {
                $this->type = $data['type'];
            } elseif (is_string($data['type'])) {
                $this->type = ScrollTypes::tryFrom(strtolower($data['type'])) ?? $data['type'];
            }
        }

        $this->attributes = array_merge($this->attributes, $data);

        return $this;
    }

    public function getEnvelope(): ?EnvelopeContract
    {
        return $this->envelope;
    }

    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? $this->envelope?->metadata[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]) || isset($this->envelope?->metadata[$key]);
    }

    public function __invoke(mixed ...$args): mixed
    {
        if (method_exists($this, 'handle')) {
            return $this->handle(...$args);
        }

        if (method_exists($this, 'execute')) {
            return $this->execute(...$args);
        }

        return $this;
    }
}