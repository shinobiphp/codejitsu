<?php

declare(strict_types=1);

namespace Codejitsu;

use InvalidArgumentException;
use LogicException;

final class SubstrateRegistry
{
    /** @var array<string, Substrate> */
    private array $substrates = [];

    public function register(string $name, Substrate $substrate): void
    {
        $name = strtolower(trim($name));

        if ($name === '') {
            throw new InvalidArgumentException('Substrate name cannot be blank.');
        }

        $this->substrates[$name] = $substrate;
    }

    public function has(string $name): bool
    {
        return isset($this->substrates[strtolower(trim($name))]);
    }

    public function get(string $name): Substrate
    {
        $name = strtolower(trim($name));

        if (!$this->has($name)) {
            throw new LogicException(sprintf('Substrate [%s] is not registered.', $name));
        }

        return $this->substrates[$name];
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->substrates);
    }
}
