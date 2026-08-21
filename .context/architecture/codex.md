# Codex Architecture

`ScrollCodex` is the in-process resource index and resolution boundary.

## Current Responsibilities

- register hydrated Scrolls
- index by type/name/version
- resolve Scroll URIs
- filter by type and tags
- invoke resolved Scrolls
- bind Scrolls back to the Codex so references resolve through the same registry

The current implementation extends the broader Envelope Codex and maintains a Scroll registry keyed by normalized type/name/version. fileciteturn103file0L2-L2

## Discovery

`ScrollDiscovery` recursively scans a resource root and maps file extensions to `ScrollTypes`. It decodes the payload and creates the corresponding Scroll type. fileciteturn104file0L2-L2

This is intentionally a separate concern from resolution. The next architectural step is to move discovery efficiency and persistence into the Codex boundary rather than teaching Boot or applications how resources are stored.

## Cache Direction

The intended cache model is:

```text
resource root
   ↓
manifest / fingerprint
   ↓
valid cache? ── yes ──> load index
      │
      no
      ↓
discover → index → persist cache
```

The cache should contain enough metadata to determine whether a resource set changed without requiring full hydration on every startup. Likely inputs include path, extension/type, size, modification time, and/or content hash.

Explicit invalidation/rebuild operations should be available through normal Command Scrolls once the mechanism exists:

```text
scrolls:cache
scrolls:cache:rebuild
scrolls:cache:clear
```

Cache persistence is not yet part of the current implementation and is deliberately documented as future work.

## Resolution Semantics

Typed URIs are preferred when a resource name could be ambiguous. Bare names may resolve only when exactly one matching Scroll exists. The Codex already treats ambiguity as an error rather than silently choosing a resource. fileciteturn103file0L2-L2

This is important for a federated/runtime environment where multiple versions, tenants, or resource types may coexist.
