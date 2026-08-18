<?php
declare(strict_types=1);

namespace Codejitsu\Codecs;

use Codejitsu\Codecs\Codec;

use Throwable;

class Php extends Codec
{
    protected const FORMAT = 'php';
    protected const SEALER = null; // No encryption by default
    protected const SIGNER = 'hmac-sha256'; // No signing by default

    public function encode(array $payload): string
    {
        try {
            $serialized = serialize($payload);
            if ($serialized === false) {
                throw new \RuntimeException("Failed to serialize payload.");
            }
            return $serialized;
        } catch (Throwable $e) {
            throw new \InvalidArgumentException("Failed to encode payload to PHP format: " . $e->getMessage(), 0, $e);
        }
    }

    public function decode(string $payload): array
    {
        try {
            $decoded = unserialize($payload, ['allowed_classes' => false]);

            return is_array($decoded) ? $decoded : (array) $decoded;
        } catch (Throwable $e) {
            throw new \InvalidArgumentException("Failed to decode PHP payload: " . $e->getMessage(), 0, $e);
        }
    }
}
