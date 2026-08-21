# 0003 — Colon-Separated Command Namespaces

## Status

Accepted

## Context

Command Scrolls need composition and namespaces without turning CLI argument parsing into a recursive positional protocol.

## Decision

Command namespaces use colon-separated canonical names:

```text
scrolls
scrolls:cache
scrolls:cache:rebuild
```

The parent namespace Scroll stores child command definitions in its serialized data. Runtime child hydration is deferred until invocation.

## Consequences

Command names are globally addressable and composable. Help can list nested commands from metadata without hydrating every child. The same namespace convention can extend naturally to framework-maintenance commands and future resource operations.
