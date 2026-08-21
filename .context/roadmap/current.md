# Current Roadmap

This roadmap records direction, not promises. Items marked planned are not necessarily implemented.

## Now

### 1. Codex-Owned Discovery and Cache

Move complete Scroll discovery responsibility behind `ScrollCodex` and add persistent cache/indexing.

Requirements:

- discover every supported Scroll type from configured roots
- cache the resource index
- validate cache freshness using a manifest/fingerprint
- avoid hydration when metadata is enough
- explicitly clear/rebuild cache
- expose cache operations as Command Scrolls

### 2. Resource Graph and References

Make references first-class enough that dependent resources can be inspected before execution.

Examples:

```text
cmd://hello
  ├── schema://hello
  └── capability://hello
```

### 3. Schema/Config/Capability Depth

Strengthen the three initial resource types with richer validation, versioning, composition, and production-grade storage semantics.

## Next

- formal Scroll identity value object integration
- stronger Envelope/Scroll serialization contract
- signed and traceable resource provenance
- richer discovery/index filters
- cache persistence drivers
- resource dependency graph
- command introspection and generated help
- context directory discovery/indexing

## Later

- long-running/OpenSwoole execution
- event/outbox/retry/compensation infrastructure
- distributed resource/node resolution
- capability execution substrates beyond PHP
- ArchIQ integration
- Shinobi orchestration and cognition layers
- self-analysis/self-maintenance workflows

## End State

Codejitsu becomes a runtime and resource substrate that can inspect, reason about, execute, validate, and help evolve the systems built on top of it — including itself.
