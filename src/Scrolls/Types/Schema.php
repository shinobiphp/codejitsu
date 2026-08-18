<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls\Types;

use Codejitsu\Contracts\Schema\Validator;
use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use Codejitsu\Schema\JsonSchema;
use Codejitsu\Scrolls\Scroll;

final class Schema extends Scroll
{
    public const TYPE = ScrollTypes::SCHEMA;

    public function __construct(
        private readonly Validator $validator = new JsonSchema(),
    ) {}

    public array $definition {
        get => $this->attributes;
    }

    public function validate(array $data): void
    {
        $this->validator->validate(
            $data,
            $this->definition,
        );
    }

    public function execute(array $data): array
    {
        $this->validate($data);

        return $data;
    }
}