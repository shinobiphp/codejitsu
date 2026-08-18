<?php
declare(strict_types=1);

namespace Codejitsu\Execution;

use Stringable;

final readonly class Permission implements Stringable
{
    /**
     * @param list<string> $resources
     */
    public function __construct(
        public string $name,
        public array $resources = [],
    ) {
        if ($this->name === '') {
            throw new \InvalidArgumentException(
                'Permission name cannot be empty.',
            );
        }
    }

    public function allows(?string $resource = null): bool
    {
        if ($resource === null) {
            return false;
        }

        return in_array('*', $this->resources, true)
            || in_array($resource, $this->resources, true);
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name
            && $this->resources === $other->resources;
    }

    public function __toString(): string
    {
        if ($this->resources === []) {
            return $this->name;
        }

        return $this->name . ':' . implode(',', $this->resources);
    }
}