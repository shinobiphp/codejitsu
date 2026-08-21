# Ideas

This file is the project's deliberate idea space.

Ideas are possibilities, not requirements, commitments, or current architecture. An idea may be promoted into the roadmap, turned into a decision record, implemented, deferred, or discarded.

Agents and maintainers should use ideas as input to exploration, not as authorization to implement them.

## Codejitsu / Scrolls

- Make `.context/` itself a first-class, discoverable resource through Scrolls/Codex rather than treating it as a permanent special case.
- Make context documents indexable and semantically searchable through the Codex.
- Support richer Scroll dependency/reference graphs so commands, schemas, capabilities, configs, workflows, and other resources can compose without hardcoded coupling.
- Add explicit dependency/reference metadata to Scroll envelopes so tooling can inspect relationships without hydrating every resource.
- Expand command namespaces with nested metadata, aliases, argument definitions, options, validation, and generated help.
- Add first-class cache inspection, invalidation, rebuild, and validation commands under a `scrolls:*` command namespace.
- Support signed, traceable, and eventually verifiable Scroll resources.
- Support multiple Scroll codecs and storage backends without changing Scroll semantics.
- Extend Scroll execution beyond PHP callables to future adapters such as Lua, WebAssembly, containers, workflows, remote APIs, and other substrates.
- Add schema-driven validation as a reusable execution boundary rather than a Command-only concern.

## Codex / Discovery

- Make `ScrollCodex` the authoritative index for all Scroll discovery, resolution, filtering, references, and cache lifecycle.
- Add persistent cache manifests with file fingerprints so unchanged Scrolls can load without reparsing.
- Support explicit cache commands and automatic invalidation when source resources change.
- Allow the Codex to expose lightweight metadata/index entries separately from hydrated Scroll instances.
- Support multiple stores such as filesystem, SQLite, NATS KV, and other backends while preserving one Codex contract.
- Build a dependency graph from references so impact analysis and invalidation can be targeted rather than global.

## ArchIQ

- Use Codejitsu Scrolls as the resource/protocol substrate for ArchIQ capabilities, analysis rules, schemas, reports, and execution plans.
- Represent ArchIQ analysis rules and language-specific strategies as discoverable resources instead of hardcoded registries.
- Make repository analysis produce durable, queryable architectural knowledge that can feed future reasoning and code-maintenance workflows.
- Let ArchIQ consume `.context/` as project intent and architectural memory when analyzing a repository.
- Eventually let ArchIQ write proposed context updates as reviewable changes rather than silently modifying architectural memory.

## Shinobi

- Position Codejitsu as the runtime/foundation beneath Shinobi.
- Use the same Scroll/Codex model for Shinobi capabilities, configuration, schemas, commands, workflows, services, and agent resources.
- Evolve the Codex toward a federated resource index suitable for local, remote, multi-tenant, and eventually distributed service nodes.
- Support zero-trust resource resolution, signed events, traceability, idempotency, and auditable execution.
- Use long-running runtimes such as OpenSwoole without changing the resource model.

## Self-Building / Self-Maintaining System

- Give Codejitsu enough introspection and context access to analyze its own source, tests, Scroll graph, architecture, and history.
- Let an agent inspect current architecture before proposing or implementing changes.
- Make architectural decisions and context updates part of the normal development lifecycle rather than external tribal knowledge.
- Eventually allow Codejitsu/Shinobi agents to propose, test, review, and maintain changes to their own ecosystem under explicit human-controlled boundaries.
- Treat the project's context, code, tests, Scrolls, Codex indexes, and decision history as connected sources of truth that future agents can navigate programmatically.

## Product / Ecosystem Directions

- Build a reusable enterprise-grade runtime/platform around Codejitsu rather than a framework that only solves HTTP application development.
- Expose resource discovery, execution, governance, and architectural intelligence as composable platform capabilities.
- Explore a marketplace/catalog model for reusable Scrolls and capability packs once the underlying contracts are stable.

## Promotion Rules

An idea should move out of this file when its status changes:

```text
idea
  ↓
validated direction → roadmap
  ↓
architectural decision → decision record
  ↓
implementation → source/tests + updated context
```

Do not use this file as a backlog replacement. Issues/tasks belong in the project's issue/task system; this file is for durable possibilities and design directions that are worth remembering.