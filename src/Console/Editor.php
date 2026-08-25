<?php

declare(strict_types=1);

namespace Codejitsu\Console;

interface Editor
{
    public function edit(string $initial = ''): string;
}
