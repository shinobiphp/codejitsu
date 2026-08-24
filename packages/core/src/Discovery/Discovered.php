<?php

declare(strict_types=1);

namespace Codejitsu\Discovery;

use ArrayAccess;
use ArrayIterator;
use Closure;
use Codejitsu\Attributes\Discoverable;
use Codejitsu\Contracts\Codex as CodexContract;
use Countable;
use IteratorAggregate;
use ReflectionClass;
use Traversable;

/**
 * @implements IteratorAggregate<string, ReflectionClass<object>>
 * @implements ArrayAccess<string, ReflectionClass<object>>
 */
final class Discovered implements Countable, IteratorAggregate, ArrayAccess
{
    /**
     * @param array<string, ReflectionClass<object>> $items FQCN => ReflectionClass
     */
    public function __construct(
        private array $items = []
    ) {}

    public function has(string $className): bool
    {
        return isset($this->items[$className]);
    }

    /**
     * @return ReflectionClass<object>|null
     */
    public function get(string $className): ?ReflectionClass
    {
        return $this->items[$className] ?? null;
    }

    /**
     * @return array<string, ReflectionClass<object>>
     */
    public function classes(): array
    {
        return $this->items;
    }

    /*
     |--------------------------------------------------------------------------
     | Generic Filters
     |--------------------------------------------------------------------------
     */

    public function filter(Closure $predicate): self
    {
        return new self(array_filter($this->items, $predicate, ARRAY_FILTER_USE_BOTH));
    }

    public function implements(string $interface): self
    {
        return $this->filter(
            fn(ReflectionClass $ref) => $ref->implementsInterface($interface)
        );
    }

    public function hasAttribute(string $attributeClass): self
    {
        return $this->filter(
            fn(ReflectionClass $ref) => $ref->getAttributes($attributeClass) !== []
        );
    }

    public function tagged(string ...$tags): self
    {
        return $this->filter(function (ReflectionClass $ref) use ($tags) {
            $attrs = $ref->getAttributes(Discoverable::class);
            if ($attrs === []) {
                return false;
            }

            /** @var Discoverable $discoverable */
            $discoverable = $attrs[0]->newInstance();

            return array_intersect($tags, $discoverable->tags) !== [];
        });
    }

    public function inGroup(string $group): self
    {
        return $this->filter(function (ReflectionClass $ref) use ($group) {
            $attrs = $ref->getAttributes(Discoverable::class);
            if ($attrs === []) {
                return false;
            }

            /** @var Discoverable $discoverable */
            $discoverable = $attrs[0]->newInstance();

            return $discoverable->group === $group;
        });
    }

    /*
     |--------------------------------------------------------------------------
     | Instantiation & Registration
     |--------------------------------------------------------------------------
     */

    /**
     * Instantiates all discovered classes (using container factory or raw new instance).
     *
     * @return array<string, object> FQCN => instance
     */
    public function instantiate(?callable $factory = null): array
    {
        $instances = [];

        foreach ($this->items as $className => $reflection) {
            $instances[$className] = $factory !== null
                ? $factory($reflection)
                : $reflection->newInstance();
        }

        return $instances;
    }

    /**
     * Registers items into any generic Codex or Container.
     */
    public function registerInto(CodexContract $codex, ?callable $factory = null): CodexContract
    {
        foreach ($this->items as $className => $reflection) {
            $instance = $factory !== null ? $factory($reflection) : $reflection->newInstance();

            // Extract metadata from #[Discoverable] if present
            $discAttr = $reflection->getAttributes(Discoverable::class)[0] ?? null;
            /** @var Discoverable|null $discMeta */
            $discMeta = $discAttr?->newInstance();

            $identifier = $discMeta?->alias ?? $className;

            $metadata = [
                'group' => $discMeta?->group,
                'tags' => $discMeta?->tags ?? [],
                'meta' => $discMeta?->meta ?? [],
                'reflection' => $reflection,
            ];

            $codex->register($identifier, $instance);
        }

        return $codex;
    }

    /**
     * Returns reflection and attribute maps for all discovered classes.
     *
     * @return array<string, array{reflection: ReflectionClass<object>, attributes: array<object>}>
     */
    public function metadata(): array
    {
        $meta = [];

        foreach ($this->items as $fqcn => $ref) {
            $attributes = [];
            foreach ($ref->getAttributes() as $attribute) {
                $attributes[] = $attribute->newInstance();
            }

            $meta[$fqcn] = [
                'reflection' => $ref,
                'attributes' => $attributes,
            ];
        }

        return $meta;
    }

    /*
     |--------------------------------------------------------------------------
     | Standard Interfaces
     |--------------------------------------------------------------------------
     */

    public function count(): int { return count($this->items); }
    public function getIterator(): Traversable { return new ArrayIterator($this->items); }
    public function offsetExists(mixed $offset): bool { return is_string($offset) && $this->has($offset); }
    public function offsetGet(mixed $offset): mixed { return $this->get((string) $offset); }
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_string($offset) && $value instanceof ReflectionClass) {
            $this->items[$offset] = $value;
        }
    }
    public function offsetUnset(mixed $offset): void
    {
        if (is_string($offset)) {
            unset($this->items[$offset]);
        }
    }
}