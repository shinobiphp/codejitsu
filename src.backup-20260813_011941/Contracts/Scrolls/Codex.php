<?php
declare(strict_types=1);

namespace Codejitsu\Contracts\Scrolls;

use ArrayAccess;
use Codejitsu\Contracts\Codecs\Codec as CodecContract;
use Codejitsu\Contracts\Crypto\Sealer as SealerContract;
use Codejitsu\Contracts\Crypto\Signer as SignerContract;
use Codejitsu\Enums\Codecs as Codec;
use Codejitsu\Enums\Crypto\Algorithms as Algorithm;
use Codejitsu\Enums\ErrorPolicies as ErrorPolicy;
use Codejitsu\Enums\ScrollTypes as ScrollType;
use Countable;
use IteratorAggregate;

interface Codex extends Countable, IteratorAggregate, ArrayAccess
{
    public ?ScrollType $type { get; set; }
    
    // Now nullable to enable parent fallback resolution via Codice
    public ?CodecContract $codec { get; set; }
    public ?SealerContract $sealer { get; set; }
    public ?SignerContract $signer { get; set; }
    
    public ErrorPolicy $missingSignaturePolicy { get; set; }
    public ErrorPolicy $invalidSignaturePolicy { get; set; }

    public function index(string $name, string|array $manifest): static;
    public function register(Scroll $scroll): static;
    public function registerEnvelope(string $name, Envelope $envelope): static;
    
    public function has(string $target): bool;
    public function get(string $target): Scroll;
    public function isHydrated(string $target): bool;
    public function all(bool $hydrateAll = false): array;

    public function __get(string $name): Scroll;
    public function __isset(string $name): bool;
    public function __call(string $method, array $args): mixed;
    public function __invoke(string $target, mixed ...$args): mixed;
}