<?php
declare(strict_types=1);

namespace Codejitsu\Execution;

use Codejitsu\Identity\Identifier;

final readonly class ExecutionScope
{
    public function __construct(
        public ?Identifier $identity = null,
        public Permissions $grants = new Permissions(),
        public Permissions $denials = new Permissions(),
    ) {}

    public function with(Permission ...$permissions): self
    {
        return new self(
            identity: $this->identity,
            grants: $this->grants->with(...$permissions),
            denials: $this->denials,
        );
    }

    public function without(Permission ...$permissions): self
    {
        return new self(
            identity: $this->identity,
            grants: $this->grants,
            denials: $this->denials->with(...$permissions),
        );
    }

    public function as(
        Permissions $with = new Permissions(),
        Permissions $without = new Permissions(),
    ): self {
        return new self(
            identity: $this->identity,
            grants: $this->grants->with(...$with),
            denials: $this->denials->with(...$without),
        );
    }

    public function allows(
        string $permission,
        ?string $resource = null,
    ): bool {
        if ($this->denials->allows($permission, $resource)) {
            return false;
        }

        return $this->grants->allows($permission, $resource);
    }

    public function denies(
        string $permission,
        ?string $resource = null,
    ): bool {
        return $this->denials->allows($permission, $resource);
    }
}