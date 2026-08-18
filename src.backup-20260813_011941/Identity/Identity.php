<?php
declare(strict_types=1);

namespace Codejitsu\Identity;

use Codejitsu\Enums\Identity\Types as IdentityType;

use Codejitsu\ValueObjects\Version;

use Stringable;

final readonly class Identity implements Stringable
{
    public function __construct(
        public IdentityType $type,
        public Identifier $identifier,
        public Version $version = new Version(),
    ) {}

    public function equals(self $other): bool
    {
        return $this->type === $other->type
            && $this->identifier->equals($other->identifier)
            && $this->version->equals($other->version);
    }

    public function sameAs(self $other): bool
    {
        return $this->type === $other->type
            && $this->identifier->equals($other->identifier);
    }

    public function next(): self
    {
        return new self(
            $this->type,
            $this->identifier,
            $this->version->next(),
        );
    }

    public function __toString(): string
    {
        return sprintf(
            '%s://%s@%s',
            $this->type->value,
            $this->identifier,
            $this->version,
        );
    }
}