# Codejitsu Agent Instructions

## Mission

Codejitsu is a PHP 8.4+ runtime/framework substrate designed to become self-describing and eventually self-maintaining. Treat the repository as an evolving architecture, not a conventional framework codebase.

The current development strategy is a monorepo. Keep `shinobiphp/codejitsu` as the single Git repository while package boundaries and APIs stabilize. Only split package directories into independent Composer/Git repositories after the architecture is stable.

## Read First

Before changing code, read:

1. This file.
2. `.context/` and its relevant decisions/specifications.
3. `composer.json` and package manifests under `packages/`.
4. Relevant tests.
5. Recent Git history and the current branch/PR state when working on repository changes.

`.context/` is the deeper architectural memory. Do not duplicate its contents into this file. When current code and `.context/` disagree, stop and identify the discrepancy before making architectural changes.

## Architecture

Core owns the substrate and protocols. Semantic packages own concrete implementations and domain meaning.

The foundational model is:

- **Graph** — nodes and typed relationships with traversal/query semantics.
- **Scroll** — an immutable, addressable, bounded semantic graph plus identity, URI, version, and metadata.
- **Codex** — an indexed collection/universe of Scrolls and their relationships.
- **URI** — the stable addressing and resolution mechanism for resources.
- **Discovery** — finds resources/packages/Scrolls and feeds them into the Codex.
- **Resolver** — resolves a URI into contextualized resources.
- **Codec/Translator** — converts external representations into/out of canonical Codejitsu resources; concrete translators should not leak into Core unless they are genuinely protocol-level.
- **Package** — Composer/PHP code plus semantic resources represented by Scrolls.
- **Vessel** — the execution harness/container for a Spark, including context, memory, system instructions, tools, capabilities, runtime, and configuration.
- **Spark** — the agent abstraction that reasons/acts within a Vessel; it does not own its environment.
- **Sensei** — the specialized developer-facing Spark/Vessel for conversational specification, code generation, inspection, testing, and self-development.

### Core boundary

Core should remain small and dependency-light. It may own:

- Graph primitives
- Scroll protocol/model
- URI and resolution primitives
- Codex/index contracts and core implementation
- Discovery primitives
- identity/version/value objects
- protocol-level codec contracts
- other primitives that are genuinely required to understand or resolve the Codejitsu resource model

Core should not become the home for application semantics, concrete runtime adapters, AI providers, database implementations, HTTP/Swoole application types, or concrete domain-specific Scroll types merely because they currently exist there.

### Semantic packages

Expected package boundaries include, as justified by the existing code:

- `core`
- `app`
- `io` and/or focused codec packages
- `config`
- `schema`
- `capability`
- `runtime`
- `db`
- `ai`
- `vessel`
- `spark`
- `sensei`

Do not create empty packages simply to match this list. Extract a package when its boundary is real and useful.

IO translators and concrete Scroll types belong with the package/domain they implement, not automatically in Core.

## Scrolls Are Graphs

Do not regress Scrolls into plain configuration/document objects.

External formats such as NEON, YAML, JSON, PHP, etc. are representations. The canonical semantic representation is the Scroll graph.

Prefer:

```text
external representation
        ↓
translator/codec
        ↓
canonical Scroll graph
        ↓
Codex/index
        ↓
URI resolution / traversal / execution
```

When adding a new resource type, first ask whether it belongs as a graph/resource definition rather than introducing a parallel object model.

## Package Management

Composer is the code/package installation layer.

Codex is the semantic resource/package knowledge layer.

Eventually package metadata should be represented by Scrolls so Codejitsu can answer questions such as:

- what package is installed?
- what does it provide?
- what does it require?
- what capabilities/schemas/resources does it expose?
- what relationships exist between those resources?

Do not replace Composer with an ad-hoc package manager prematurely.

## PHP

- PHP 8.4+.
- Always use `declare(strict_types=1);`.
- Public classes are behavioral contracts.
- Prefer concrete public contracts and composition.
- Use internal interfaces only where they genuinely improve internal wiring.
- Avoid suffixes such as `Interface`, `Helper`, `Manager`, etc. unless there is a specific compatibility/aliasing reason.
- Prefer factories, strategies, and composition.
- Do not introduce static helpers outside bootstrap/infrastructure where justified.
- Messages/commands/queries/events should be immutable DTOs.
- Favor DDD + EDD and explicit boundaries.

## Design Rules

- Inspect before changing.
- Preserve established architecture unless there is a concrete reason to change it.
- Prefer the smallest coherent abstraction.
- Do not create abstractions for hypothetical future requirements.
- Do not duplicate concepts that already exist in Core or `.context/`.
- Keep dependencies flowing inward toward Core.
- Avoid circular package dependencies.
- Keep package APIs independently publishable even while the repository remains a monorepo.
- Do not mix unrelated refactors into feature work.

## Testing

Use behavior-oriented tests with Pest or PHPUnit as appropriate.

For orchestration changes, include integration coverage. For public contracts and graph/resource semantics, include focused unit tests.

Before claiming work is complete:

1. Run the relevant focused tests.
2. Run the broader test suite when practical.
3. Run repository smoke/verification commands when available.
4. Inspect the final diff for accidental changes.
5. Report what was actually verified; never claim tests passed without running them.

## Git / Change Discipline

Work on the current feature branch unless explicitly asked otherwise.

Keep commits small, coherent, and descriptive. Do not rewrite history or force-push unless explicitly requested.

The current monorepo transition is intentionally being developed in `shinobiphp/codejitsu`. Do not split package repositories yet.

When a task spans multiple architectural concerns, establish the plan before implementation and keep the implementation incremental.

## Current Direction

The intended progression is:

```text
existing Codejitsu
    ↓
monorepo package boundaries
    ↓
Core: Graph + Scroll + URI + Codex + Discovery
    ↓
semantic/IO package extraction
    ↓
package metadata + semantic resource graph
    ↓
runtime/capability execution
    ↓
Vessel
    ↓
Spark
    ↓
Sensei
    ↓
Codejitsu can inspect/specify/build/test/maintain itself
```

Do not jump ahead to Vessel/Spark/Sensei while the Core/Scroll/Codex/package substrate is unstable.
