<?php
declare(strict_types=1);

namespace Codejitsu\Scrolls;

use ArrayIterator;
use Codejitsu\Contracts\Codec as CodecContract;
use Codejitsu\Contracts\Scrolls\Codice as CodiceContract;
use Codejitsu\Contracts\Scrolls\Codex as CodexContract;
use Codejitsu\Contracts\Scrolls\Envelope as EnvelopeContract;
use Codejitsu\Contracts\Scrolls\Scroll as ScrollContract;
use Codejitsu\Contracts\Scrolls\Store as StoreContract;
use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use Codejitsu\Enums\Codecs;
use OutOfBoundsException;
use RuntimeException;
use Traversable;

class Codex implements CodexContract
{
    /**
     * Optional class-level override for scroll type. If null, type is dynamically configured.
     */
    public const ?ScrollTypes SCROLL_TYPE = null;

    /**
     * Unhydrated envelopes indexed by scroll name.
     *
     * @var array<string, EnvelopeContract>
     */
    protected array $envelopes = [];

    /**
     * Fully hydrated scroll instances cached by scroll name.
     *
     * @var array<string, ScrollContract>
     */
    protected array $hydrated = [];

    public function __construct(
        public CodiceContract $codice {
            get => $this->codice;
            set {
                $this->codice = $value;
                if (!isset($this->codec) && method_exists($this->codice, 'getCodec')) {
                    $this->codec = $this->codice->getCodec();
                } elseif (!isset($this->codec) && property_exists($this->codice, 'codec')) {
                    $this->codec = $this->codice->codec;
                }
            }
        },
        public ScrollTypes $type = self::SCROLL_TYPE ?? ScrollTypes::APP {
            get => static::SCROLL_TYPE ?? $this->type;
            set {
                if (static::SCROLL_TYPE !== null) {
                    $onError = method_exists(static::SCROLL_TYPE, 'to') ? (static::SCROLL_TYPE->to('$onError') ?? null) : null;
                    if ($onError instanceof \Closure) {
                        $onError('Cannot mutate scroll type: Codex defines a static SCROLL_TYPE override.');
                    }
                    throw new RuntimeException("Cannot mutate scroll type: Codex defines a static SCROLL_TYPE override.");
                }

                $normalized = is_string($value) 
                    ? (ScrollTypes::tryValue(ScrollTypes::normalize($value)) ?? ScrollTypes::tryValue(rtrim(ScrollTypes::normalize($value), 's')) ?? $value)
                    : $value;

                if (!$normalized instanceof ScrollTypes) {
                    $onError = method_exists(ScrollTypes::class, 'to') ? (ScrollTypes::to('$onError') ?? null) : null;
                    if (!$onError && method_exists($this->type, 'to')) {
                        $onError = $this->type->to('$onError');
                    }
                    if ($onError instanceof \Closure) {
                        $onError("Invalid scroll type representation [{$value}].");
                    }
                    throw new OutOfBoundsException("Invalid scroll type representation [{$value}].");
                }

                $this->type = $normalized;
            }
        },
        public ?CodecContract $codec = null {
            get {
                if (isset($this->codec)) {
                    return $this->codec;
                }
                
                // 1. Check if type defines or overrides a codec mapping
                if (method_exists($this->type, 'to')) {
                    $typeCodecFactory = $this->type->to('$codec');
                    if ($typeCodecFactory instanceof \Closure) {
                        $resolved = $typeCodecFactory();
                        if ($resolved instanceof CodecContract) {
                            return $resolved;
                        }
                    }
                }

                // 2. Check Codice getCodec method or property
                if (method_exists($this->codice, 'getCodec')) {
                    $codiceCodec = $this->codice->getCodec();
                    if ($codiceCodec instanceof CodecContract) {
                        return $codiceCodec;
                    }
                }

                if (isset($this->codice->codec) && $this->codice->codec instanceof CodecContract) {
                    return $this->codice->codec;
                }

                // 3. Fallback: Get the default from the Codecs enum
                if (method_exists(Codecs::class, 'default')) {
                    $defaultCodec = Codecs::default();
                    if ($defaultCodec instanceof CodecContract) {
                        return $defaultCodec;
                    }
                    if (is_string($defaultCodec)) {
                        $normalized = Codecs::tryValue(Codecs::normalize($defaultCodec));
                        if ($normalized && method_exists($normalized, 'make')) {
                            return $normalized->make();
                        }
                    }
                }

                return null;
            }
            set => $this->codec = is_string($value) 
                ? (Codecs::tryValue(Codecs::normalize($value))?->make() ?? $value) 
                : $value;
        },
    ) {}

    /**
     * Target scroll class retrieved dynamically from type mapping (read-only).
     */
    public string $targetScrollClass {
        get => method_exists($this->type, 'to') ? ($this->type->to('class') ?? AbstractScroll::class) : AbstractScroll::class;
    }

    /**
     * Store retrieved dynamically from the bound Codice (read-only hooked property).
     */
    public ?StoreContract $store {
        get => method_exists($this->codice, 'getStore') ? $this->codice->getStore() : ($this->codice->store ?? null);
    }

    /**
     * Register a pre-instantiated, hydrated Scroll.
     */
    public function register(ScrollContract $scroll): static
    {
        $key = strtolower($scroll->name);
        $this->hydrated[$key] = $scroll;
        unset($this->envelopes[$key]);

        return $this;
    }

    /**
     * Register an unhydrated Envelope for lazy resolution.
     */
    public function registerEnvelope(string $name, EnvelopeContract $envelope): static
    {
        $key = strtolower($name);
        $this->envelopes[$key] = $envelope;
        unset($this->hydrated[$key]);

        return $this;
    }

    /**
     * Check if a scroll exists either as a hydrated instance, raw envelope, or in the store.
     */
    public function has(string $target): bool
    {
        $key = strtolower($target);

        if (isset($this->hydrated[$key]) || isset($this->envelopes[$key])) {
            return true;
        }

        $activeStore = $this->store;
        return $activeStore !== null && $activeStore->has($this->type, $key);
    }

    /**
     * Retrieve and lazy-hydrate a scroll by name.
     */
    public function get(string $target): ScrollContract
    {
        $key = strtolower($target);

        if (isset($this->hydrated[$key])) {
            return $this->hydrated[$key];
        }

        $activeStore = $this->store;

        if (!isset($this->envelopes[$key]) && $activeStore !== null) {
            $envelope = $activeStore->get($this->type, $key);
            if ($envelope !== null) {
                $this->envelopes[$key] = $envelope;
            }
        }

        if (!isset($this->envelopes[$key])) {
            $typeName = method_exists($this->type, 'plural') ? $this->type->plural() : $this->type->value;
            throw new OutOfBoundsException(
                "Scroll [{$target}] not found in Codex [{$typeName}]."
            );
        }

        $envelope = $this->envelopes[$key];
        $scroll = $this->hydrateEnvelope($envelope);

        unset($this->envelopes[$key]);
        return $this->hydrated[$key] = $scroll;
    }

    /**
     * Internal pipeline to verify, unseal, decode, and instantiate an envelope into a Scroll.
     */
    protected function hydrateEnvelope(EnvelopeContract $envelope): ScrollContract
    {
        $activeCodec = $this->codec;
        if (!$activeCodec) {
            throw new RuntimeException("Cannot hydrate envelope: No Codec configured for Codex.");
        }

        // Determine Codec Enum representation if available for fallback queries
        $codecEnum = null;
        if (is_object($activeCodec) && method_exists(Codecs::class, 'tryValue')) {
            $reflection = new \ReflectionClass($activeCodec);
            $codecEnum = Codecs::tryValue(Codecs::normalize($reflection->getShortName())) 
                ?? (method_exists($activeCodec, 'getName') ? Codecs::tryValue(Codecs::normalize($activeCodec->getName())) : null);
        }

        $payload = $envelope->data;

        // 1. Signature Verification via Codec Definition or Enum Fallback
        if ($envelope->signature !== null) {
            $signer = method_exists($activeCodec, 'getSigner') ? $activeCodec->getSigner() : null;
            if (!$signer && $codecEnum !== null && method_exists($codecEnum, 'to')) {
                $signer = $codecEnum->to('$signer');
            }

            if (!$signer) {
                throw new RuntimeException("Envelope is signed, but active Codec provides no Signer.");
            }

            if (!method_exists($signer, 'verify') || !$signer->verify($payload, $envelope->signature)) {
                throw new RuntimeException("Signature verification failed for envelope.");
            }
        }

        // 2. Unseal Payload via Codec Definition or Enum Fallback
        if ($envelope->sealed) {
            $sealer = method_exists($activeCodec, 'getSealer') ? $activeCodec->getSealer() : null;
            if (!$sealer && $codecEnum !== null && method_exists($codecEnum, 'to')) {
                $sealer = $codecEnum->to('$sealer');
            }

            if (!$sealer) {
                throw new RuntimeException("Envelope is sealed, but active Codec provides no Sealer.");
            }

            if (!method_exists($sealer, 'unseal')) {
                throw new RuntimeException("Sealer implementation missing unseal protocol.");
            }

            $payload = $sealer->unseal($payload);
        }

        // 3. Decode Payload
        $decodedData = $activeCodec->decode($payload);

        // 4. Instantiate via static Factory on target Scroll class derived from type enums map
        /** @var class-string<AbstractScroll> $class */
        $class = $this->targetScrollClass;

        if (method_exists($class, 'make')) {
            return $class::make($envelope, $decodedData);
        }

        $instance = new $class();
        if (method_exists($instance, 'hydrate')) {
            $instance->hydrate($decodedData);
        }

        return $instance;
    }

    public function all(): array
    {
        $activeStore = $this->store;
        if ($activeStore !== null) {
            $discovered = $activeStore->all($this->type);
            foreach ($discovered as $name => $envelope) {
                if (!isset($this->hydrated[$name])) {
                    $this->envelopes[$name] = $envelope;
                }
            }
        }

        foreach (array_keys($this->envelopes) as $key) {
            $this->get($key);
        }

        return $this->hydrated;
    }

    public function __get(string $name): ScrollContract
    {
        return $this->get($name);
    }

    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    public function __call(string $method, array $args): mixed
    {
        $scroll = $this->get($method);

        if (empty($args)) {
            return $scroll;
        }

        return $scroll(...$args);
    }

    public function __invoke(string $target, mixed ...$args): mixed
    {
        return $this->__call($target, $args);
    }

    public function count(): int
    {
        return count($this->all());
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->all());
    }
}
