<?php

declare(strict_types=1);

namespace Codejitsu\Codecs;

use InvalidArgumentException;
use Nette\Neon\Neon as NeonParser;
use Throwable;

final class Neon extends Codec
{
    protected const FORMAT = 'neon';

    protected const SEALER = null;

    protected const SIGNER = null;

    public function encode(array $payload): string
    {
        try {
            return NeonParser::encode($payload);
        } catch (Throwable $e) {
            throw new InvalidArgumentException(
                'Failed to encode payload to NEON: '
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
            $decoded = NeonParser::decode($payload);
        } catch (Throwable $e) {
            throw new InvalidArgumentException(
                'Failed to decode NEON payload: '
                . $e->getMessage(),
                0,
                $e,
            );
        }

        if (!is_array($decoded)) {
            throw new InvalidArgumentException(
                'Decoded NEON payload must be an object or array.',
            );
        }

        return $decoded;
    }
}