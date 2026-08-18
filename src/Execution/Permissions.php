<?php
declare(strict_types=1);

namespace Codejitsu\Execution;

use Countable;
use IteratorAggregate;
use Traversable;

final readonly class Permissions implements Countable, IteratorAggregate
{
    /**
     * @param list<Permission> $permissions
     */
    public function __construct(
        private array $permissions = [],
    ) {}

    public function with(Permission ...$permissions): self
    {
        $current = $this->permissions;

        foreach ($permissions as $permission) {
            foreach ($current as $existing) {
                if ($existing->equals($permission)) {
                    continue 2;
                }
            }

            $current[] = $permission;
        }

        return new self($current);
    }

    public function without(Permission ...$permissions): self
    {
        return new self(
            array_values(
                array_filter(
                    $this->permissions,
                    static function (Permission $current) use ($permissions): bool {
                        foreach ($permissions as $permission) {
                            if ($current->equals($permission)) {
                                return false;
                            }
                        }

                        return true;
                    },
                ),
            ),
        );
    }

    public function allows(
        string $name,
        ?string $resource = null,
    ): bool {
        foreach ($this->permissions as $permission) {
            if (
                $permission->name === $name
                && $permission->allows($resource)
            ) {
                return true;
            }
        }

        return false;
    }

    public function disallows(
        string $name,
        ?string $resource = null,
    ): bool {
        return !$this->allows($name, $resource);
    }

    public function count(): int
    {
        return count($this->permissions);
    }

    /**
     * @return Traversable<int, Permission>
     */
    public function getIterator(): Traversable
    {
        yield from $this->permissions;
    }

    /**
     * @return list<Permission>
     */
    public function toArray(): array
    {
        return $this->permissions;
    }
}