<?php

declare(strict_types=1);

namespace Codejitsu\Codecs;

use InvalidArgumentException;
use Throwable;

final class Php extends Codec
{
    protected const FORMAT = 'php';

    protected const SEALER = null;

    protected const SIGNER = null;

    public function encode(array $payload): string
    {
        try {
            return serialize($payload);
        } catch (Throwable $e) {
            throw new InvalidArgumentException(
                'Failed to encode payload to PHP: '
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
            $decoded = unserialize($payload);
        } catch (Throwable $e) {
            throw new InvalidArgumentException(
                'Failed to decode PHP payload: '
                . $e->getMessage(),
                0,
                $e,
            );
        }

        if (!is_array($decoded)) {
            throw new InvalidArgumentException(
                'Decoded PHP payload must be an object or array.',
            );
        }

        return $decoded;
    }
}