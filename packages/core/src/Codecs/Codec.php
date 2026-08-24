<?php

declare(strict_types=1);

namespace Codejitsu\Codecs;

use Codejitsu\Contracts\Codec as CodecContract;
use Codejitsu\Contracts\Crypto\Sealer as SealerContract;
use Codejitsu\Contracts\Crypto\Signer as SignerContract;
use Codejitsu\Enums\Codecs as CodecFormat;
use Codejitsu\Enums\Crypto\EncryptionAlgorithms;
use Codejitsu\Enums\Crypto\SignatureAlgorithms;
use RuntimeException;

abstract class Codec implements CodecContract
{
    protected const FORMAT = 'neon';

    protected const SEALER = 'aes-256-gcm';

    protected const SIGNER = 'hmac-sha256';

    private ?SealerContract $sealerInstance = null;

    private ?SignerContract $signerInstance = null;

    public ?SealerContract $sealer {
        get => $this->sealerInstance ??= $this->createSealer();
        set (?SealerContract $value) => $this->sealerInstance = $value;
    }

    public ?SignerContract $signer {
        get => $this->signerInstance ??= $this->createSigner();
        set (?SignerContract $value) => $this->signerInstance = $value;
    }

    public function format(): CodecFormat
    {
        return CodecFormat::tryFrom(static::FORMAT)
            ?? throw new RuntimeException(
                sprintf(
                    'Invalid codec format [%s] defined in [%s].',
                    static::FORMAT,
                    static::class,
                ),
            );
    }

    public function setSealer(
        SealerContract $sealer,
    ): static {
        $this->sealer = $sealer;

        return $this;
    }

    public function setSigner(
        SignerContract $signer,
    ): static {
        $this->signer = $signer;

        return $this;
    }

    public function isSealed(
        string $filePathOrPayload,
    ): bool {
        $sealer = $this->sealer;

        if ($sealer === null) {
            return false;
        }

        return $sealer->isSealed(
            $this->resolvePayload($filePathOrPayload),
        );
    }

    public function inspect(
        string $filePathOrPayload,
        ?string $key = null,
    ): array {
        $payload = $this->resolvePayload(
            $filePathOrPayload,
        );

        $sealer = $this->sealer;

        if (
            $sealer !== null
            && $sealer->isSealed($payload)
        ) {
            if ($key === null) {
                throw new RuntimeException(
                    'A decryption key is required to inspect a sealed payload.',
                );
            }

            $payload = $sealer->unseal(
                $payload,
                $key,
            );
        }

        return $this->decode(
            $payload,
            $key,
        );
    }

    abstract public function encode(
        array $data,
    ): string;

    abstract public function decode(
        string $payload,
        ?string $key = null,
    ): array;

    protected function createSealer(): ?SealerContract
    {
        if (static::SEALER === null) {
            return null;
        } else {
            $algorithm = EncryptionAlgorithms::tryFrom(
                static::SEALER,
            );
        }

        if ($algorithm === null) {
            throw new RuntimeException(
                sprintf(
                    'Invalid sealer algorithm [%s] in [%s].',
                    static::SEALER,
                    static::class,
                ),
            );
        }

        $factory = $algorithm->to('$sealer');

        if ($factory instanceof \Closure) {
            $sealer = $factory();

            if (!$sealer instanceof SealerContract) {
                throw new RuntimeException(
                    sprintf(
                        'Sealer factory for [%s] did not return a SealerContract.',
                        $algorithm->value,
                    ),
                );
            }

            return $sealer;
        }

        if (
            is_string($factory)
            && class_exists($factory)
        ) {
            $sealer = new $factory();

            if (!$sealer instanceof SealerContract) {
                throw new RuntimeException(
                    sprintf(
                        'Sealer class [%s] does not implement SealerContract.',
                        $factory,
                    ),
                );
            }

            return $sealer;
        }

        throw new RuntimeException(
            sprintf(
                'No valid sealer factory configured for [%s].',
                $algorithm->value,
            ),
        );
    }

    protected function createSigner(): ?SignerContract
    {
        if (static::SIGNER === null) {
            return null;
        }

        $algorithm = SignatureAlgorithms::tryFrom(
            static::SIGNER,
        );

        if ($algorithm === null) {
            throw new RuntimeException(
                sprintf(
                    'Invalid signer algorithm [%s] in [%s].',
                    static::SIGNER,
                    static::class,
                ),
            );
        }

        $factory = $algorithm->to('$signer');

        if ($factory instanceof \Closure) {
            $signer = $factory();

            if (!$signer instanceof SignerContract) {
                throw new RuntimeException(
                    sprintf(
                        'Signer factory for [%s] did not return a SignerContract.',
                        $algorithm->value,
                    ),
                );
            }

            return $signer;
        }

        if (
            is_string($factory)
            && class_exists($factory)
        ) {
            $signer = new $factory();

            if (!$signer instanceof SignerContract) {
                throw new RuntimeException(
                    sprintf(
                        'Signer class [%s] does not implement SignerContract.',
                        $factory,
                    ),
                );
            }

            return $signer;
        }

        throw new RuntimeException(
            sprintf(
                'No valid signer factory configured for [%s].',
                $algorithm->value,
            ),
        );
    }

    protected function resolvePayload(
        string $filePathOrPayload,
    ): string {
        if (!is_file($filePathOrPayload)) {
            return $filePathOrPayload;
        }

        $payload = file_get_contents(
            $filePathOrPayload,
        );

        if ($payload === false) {
            throw new RuntimeException(
                sprintf(
                    'Unable to read codec payload file [%s].',
                    $filePathOrPayload,
                ),
            );
        }

        return $payload;
    }
}