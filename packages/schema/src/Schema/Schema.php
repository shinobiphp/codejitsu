<?php
declare(strict_types=1);

namespace Codejitsu\Schema;

use Codejitsu\Schema\Contracts\Validator;

final readonly class Schema
{
    public function __construct(
        public array $definition,
    ) {}

    public function id(): string
    {
        return $this->definition['$id'];
    }

    public function validate(
        mixed $data,
        Validator $validator,
    ): void {
        $validator->validate($data, $this);
    }
}