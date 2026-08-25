<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls\Lifecycle;

use Codejitsu\Codecs\Neon;
use Codejitsu\Contracts\Scrolls\Scroll as ScrollContract;

final class Canonicalizer
{
    public function array(array $payload): array
    {
        foreach (['signature', 'seal'] as $field) {
            unset($payload[$field]);
        }

        return $this->normalize($payload);
    }

    public function scroll(ScrollContract $scroll): string
    {
        return (new Neon())->encode($this->array($scroll->toArray()));
    }

    private function normalize(array $payload): array
    {
        if (array_is_list($payload)) {
            return array_map(
                fn (mixed $value): mixed => is_array($value)
                    ? $this->normalize($value)
                    : $value,
                $payload,
            );
        }

        $normalized = [];
        $keys = array_keys($payload);
        sort($keys, SORT_STRING);

        foreach ($keys as $key) {
            $value = $payload[$key];
            $normalized[$key] = is_array($value)
                ? $this->normalize($value)
                : $value;
        }

        return $normalized;
    }
}
