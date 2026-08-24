<?php

declare(strict_types=1);

namespace Codejitsu\Codecs;

use InvalidArgumentException;
use JsonException;

final class Json extends Codec
{
    protected const FORMAT = 'json';

    protected const SEALER = null;

    protected const SIGNER = null;

    public function encode(array $payload): string
    {
        try {
            return json_encode(
                $payload,
                JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES,
            );
        } catch (JsonException $e) {
            throw new InvalidArgumentException(
                'Failed to encode payload to JSON: '
                . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    public function decode(
        string $payload,
        ?string $key = null,
    ): array {
        try {
            $decoded = json_decode(
                $payload,
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $e) {
            throw new InvalidArgumentException(
                'Failed to decode JSON payload: '
                . $e->getMessage(),
                0,
                $e,
            );
        }

        if (!is_array($decoded)) {
            throw new InvalidArgumentException(
                'Decoded JSON payload must be an object or array.',
            );
        }

        return $decoded;
    }
}