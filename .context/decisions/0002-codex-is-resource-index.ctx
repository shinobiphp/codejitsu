# 0002 — Codex Is the Resource Index

## Status

Accepted

## Context

Resource lookup should not be duplicated across Boot, applications, Scroll implementations, or individual drivers. References need a single resolution boundary.

## Decision

`ScrollCodex` owns the in-process Scroll registry and resolution boundary. Applications ask the Codex for resources instead of discovering files directly.

Discovery, indexing, resolution, and later persistent caching belong at or behind the Codex boundary.

## Consequences

The runtime has one place to resolve resources by name, URI, type, tags, and version. Future cache, federation, persistence, and remote resolution can evolve without changing every consumer.
