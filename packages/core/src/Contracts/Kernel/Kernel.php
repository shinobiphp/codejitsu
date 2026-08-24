<?php
declare(strict_types=1);

namespace Codejitsu\Contracts\Kernel;

interface Kernel
{
    public function boot(): self;
}