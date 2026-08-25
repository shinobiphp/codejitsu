<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls\Lifecycle;

use Codejitsu\Contracts\Crypto\Sealer as SealerContract;
use Codejitsu\Contracts\Crypto\Signer as SignerContract;
use Codejitsu\Crypto\Key;
use Codejitsu\Crypto\Seal;
use Codejitsu\Crypto\Signature;
use Codejitsu\Scrolls\Scroll;
use InvalidArgumentException;
use LogicException;

final class Lifecycle
{
    public function __construct(
        private readonly Canonicalizer $canonicalizer,
        private readonly SignerContract $signer,
        private readonly SealerContract $sealer,
    ) {}

    public function sign(Scroll $scroll, Key $key): Scroll
    {
        $envelope = $this->envelope($scroll);
        $payload = $this->canonicalizer->scroll($scroll);

        $envelope->signature = new Signature(
            $this->signer->algorithm(),
            $key->id,
            $this->signer->sign($payload, $key->contents),
        );

        return $scroll;
    }

    public function verify(Scroll $scroll, Key $key): bool
    {
        $envelope = $this->envelope($scroll);
        if (!$envelope->signed) {
            return false;
        }

        if ($envelope->signature->algorithm !== $this->signer->algorithm()) {
            return false;
        }

        return $this->signer->verify(
            $this->canonicalizer->scroll($scroll),
            $envelope->signature->value,
            $key->contents,
        );
    }

    public function seal(Scroll $scroll, Key $key): Scroll
    {
        if (!$this->verify($scroll, $key)) {
            throw new LogicException(sprintf(
                'Scroll [%s] cannot be sealed without a valid signature.',
                $scroll->name,
            ));
        }

        $envelope = $this->envelope($scroll);
        $envelope->data = $this->sealer->seal(
            $this->canonicalizer->scroll($scroll),
            $key->contents,
        );
        $envelope->seal = new Seal(
            $this->sealer->algorithm(),
            $key->id,
            '',
            '',
        );

        return $scroll;
    }

    public function unseal(Scroll $scroll, Key $key): Scroll
    {
        $envelope = $this->envelope($scroll);
        if (!$envelope->sealed) {
            return $scroll;
        }

        $envelope->data = $this->sealer->unseal(
            $envelope->data,
            $key->contents,
        );
        $envelope->seal = null;

        return $scroll;
    }

    /** @param iterable<Scroll> $scrolls */
    public function signAll(iterable $scrolls, Key $key): array
    {
        $items = $this->unique($scrolls);
        foreach ($items as $scroll) {
            $this->sign($scroll, $key);
        }

        return $items;
    }

    /** @param iterable<Scroll> $scrolls */
    public function verifyAll(iterable $scrolls, Key $key): bool
    {
        foreach ($this->unique($scrolls) as $scroll) {
            if (!$this->verify($scroll, $key)) {
                return false;
            }
        }

        return true;
    }

    /** @param iterable<Scroll> $scrolls */
    public function sealAll(iterable $scrolls, Key $key): array
    {
        $items = $this->unique($scrolls);

        foreach ($items as $scroll) {
            if (!$this->verify($scroll, $key)) {
                throw new LogicException(sprintf(
                    'Bulk seal validation failed for Scroll [%s].',
                    $scroll->name,
                ));
            }
        }

        foreach ($items as $scroll) {
            $this->seal($scroll, $key);
        }

        return $items;
    }

    /** @param iterable<Scroll> $scrolls */
    public function unsealAll(iterable $scrolls, Key $key): array
    {
        $items = $this->unique($scrolls);
        foreach ($items as $scroll) {
            $this->unseal($scroll, $key);
        }

        return $items;
    }

    private function envelope(Scroll $scroll): \Codejitsu\Contracts\Envelope
    {
        return $scroll->getEnvelope()
            ?? throw new LogicException(sprintf(
                'Scroll [%s] has no envelope and cannot participate in lifecycle operations.',
                $scroll->name,
            ));
    }

    /** @param iterable<Scroll> $scrolls @return list<Scroll> */
    private function unique(iterable $scrolls): array
    {
        $unique = [];
        foreach ($scrolls as $scroll) {
            if (!$scroll instanceof Scroll) {
                throw new InvalidArgumentException('Lifecycle operations require Scroll instances.');
            }

            $identity = sprintf('%s://%s#%s', $scroll->type instanceof \Codejitsu\Enums\Scrolls\Types ? $scroll->type->value : $scroll->type, $scroll->name, $scroll->version);
            $unique[$identity] = $scroll;
        }

        return array_values($unique);
    }
}
