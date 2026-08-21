<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls\Types;

use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use Codejitsu\Scrolls\Scroll;

final class Config extends Scroll
{
    public const ScrollTypes TYPE = ScrollTypes::CONFIG;

    public function get(string $path, mixed $default = null): mixed
    {
        $value = $this->attributes;

        foreach (explode('.', trim($path, '.')) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public function execute(?string $path = null, mixed $default = null): mixed
    {
        return $path === null
            ? $this->toArray()
            : $this->get($path, $default);
    }
}