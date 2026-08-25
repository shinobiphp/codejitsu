# Codejitsu Scroll Lifecycle Design

**Status:** Approved
**Date:** 2026-08-25

## Goal

Give Scrolls a deterministic lifecycle for signing, sealing, verifying, and unsealing, with CLI orchestration capable of applying the lifecycle to one scroll or an entire resolved set for production builds.

## Scope

This phase covers:

- deterministic canonical payload generation for a Scroll;
- Ed25519 signing and verification through the existing crypto abstraction;
- Scroll signature state stored on the Scroll envelope;
- seal/unseal state using the existing codec/sealer abstraction;
- lifecycle orchestration for individual and bulk operations;
- CLI commands for `scroll:sign`, `scroll:seal`, `scroll:unseal`, and `scroll:verify`;
- `--all` selection and explicit selectors;
- dependency/reference-aware bulk processing;
- atomic bulk semantics: a bulk seal operation must not leave a partially sealed in-memory result when validation fails;
- tests covering primitives, lifecycle transitions, selectors, references, and CLI behavior.

This phase does **not** introduce manifests, trust-chain/key-distribution infrastructure, package management, data stores, or additional Scroll types. Those build on the lifecycle later.

## Lifecycle

```text
discover -> resolve -> canonicalize -> sign -> verify -> seal -> verify
```

`unseal` is a development/build operation that removes the seal while retaining the signature unless explicitly requested otherwise by a future lifecycle policy.

A Scroll may be:

- unsigned and unsealed;
- signed and unsealed;
- signed and sealed.

A Scroll may not be sealed without a valid signature.

## Canonicalization

The signed payload is derived from `Scroll::toArray()` after recursively normalizing associative keys in deterministic order. Lifecycle metadata containing the signature or seal itself is excluded from the signed payload. The canonical representation is encoded with the existing codec rather than PHP serialization.

The canonical payload must be stable for semantically identical Scroll data and must not depend on object identity, memory layout, or runtime ordering.

## Signature

The existing `Signature` value object remains the persisted representation. Ed25519 is the asymmetric signing implementation for this lifecycle. Signature metadata contains the algorithm, key identifier, and encoded signature value.

The signer must fail closed when the required key is absent or invalid. Verification returns `false` for invalid signatures and does not silently regenerate or replace them.

## Sealing

Sealing uses the existing `Sealer`/codec abstraction. The seal is metadata attached to the Scroll envelope; the signed canonical payload is the authority for integrity verification. A sealed Scroll must verify successfully before it is accepted by a production lifecycle operation.

Unsealing requires the configured seal key and removes only the seal state. It must not mutate the signature or canonical Scroll data.

## Bulk operations

The lifecycle service accepts an explicit set of resolved Scrolls. CLI selection resolves that set through `ScrollCodex` rather than walking files directly.

Supported selection forms:

```text
scroll:sign <uri>
scroll:sign --all
scroll:seal <uri>
scroll:seal --all
scroll:unseal <uri>
scroll:unseal --all
scroll:verify <uri>
scroll:verify --all
```

Bulk selection is deduplicated by Scroll identity. References are resolved before sealing so a production build can validate the complete dependency graph.

Bulk sealing is two-phase:

1. resolve and validate every selected Scroll and its required references;
2. apply seals only after the complete set is known to be sealable.

If validation fails, the operation reports the failing Scroll and performs no seal mutation in the lifecycle result.

## Production invariant

The production build can eventually enforce:

```text
verify --all
```

as a hard gate. No unsigned, invalidly signed, or invalidly sealed Scroll may pass that gate.

## CLI responsibilities

The commands are thin adapters. They select Scrolls, invoke the lifecycle service, and render results. They do not implement cryptography, canonicalization, or sealing themselves.

`--all` is explicit; normal commands operate only on the requested selector.

## Future extension points

The lifecycle deliberately leaves room for:

- signed manifests;
- key stores and trust roots;
- package/build sealing;
- provenance metadata;
- sealed production artifacts;
- `scroll:make:*` and other creation commands;
- Katas and DAG validation;
- Message, Entity, Projection, UI, Manifest, and App Scroll types;
- persistent `Codejitsu\\Data\\Store` implementations.
