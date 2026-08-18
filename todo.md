# Codejitsu Codebase Fixes, Refactoring Plan, & Core Primitives

## 1. Namespace & Contract Alignment
- [X] **Fix Codec Contract Mismatch:** Unify codec contracts to use `Codejitsu\Contracts\Codec` and remove any references to `Codejitsu\Contracts\Scrolls\Codec`.
- [ ] **Fix Codex & Types Class Mapping:** Resolve discrepancies between `Types::map()` (storing `'class' => AppScroll::class`) and how `Codex` looks up target classes (`$this->type->to('$scrollClass')`). Stop making `Types` directly responsible for instantiation.
- [ ] **Fix Configuration Environment Keys:** Update `Codecs::default()` to query `CODEJITSU_CODEC` (matching the `Environment` setup) instead of `CODEJITSU_DEFAULT_CODEC`.

## 2. Type Safety & Cryptography Fixes
- [ ] **Fix EnhancedEnum Object Key Bug:** Remove object dimensions entirely from `EnhancedEnum` or enforce explicit canonical string/enum representations to prevent runtime TypeErrors from objects in `$index['dimensions'][$dimVal]`.
- [ ] **Fix ErrorPolicies Signatures:** Update error policies mapping to accept `Throwable|string` (or consistently `Throwable`), matching what `Environment::error()` passes.
- [ ] **Fix Algorithm & Crypto Implementations:** - Stop mapping `Algorithm::ED25519` to `Hmac::class` (fail loudly until Ed25519 is implemented).
  - Stop mapping `xchacha20poly1305` to `OpenSSL::class`; map each algorithm to its actual implementation.
  - Fix interface vs implementation discrepancies in crypto contracts (`sign`/`verify` parameters for `Hmac`).
  - Adjust `Sealer` contracts and implementations to cleanly separate key management and dependency injection:
    ```php
    seal(string $payload): string
    unseal(string $payload): string
    ```
    leaving key management outside the primitive.

## 3. Storage, Discovery, & Pipeline Architecture
- [ ] **Redesign Filesystem Discovery:** Decouple storage from rigid path structures (`scrolls/apps/*.app`) where scroll type determines extension. Support multi-codec discovery (`.neon`, `.lua`, `.py`, `.js`, `.wasm`) via file format detection using `CodecResolver`.
- [ ] **Fix Codex Lazy Hydration:** Modify `$codex->all()` to return envelopes without eagerly hydrating scrolls, and introduce an explicit `$codex->hydrateAll()` method for full bulk loading.
- [ ] **Refactor Codex::get() Pipeline:** Split monolithic `get()` logic into an explicit, sequential pipeline:
  `Codex` → `Envelope` → `CodecResolver` → `Codec` → `ScrollResolver` → `Scroll`.

## 4. Architectural Cleanup & Primitives
- [ ] **Remove Deprecated Codice Classes & Per-Type Ownership:** Delete `Codejitsu\Contracts\Scrolls\Codice`, `Codejitsu\Scrolls\Codice`, old `Codejitsu\Contracts\Scrolls\Store` forms, and per-type Codex registries. Handle sub-registries directly inside the new root `Codex` via filtering/navigation.
- [ ] **Implement New `ScrollEnvelope` Primitive:** Create the new `Envelope` structure:
    ```php
    declare(strict_types=1);

    namespace Codejitsu\Scrolls;

    use Codejitsu\Enums\Codecs;
    use Codejitsu\Enums\Scrolls\Types;

    final readonly class Envelope
    {
        public function __construct(
            public string $uri,
            // ... required envelope properties
        ) {}
    }
    ```