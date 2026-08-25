<?php

declare(strict_types=1);

namespace Codejitsu\Substrate;

use Codejitsu\Substrate;
use Codejitsu\SubstrateRegistry;
use LogicException;

final readonly class Resolver
{
    public function __construct(
        private SubstrateRegistry $registry,
        private Detector $detector = new Detector(),
    ) {
    }

    public function resolve(?string $requested, string $source): Substrate
    {
        $name = strtolower(trim((string) $requested));

        if ($name === '' || $name === 'auto') {
            $name = $this->detector->detect($source);
        }

        if (!$this->registry->has($name)) {
            throw new LogicException(sprintf('Substrate [%s] is not registered.', $name));
        }

        return $this->registry->get($name);
    }
}
