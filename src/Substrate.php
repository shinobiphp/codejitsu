<?php

declare(strict_types=1);

namespace Codejitsu;

interface Substrate
{
    public function execute(string $source, ExecutionContext $context): mixed;
}
