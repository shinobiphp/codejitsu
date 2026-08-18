<?php
declare(strict_types=1);

namespace Codejitsu\Codecs;

use Codejitsu\Contracts\Codec as CodecContract;
use Codejitsu\Contracts\Crypto\Sealer as SealerContract;
use Codejitsu\Contracts\Crypto\Signer as SignerContract;
use Codejitsu\Enums\Codecs as CodecFormat;
use Codejitsu\Enums\Crypto\SignatureAlgorithms as SignatureAlgorithm;
use Codejitsu\Enums\Crypto\EncryptionAlgorithms as EncryptionAlgorithm;
use RuntimeException;

abstract class Codec implements CodecContract
{
    protected const FORMAT = 'neon';
    protected const SEALER = 'openssl'; // Default to null (no auto-sealing)
    protected const SIGNER = 'hmac-sha256'; // Default to null (no auto-signing)

    protected ?SealerContract $sealer = null;
    protected ?SignerContract $signer = null;

    public function format(): CodecFormat
    {
        $formatValue = $this::FORMAT;
        return CodecFormat::tryFrom($formatValue) 
            ?? throw new RuntimeException("Invalid or unsupported format [{$formatValue}] defined in [" . static::class . "].");
    }

    public function getSealer(): ?SealerContract
    {
        if (is_null($this->sealer) && !is_null($this::SEALER)) {
            $algoValue = $this::SEALER;
            $algorithm = EncryptionAlgorithm::tryFrom($algoValue) 
                ?? throw new RuntimeException("Invalid sealer algorithm [{$algoValue}] defined in [" . static::class . "].");
            
            $this->sealer = $algorithm->to('$sealer')();
        }

        return $this->sealer; // Can be null if SEALER constant is null
    }

    public function setSealer(?SealerContract $sealer): static
    {
        $this->sealer = $sealer;
        return $this;
    }

    public function getSigner(): ?SignerContract
    {
        if (is_null($this->signer) && !is_null($this::SIGNER)) {
            $algoValue = $this::SIGNER;
            $algorithm = SignatureAlgorithm::tryFrom($algoValue) 
                ?? throw new RuntimeException("Invalid signer algorithm [{$algoValue}] defined in [" . static::class . "].");
            
            $this->signer = $algorithm->to('$signer');
        }

        return $this->signer; // Can be null if SIGNER constant is null
    }

    public function setSigner(?SignerContract $signer): static
    {
        $this->signer = $signer;
        return $this;
    }

    abstract public function encode(array $data): string;
    abstract public function decode(string $payload, ?string $key = null): array;

    public function isSealed(string $filePathOrPayload): bool
    {
        $sealer = $this->getSealer();
        if ($sealer === null) {
            return false; // Safely return false if no sealer is configured
        }

        $payload = $this->resolvePayload($filePathOrPayload);
        return $sealer->isSealed($payload);
    }

    public function inspect(string $filePathOrPayload, ?string $key = null): array
    {
        $payload = $this->resolvePayload($filePathOrPayload);
        $sealer = $this->getSealer();

        if ($sealer !== null && $this->isSealed($payload)) {
            $payload = $sealer->unseal($payload, $key);
        }

        return $this->decode($payload, $key);
    }

    protected function resolvePayload(string $filePathOrPayload): string
    {
        if (is_file($filePathOrPayload)) {
            return file_get_contents($filePathOrPayload) ?: '';
        }

        return $filePathOrPayload;
    }
}