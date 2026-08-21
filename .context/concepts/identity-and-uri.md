# Identity and URI

Codejitsu separates resource identity from transport/storage representation.

## Identity

The repository already has an `Identifier` value object in `Codejitsu\Identity`. It is intentionally small and immutable: an identifier is a stable string value that can be compared and serialized. fileciteturn106file0L2-L2

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
- version fragment

It also distinguishes local/global/latest addressing. fileciteturn107file0L2-L2

Examples:

```text
config://app#0.1.0
schema://scroll#1.0.0
capability://hello
cmd://hello
```

The URI is therefore capable of becoming more than a filesystem alias: it is the future addressing layer for local, tenant-scoped, versioned, and potentially remote resources.

## Rule

Use identifiers for stable identity and indexing.

Use URIs for resolution/addressing.

Do not make filesystem paths, PHP class names, or serialized filenames the domain identity of a Scroll.
