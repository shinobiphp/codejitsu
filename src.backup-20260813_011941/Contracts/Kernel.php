<?php
declare(strict_types=1);

namespace Codejitsu\Contracts;

interface Kernel
{
    public function boot(): self;
}