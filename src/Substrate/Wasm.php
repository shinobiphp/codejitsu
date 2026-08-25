<?php

declare(strict_types=1);

namespace Codejitsu\Substrate;

use Codejitsu\ExecutionContext;
use Codejitsu\Substrate;
use LogicException;

final class Wasm implements Substrate
{
    public function execute(string $source, ExecutionContext $context): mixed
    {
        if (!class_exists('Codejitsu\Substrate\Wasmtime')) {
            throw new LogicException('Wasmtime substrate is unavailable.');
        }

        return (new Wasmtime())->execute($source, $context);
    }
}
