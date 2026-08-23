# Identity and URI

Codejitsu separates resource identity from transport/storage representation.

## Identity

The repository already has an `Identifier` value object in `Codejitsu\Identity`. It is intentionally small and immutable: an identifier is a stable string value that can be compared and serialized.

For Scrolls, the current Codex identity key is derived from:

```text
<type>:<name>#<version>
```

That key is an index key, not necessarily the only public identifier representation.

## URI

`Codejitsu\Uri\Uri` is the addressing model. It parses:

- scheme/type
- tenant/user scope
- target node
- path/name
- query parameters
- source selector
- version fragment

The URI is capable of becoming more than a filesystem alias: it is the addressing layer for local, tenant-scoped, versioned, source-aware, and potentially remote resources.

### Resource paths

The URI path identifies a logical resource or a resource beneath another resource. Paths are domain/resource references, not filesystem paths.

Examples:

```text
app://archiq
app://archiq/analysis/php
context://architecture/overview
schema://scroll
```

### Sources

A URI may explicitly select a resource source with `@source`:

```text
config://app@global#0.1.0
config://app@tenant#0.1.0
app://archiq/analysis/php@tenant
```

The source selector identifies **where resolution should be attempted**, not part of the logical resource identity. The same logical resource may exist in multiple sources.

A source is registered with the Codex and can represent a filesystem root, package, database, remote store, tenant store, or another discovery/resolution backend. Source metadata may include tags such as `scrolls-dir`, but tags do not replace source identity.

Multiple source names may be chained with dots to explicitly define a fallback cascade:

```text
config://app@tenant.global#0.1.0
config://app@global.tenant#0.1.0
```

The first source is attempted first, followed by each subsequent source from left to right. An explicit source chain therefore defines its own resolution order.

When no source selector is present, the Codex uses its registered source cascade in reverse registration order. For example, if `@global` is registered first and `@tenant` second:

```text
config://app#0.1.0
```

resolves as:

```text
@tenant → @global
```

This makes later-registered sources the more specific overrides while preserving earlier sources as fallbacks.

An explicitly selected source chain must not silently expand into the global Codex cascade. Explicit addressing and implicit fallback are distinct resolution modes.

## Rule

Use identifiers for stable identity and indexing.

Use URIs for resolution/addressing.

Use logical URI paths for resource references.

Use `@source` for source selection and source fallback chains.

Do not make filesystem paths, PHP class names, or serialized filenames the domain identity of a Scroll.
