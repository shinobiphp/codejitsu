<?php

declare(strict_types=1);

namespace Codejitsu;

use Codejitsu\Contracts\Codec as CodecContract;
use Codejitsu\Contracts\Envelope as EnvelopeContract;
use Codejitsu\Contracts\EnvelopeCodex as EnvelopeCodexContract;
use Codejitsu\Enums\Codecs;
use OutOfBoundsException;
use RuntimeException;

class EnvelopeCodex extends Codex implements EnvelopeCodexContract
{
    /**
     * @var array<string, EnvelopeContract>
     */
    protected array $envelopes = [];

    public function __construct(
        ?Codecs $codec = null,
        array $itemsOrEnvelopes = [],
    ) {
        $this->codec = $codec ?? Codecs::default();

        $cleanItems = [];

        foreach ($itemsOrEnvelopes as $key => $item) {
            if ($item instanceof EnvelopeContract) {
                $name = is_string($key)
                    ? $key
                    : $this->envelopeName($item);

                $this->envelopes[
                    strtolower($name)
                ] = $item;

                continue;
            }

            $cleanItems[$key] = $item;
        }

        parent::__construct($cleanItems);
    }

    public ?Codecs $codec;

    public function loadEnvelope(
        EnvelopeContract $envelope,
        ?string $name = null,
    ): static {
        $key = strtolower(
            $name ?? $this->envelopeName($envelope),
        );

        $this->envelopes[$key] = $envelope;

        unset($this->items[$key]);

        return $this;
    }

    public function has(
        string $target,
    ): bool {
        $key = strtolower($target);

        return parent::has($key)
            || isset($this->envelopes[$key]);
    }

    public function get(
        string $target,
    ): mixed {
        $key = strtolower($target);

        if (parent::has($key)) {
            return parent::get($key);
        }

        if (!isset($this->envelopes[$key])) {
            throw new OutOfBoundsException(
                sprintf(
                    'Entity [%s] could not be found or hydrated.',
                    $target,
                ),
            );
        }

        $hydrated = $this->hydrateFromEnvelope(
            $this->envelopes[$key],
        );

        $this->items[$key] = $hydrated;

        unset($this->envelopes[$key]);

        return $hydrated;
    }

    public function isHydrated(
        string $target,
    ): bool {
        return parent::has($target);
    }

    public function all(
        bool $hydrateAll = false,
    ): array {
        if (!$hydrateAll) {
            return $this->items;
        }

        foreach ($this->envelopes as $key => $envelope) {
            if (!isset($this->items[$key])) {
                $this->items[$key] =
                    $this->hydrateFromEnvelope(
                        $envelope,
                    );
            }
        }

        $this->envelopes = [];

        return $this->items;
    }

    /**
     * Hydrate an item from an envelope.
     *
     * Subclasses may override this to turn decoded
     * envelope data into a domain-specific object.
     */
    protected function hydrateFromEnvelope(
        EnvelopeContract $envelope,
    ): mixed {
        return $this->decodeEnvelope($envelope);
    }

    /**
     * Decode an envelope payload.
     *
     * The generic EnvelopeCodex deliberately knows nothing
     * about the object represented by the envelope.
     *
     * @return array<string, mixed>
     */
    protected function decodeEnvelope(
        EnvelopeContract $envelope,
    ): array {
        $codec = $this->resolveCodec($envelope);

        $payload = $envelope->data;

        /*
         * Verify the signature against the exact bytes that
         * are going to be unsealed.
         */
        if ($envelope->signed) {
            $signer = $codec->signer
                ?? throw new RuntimeException(
                    'Envelope is signed but no signer is configured.',
                );

            if (!$signer->verify(
                $payload,
                $envelope->signature->value,
            )) {
                throw new RuntimeException(
                    'Envelope signature verification failed.',
                );
            }
        }

        if ($envelope->sealed) {
            $sealer = $codec->sealer
                ?? throw new RuntimeException(
                    'Envelope is sealed but no sealer is configured.',
                );

            $payload = $sealer->unseal(
                $payload,
            );
        }

        $decoded = $codec->decode(
            $payload,
        );

        if (!is_array($decoded)) {
            throw new RuntimeException(
                'Decoded envelope payload must be an array.',
            );
        }

        return $decoded;
    }

    protected function resolveCodec(
        EnvelopeContract $envelope,
    ): CodecContract {
        return $envelope->codec->make();
    }

    protected function envelopeName(
        EnvelopeContract $envelope,
    ): string {
        return $envelope->name;
    }
}