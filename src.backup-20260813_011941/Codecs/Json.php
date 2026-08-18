<?php
declare(strict_types=1);

namespace Codejitsu\Codecs;

use Codejitsu\Codecs\Codec;

use JsonException;

class Json extends Codec
{
    protected const FORMAT = 'json';
    protected const SEALER = null; // No encryption by default
    protected const SIGNER = 'hmac-sha256'; // No signing by default

    public function encode(array $payload): string
    {
        try {
            return json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        } catch (JsonException $e) {
            throw new \InvalidArgumentException("Failed to encode payload to JSON: " . $e->getMessage(), 0, $e);
        }
    }

    public function decode(string $payload): array
    {
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

            // If it's already an array, return it directly. Otherwise, cast to array safely.
            return is_array($decoded) ? $decoded : (array) $decoded;
        } catch (JsonException $e) {
            throw new \InvalidArgumentException("Failed to decode JSON payload: " . $e->getMessage(), 0, $e);
        }
    }
}
