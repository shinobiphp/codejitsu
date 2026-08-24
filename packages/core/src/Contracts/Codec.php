<?php

declare(strict_types=1);

namespace Codejitsu\Contracts;

use Codejitsu\Contracts\Crypto\Sealer as SealerContract;
use Codejitsu\Contracts\Crypto\Signer as SignerContract;
use Codejitsu\Enums\Codecs as CodecFormat;

interface Codec
{
    public function format(): CodecFormat;

    public function isSealed(
        string $filePathOrPayload,
    ): bool;

    /**
     * Inspect and decode a payload.
     *
     * @return array<string, mixed>
     */
    public function inspect(
        string $filePathOrPayload,
        ?string $key = null,
    ): array;

    public function encode(array $data): string;

    /**
     * @return array<string, mixed>
     */
    public function decode(
        string $payload,
        ?string $key = null,
    ): array;

    public function setSealer(
        SealerContract $sealer,
    ): static;

    public function setSigner(
        SignerContract $signer,
    ): static;
}