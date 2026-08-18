<?php

declare(strict_types=1);

namespace Codejitsu\Schema;

use Codejitsu\Enums\Environment;
use Opis\JsonSchema\Helper;
use Opis\JsonSchema\Validator as OpisValidator;

final class JsonSchema extends Validator
{
    public function __construct(
        private readonly OpisValidator $validator = new OpisValidator(),
    ) {}

    protected function validateSchema(
        array $data,
        array $schema,
    ): void {
        $result = $this->validator->validate(
            Helper::toJSON($data),
            Helper::toJSON($schema),
        );

        if ($result->isValid()) {
            return;
        }

        Environment::error(
            new \InvalidArgumentException(
                $result->error()?->message()
                    ?? 'Schema validation failed.',
            ),
        );
    }
}