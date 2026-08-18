<?php
declare(strict_types=1);

namespace Codejitsu\Codecs;

use Codejitsu\Codecs\Codec;

use Nette\Neon\Neon as NeonParser;

use Throwable;

class Neon extends Codec
{
    protected const FORMAT = 'neon';
    protected const SEALER = null; // No encryption by default
    protected const SIGNER = 'hmac-sha256'; // No signing by default

    public function encode(array $payload): string
    {
        try {
            return NeonParser::encode($payload);
        } catch (Throwable $e) {
            throw new \InvalidArgumentException("Failed to encode payload to NEON: " . $e->getMessage(), 0, $e);
        }
    }

    public function decode(string $payload): array
    {
        try {
            $decoded = NeonParser::decode($payload);

            return is_array($decoded) ? $decoded : (array) $decoded;
        } catch (Throwable $e) {
            throw new \InvalidArgumentException("Failed to decode NEON payload: " . $e->getMessage(), 0, $e);
        }
    }
}
