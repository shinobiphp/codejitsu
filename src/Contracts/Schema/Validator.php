<?php
declare(strict_types= 1);

namespace Codejitsu\Contracts\Schema;

interface Validator
{
    public function validate(
        array $data,
        array $schema,
    ): void;
}