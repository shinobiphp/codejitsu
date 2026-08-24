<?php
declare(strict_types=1);

namespace Codejitsu\Schema;

use Codejitsu\Contracts\Schema\Validator as ValidatorContract;

abstract class Validator implements ValidatorContract
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $schema
     */
    final public function validate(
        array $data,
        array $schema,
    ): void {
        $this->validateSchema($data, $schema);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $schema
     */
    abstract protected function validateSchema(
        array $data,
        array $schema,
    ): void;
}