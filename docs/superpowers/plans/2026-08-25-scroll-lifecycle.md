# Scroll Lifecycle Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add deterministic Scroll signing, verification, sealing, unsealing, and bulk lifecycle commands suitable for a production build gate.

**Architecture:** Keep cryptography in `Codejitsu\\Crypto`, canonicalization in a focused Scroll lifecycle component, and orchestration in a Scroll lifecycle service. CLI commands remain thin adapters that select Scrolls through `ScrollCodex` and delegate lifecycle operations. Bulk sealing validates the complete selected/reference set before mutating seals.

**Tech Stack:** PHP 8.4+, PHPUnit 13, Sodium/OpenSSL, existing `Scroll`, `Envelope`, `ScrollCodex`, `Codec`, `Signer`, and `Sealer` abstractions.

**Spec:** `docs/superpowers/specs/2026-08-25-scroll-lifecycle-design.md`

## Global Constraints

- PHP requirement remains `>=8.4`.
- Public classes are behavioral contracts; avoid `Interface`/`Helper` suffixes.
- Use `declare(strict_types=1);` in every PHP file.
- Do not put cryptography or lifecycle policy in CLI commands.
- Signed payloads must be deterministic and must exclude signature/seal metadata.
- A Scroll cannot be sealed without a valid signature.
- Bulk sealing validates the entire selected set before applying seal mutations.
- `unseal` removes only seal state; it does not remove the signature.
- Tests must cover behavior and orchestration, not implementation details.

---

## File Map

### Crypto

- Modify: `src/Crypto/Signers/Ed25519.php` — implement the existing Ed25519 signer contract using Sodium.
- Modify: `src/Crypto/Signature.php` only if the algorithm/value representation needs a small compatibility adjustment.
- Modify: `src/Crypto/Seal.php` only if seal metadata needs a minimal representation adjustment.

### Scroll lifecycle

- Create: `src/Scrolls/Lifecycle/Canonicalizer.php` — recursively canonicalize Scroll payload arrays and encode deterministic bytes.
- Create: `src/Scrolls/Lifecycle/Lifecycle.php` — sign, verify, seal, unseal, and bulk orchestration over Scroll instances.

### Commands

- Create: `src/Commands/ScrollSign.php` — CLI-facing command scroll for signing.
- Create: `src/Commands/ScrollSeal.php` — CLI-facing command scroll for sealing.
- Create: `src/Commands/ScrollUnseal.php` — CLI-facing command scroll for unsealing.
- Create: `src/Commands/ScrollVerify.php` — CLI-facing command scroll for verification.

### Tests

- Create: `tests/Crypto/Ed25519Test.php` — signer round-trip and invalid signature behavior.
- Create: `tests/Scrolls/Lifecycle/CanonicalizerTest.php` — deterministic ordering and metadata exclusion.
- Create: `tests/Scrolls/Lifecycle/LifecycleTest.php` — state transitions, key errors, and bulk atomicity.
- Create: `tests/Commands/ScrollLifecycleCommandTest.php` — command selection and `--all` delegation.

### Documentation

- Create: `docs/superpowers/specs/2026-08-25-scroll-lifecycle-design.md` — approved design.
- Create: `docs/superpowers/plans/2026-08-25-scroll-lifecycle.md` — this implementation plan.

---

### Task 1: Implement the Ed25519 signer

**Files:**
- Modify: `src/Crypto/Signers/Ed25519.php`
- Test: `tests/Crypto/Ed25519Test.php`

**Interfaces:**
- Consumes: `Codejitsu\\Contracts\\Crypto\\Signer`, `Key`, and Sodium Ed25519 functions.
- Produces: `algorithm(): SignatureAlgorithms`, `sign(string $payload, string $secretKey): string`, and `verify(string $payload, string $signature, string $publicKey): bool`.

- [ ] **Step 1: Write the failing tests**

Use Sodium to generate a keypair, then assert a signed payload verifies and a modified payload does not:

```php
$keyPair = sodium_crypto_sign_keypair();
$private = sodium_crypto_sign_secretkey($keyPair);
$public = sodium_crypto_sign_publickey($keyPair);
$signer = new Ed25519();
$signature = $signer->sign('payload', $private);

self::assertTrue($signer->verify('payload', $signature, $public));
self::assertFalse($signer->verify('tampered', $signature, $public));
```

Also assert empty or malformed keys fail with an exception rather than silently returning a fabricated signature.

- [ ] **Step 2: Run the focused test and confirm failure**

Run: `composer test -- tests/Crypto/Ed25519Test.php`
Expected: FAIL because the current Ed25519 class returns an empty signature and `true` from verification.

- [ ] **Step 3: Implement the signer**

Implement the contract with Sodium and strict key validation. The core operations are:

```php
return base64_encode(sodium_crypto_sign_detached($payload, $secretKey));

return sodium_crypto_sign_verify_detached(
    base64_decode($signature, true),
    $payload,
    $publicKey,
);
```

Validate decoded signature length and key lengths before calling Sodium. `algorithm()` returns the existing Ed25519 enum value.

- [ ] **Step 4: Run the focused test**

Run: `composer test -- tests/Crypto/Ed25519Test.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Crypto/Signers/Ed25519.php tests/Crypto/Ed25519Test.php
git commit -m "feat: implement ed25519 signer"
```

---

### Task 2: Add deterministic Scroll canonicalization

**Files:**
- Create: `src/Scrolls/Lifecycle/Canonicalizer.php`
- Test: `tests/Scrolls/Lifecycle/CanonicalizerTest.php`

**Interfaces:**
- Consumes: `Scroll`/`ScrollContract`.
- Produces: `canonical(ScrollContract $scroll): string`.

- [ ] **Step 1: Write the failing tests**

Assert key order does not affect the canonical result and that lifecycle metadata is excluded:

```php
$left = ['b' => 2, 'a' => 1];
$right = ['a' => 1, 'b' => 2];

self::assertSame(
    $canonicalizer->array($left),
    $canonicalizer->array($right),
);
```

Create equivalent Scrolls with different `signature`/`seal` envelope state and assert their canonical payloads are identical.

- [ ] **Step 2: Run the focused test**

Run: `composer test -- tests/Scrolls/Lifecycle/CanonicalizerTest.php`
Expected: FAIL because the class does not exist.

- [ ] **Step 3: Implement canonicalization**

Recursively sort associative arrays by string key while preserving list ordering. Remove lifecycle-only fields before encoding. Encode using the existing NEON codec so the canonical representation stays inside the framework's codec model.

The public surface is intentionally tiny:

```php
final class Canonicalizer
{
    public function array(array $payload): array;
    public function scroll(ScrollContract $scroll): string;
}
```

- [ ] **Step 4: Run the focused test**

Run: `composer test -- tests/Scrolls/Lifecycle/CanonicalizerTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Scrolls/Lifecycle/Canonicalizer.php tests/Scrolls/Lifecycle/CanonicalizerTest.php
git commit -m "feat: add deterministic scroll canonicalization"
```

---

### Task 3: Implement Scroll lifecycle transitions

**Files:**
- Create: `src/Scrolls/Lifecycle/Lifecycle.php`
- Modify: `src/Envelope.php` only if required for safe lifecycle mutation.
- Test: `tests/Scrolls/Lifecycle/LifecycleTest.php`

**Interfaces:**
- Consumes: `ScrollContract`, `EnvelopeContract`, `Canonicalizer`, `Signer`, `Sealer`, and `Key`.
- Produces:
  - `sign(ScrollContract $scroll, Key $key): ScrollContract`
  - `verify(ScrollContract $scroll, Key $key): bool`
  - `seal(ScrollContract $scroll, Key $key): ScrollContract`
  - `unseal(ScrollContract $scroll, Key $key): ScrollContract`
  - `signAll(iterable $scrolls, Key $key): array`
  - `verifyAll(iterable $scrolls, Key $key): bool`
  - `sealAll(iterable $scrolls, Key $signingKey, Key $sealingKey): array`
  - `unsealAll(iterable $scrolls, Key $key): array`

- [ ] **Step 1: Write failing transition tests**

Cover:

```text
unsigned -> sign -> signed
signed -> verify -> true
signed -> seal -> sealed
sealed -> verify -> true
sealed -> unseal -> signed/unsealed
```

Assert sealing an unsigned Scroll throws. Assert tampering after signing makes `verify()` false. Assert unsealing leaves `signature` intact.

- [ ] **Step 2: Run the focused test**

Run: `composer test -- tests/Scrolls/Lifecycle/LifecycleTest.php`
Expected: FAIL because the lifecycle service does not exist.

- [ ] **Step 3: Implement individual transitions**

The service canonicalizes the Scroll, signs the canonical bytes, and stores a `Signature` containing the signer algorithm, key ID, and encoded signature. Verification resolves the stored signature and public key, then compares it with a newly canonicalized payload.

Sealing first calls `verify()`. Only after successful verification does it seal the envelope payload using the configured `Sealer`, storing a `Seal` with the algorithm and key ID. Unseal removes the seal only after successful decryption.

- [ ] **Step 4: Add bulk atomicity tests**

Prepare two valid Scrolls and one invalid Scroll. Assert `sealAll()` throws and none of the selected envelopes is sealed. Then run with all valid Scrolls and assert every Scroll is sealed.

- [ ] **Step 5: Implement bulk operations**

Materialize the iterable once, deduplicate by Scroll identity, verify all selected Scrolls first, then apply mutations. Do not mutate the input until the complete validation pass succeeds.

- [ ] **Step 6: Run focused lifecycle tests**

Run: `composer test -- tests/Scrolls/Lifecycle/LifecycleTest.php`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add src/Scrolls/Lifecycle/Lifecycle.php src/Envelope.php tests/Scrolls/Lifecycle/LifecycleTest.php
git commit -m "feat: add scroll lifecycle service"
```

---

### Task 4: Add Scroll lifecycle CLI commands

**Files:**
- Create: `src/Commands/ScrollSign.php`
- Create: `src/Commands/ScrollSeal.php`
- Create: `src/Commands/ScrollUnseal.php`
- Create: `src/Commands/ScrollVerify.php`
- Test: `tests/Commands/ScrollLifecycleCommandTest.php`

**Interfaces:**
- Consumes: `ScrollCodex` selection and `Scrolls\\Lifecycle\\Lifecycle`.
- Produces: command-scroll behavior for `scroll:sign`, `scroll:seal`, `scroll:unseal`, and `scroll:verify`.

- [ ] **Step 1: Write failing command tests**

Assert each command resolves an explicit URI and that `--all` uses every registered Scroll. Assert `verify` reports failure through its return value/exception path rather than swallowing it.

- [ ] **Step 2: Run focused command tests**

Run: `composer test -- tests/Commands/ScrollLifecycleCommandTest.php`
Expected: FAIL because the commands do not exist.

- [ ] **Step 3: Implement thin command adapters**

Each command accepts a target plus an `all` option. Selection is performed through `ScrollCodex`; lifecycle operations are delegated to `Lifecycle`. Commands do not call Sodium, OpenSSL, or codec internals directly.

The target resolution shape is:

```php
$scrolls = $all
    ? $codex->all(true)
    : [$codex->resolve($target)];
```

Filter the result to `ScrollContract` instances before lifecycle delegation.

- [ ] **Step 4: Run focused command tests**

Run: `composer test -- tests/Commands/ScrollLifecycleCommandTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Commands/ScrollSign.php src/Commands/ScrollSeal.php src/Commands/ScrollUnseal.php src/Commands/ScrollVerify.php tests/Commands/ScrollLifecycleCommandTest.php
git commit -m "feat: add scroll lifecycle commands"
```

---

### Task 5: Run the complete suite and verify production invariants

**Files:**
- Modify: any implementation/test files only if the complete suite exposes an integration regression.

- [ ] **Step 1: Run all tests**

Run: `composer test`
Expected: all existing and new tests pass with zero risky tests.

- [ ] **Step 2: Verify lifecycle invariants**

Run the focused lifecycle suite and confirm:

```text
unsigned cannot seal
invalid signature cannot seal
valid signature can seal
sealed payload verifies
unseal preserves signature
bulk failure leaves all selected Scrolls unsealed
bulk success seals every selected Scroll
```

- [ ] **Step 3: Commit any test-only correction**

```bash
git add .
git commit -m "test: verify scroll lifecycle invariants"
```

- [ ] **Step 4: Report branch state**

Confirm the feature branch contains the lifecycle implementation and is ready for PR/merge into `main`.
