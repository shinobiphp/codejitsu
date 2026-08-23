# 0003 — Source-Aware URI Resolution

## Status

Accepted

## Context

Codejitsu needs to resolve the same logical Scroll resources from multiple sources. Typical examples include global resources, tenant-specific resources, project resources, package resources, and eventually remote stores.

A resource's physical storage location must not become its domain identity. Resolution also needs to support both explicit source selection and controlled fallback.

## Decision

URIs use the following conceptual form:

```text
scheme://path[@source-chain][#version]
```

- the scheme identifies the resource type/addressing domain
- the path identifies the logical resource or resource beneath another resource
- `@source-chain` selects one or more named resource sources
- `#version` constrains the resource version

The `@` selector is **source context**, not part of logical resource identity.

A source is a named discovery/resolution origin registered with the Codex. A source may be backed by a filesystem, package, database, tenant store, remote service, or another backend. Source metadata may include tags such as `scrolls-dir`.

Source selectors may contain dot-separated source names. The selector is evaluated left-to-right:

```text
config://app@tenant.global#0.1.0
```

means tenant first, then global.

```text
config://app@global.tenant#0.1.0
```

means global first, then tenant.

When no source selector is present, the Codex uses its registered sources in reverse registration order. Therefore:

```text
register @global
register @tenant

config://app#0.1.0
```

checks:

```text
@tenant → @global
```

Later registrations are consequently more specific and override earlier registrations while preserving them as fallbacks.

An explicit source selector/chain is authoritative and does not silently expand into the implicit registered-source cascade.

Logical URI paths are resource references, not filesystem paths. For example:

```text
app://archiq
app://archiq/analysis/php
```

can identify an application resource and a resource beneath it regardless of where either is stored.

## Consequences

The Codex must retain source provenance in its index entries and resolution results.

Discovery must register resources against a named source rather than treating every discovered filesystem root as an anonymous collection.

The Codex can support layered global/tenant/project/application configuration without changing resource identity.

The same resource tree can exist in multiple sources and be resolved explicitly or through a deterministic cascade.

The URI parser must distinguish source selectors from logical paths and version fragments.
