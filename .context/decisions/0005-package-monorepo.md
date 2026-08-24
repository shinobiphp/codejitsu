# Decision 0005: Package Monorepo Before Repository Split

## Status

Accepted.

## Decision

Codejitsu will remain in the `shinobiphp/codejitsu` repository while its package boundaries and public contracts are being stabilized.

The repository is now a Composer monorepo with independently describable packages under `packages/`. Local packages are connected through Composer path repositories during development.

Independent Git repositories will be created only after package APIs, dependency direction, discovery, Scroll metadata, and installation behavior are stable.

## Dependency Direction

```text
semantic packages
      ↓
    core
```

Core must not depend on App, Config, Schema, Capability, Runtime, Vessel, Spark, Sensei, or other semantic/application packages.

Composer manages PHP package installation and dependency resolution. Codex manages Codejitsu's semantic resource/package graph.

## Initial Package Boundaries

The current migration establishes:

```text
packages/core
packages/app
packages/io
packages/config
packages/schema
packages/runtime
```

The first extraction intentionally preserves existing namespaces to minimize migration churn. Namespace cleanup happens only when the semantic package API is being stabilized.

Future packages include:

```text
packages/capability
packages/codec
packages/package
packages/vessel
packages/spark
packages/sensei
```

## Core Responsibilities

Core owns the substrate required for every Codejitsu package:

- canonical Graph / Node / Edge
- Scroll resource model
- URI and logical resource identity
- Codex indexing/query/resolution
- discovery primitives
- resolution primitives
- shared identity/version/value primitives
- contracts that are genuinely cross-package substrate

Concrete application semantics and infrastructure adapters belong in packages above Core.

## Consequences

- Development remains atomic and fast in one repository.
- Package boundaries can be refactored without multi-repository coordination.
- Composer path repositories provide the same dependency shape that will later be used with independent repositories.
- Final repository splitting becomes a packaging operation rather than an architectural rewrite.
