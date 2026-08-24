<?php
declare(strict_types=1);

namespace Codejitsu\Crypto;

use Codejitsu\Enums\Crypto\HashAlgorithms as Algorithm;
use Stringable;

final readonly class Hash implements Stringable
{
    public function __construct(
        public Algorithm $algorithm,
        public string $value,
    ) {
        if ($this->value === '') {
            throw new \InvalidArgumentException(
                'Hash value cannot be empty.',
            );
        }
    }

    public function equals(self $other): bool
    {
        return $this->algorithm === $other->algorithm
            && hash_equals($this->value, $other->value);
    }

    public function __toString(): string
    {
        return "{$this->algorithm->value}:{$this->value}";
    }

    public static function of(
       string $value,
        Algorithm $algorithm = Algorithm::SHA256,
    ): self
    {
        return new self(
            $algorithm,
            hash($algorithm->value, $value),
        );
    }

    public function verifies(string $value): bool
    {
        return hash_equals(
            $this->value,
            hash($this->algorithm->value, $value),
        );
    }
}