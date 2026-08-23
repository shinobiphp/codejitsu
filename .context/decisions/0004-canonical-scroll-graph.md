# Decision 0004: Canonical Scroll Graph

## Status

Accepted.

## Decision

A Scroll is represented canonically as a semantic graph rather than as a codec-specific array or syntax tree.

The canonical representation contains structured scalar metadata plus meaningful nodes and relationships. It must not model every scalar as a graph node.

Codecs and physical sources are responsible for translating their representations into the canonical graph. The Codex indexes the graph metadata and resolves graph relationships. A hydrated Scroll provides behavior over the canonical representation.

The conceptual pipeline is:

```text
Source → Parser/Codec → Canonical Scroll Graph → Index → Codex → Scroll
```

## Logical Resources

URI paths are logical resource paths, not filesystem paths. Different physical representations may expose the same logical graph:

```text
scrolls/context/architecture/scrolls.ctx
```

and:

```text
scrolls/context/architecture.ctx/scrolls
```

may both represent:

```text
context://architecture/scrolls
```

The source determines how physical resources map to logical graph nodes and edges.

## References

Named references such as:

```text
$php: context://architecture/php
```

are represented as named graph edges. The `$` is source-format syntax and is not part of the canonical identifier.

References should resolve lazily through the Codex rather than eagerly hydrating every target.

## Graph Shape

A Scroll graph may contain:

- identity
- metadata
- content
- attributes
- child/resource nodes
- named or typed relationships
- provenance/source information

Scalar metadata remains structured data. Graph nodes and edges are reserved for meaningful resources and relationships.

## Consequences

- Scrolls become codec-independent semantic resources.
- NEON, YAML, JSON, `.ctx`, database, Git, remote, and generated sources can share one logical representation.
- Codex queries can eventually operate on relationships as well as metadata.
- Nested resources do not require a filesystem-specific model.
- App Scrolls can expose resource trees naturally.
- Source overlays and fallback operate on logical resources rather than physical files.

## Non-goals

This decision does not require a general-purpose graph database or a compiler-style AST. The graph is an in-memory/domain representation optimized for resource identity, metadata, content, relationships, indexing, and resolution.
