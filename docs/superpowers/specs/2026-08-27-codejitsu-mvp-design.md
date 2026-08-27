# Codejitsu MVP Package Architecture

## Goal

Finish the existing `refactor/monorepo-core` work into a coherent Codejitsu MVP/POC while preserving the package boundaries already established by the branch and avoiding speculative extraction.

## Scope

The implementation starts from the existing package workspace:

- `packages/core`
- `packages/app`
- `packages/config`
- `packages/io`
- `packages/runtime`
- `packages/schema`

These packages may remain thin. A new package is added only when current source ownership requires it. Separate Git repositories remain a later extraction step; this phase is a Composer path-repository monorepo.

## Architecture

Codejitsu is the reusable resource/discovery/resolution substrate. The package workspace expresses architectural ownership without requiring every package to be feature-rich immediately.

`core` owns foundational contracts, value objects, kernel/container primitives, and resource primitives that have no higher-level dependency. `app`, `config`, `io`, `runtime`, and `schema` own the concerns already assigned to them by the existing extraction.

Long-running OpenSwoole runtime concerns belong to Shinobi, not Codejitsu. Codejitsu's runtime package may retain only the execution abstractions needed by the current resource/capability model.

The primary MVP path is:

```text
package
  -> resource/Scroll
  -> discovery
  -> Codex
  -> Command Scroll
  -> capability
  -> execution/result
```

The CLI is a consumer of that path rather than a parallel hard-coded command architecture.

## Resource Model

The existing Scroll, URI, Codex, graph/reference, Config Scroll, Schema Scroll, Capability Scroll, Command Scroll, and Context Scroll concepts remain authoritative. Resource identity is URI-based, metadata is indexable without hydration, provenance is preserved, and source cascades remain explicit/ordered according to the existing architecture.

Command Scrolls use the existing colon-separated command naming convention. Package-management commands therefore fit the same mechanism:

```text
pkg:list
pkg:info
pkg:install
pkg:remove
pkg:update
```

They are Codejitsu capabilities exposed through Command Scrolls, not a second command framework.

## Package Management

Package management is intentionally MVP-sized. It provides Codejitsu-aware package discovery and lifecycle operations while delegating PHP dependency resolution/install mechanics to Composer where required. It does not replace Composer.

The first vertical slice must prove that a package-management operation can be discovered and invoked through the same Codex/Scroll/Capability path used by other commands.

## Dependency Rules

- `core` must remain the lowest-level Codejitsu package.
- Higher-level packages may depend on `core`.
- Lower-level packages must not depend on Shinobi.
- OpenSwoole is a Shinobi runtime concern and must not remain an accidental Codejitsu-core dependency.
- Package dependencies must be declared by the package that owns the code.
- The root package is a development/workspace aggregate, not a substitute for package boundaries.

## CLI

The CLI resolves command intent through Codejitsu resources. It must support enough command discovery, introspection, and invocation to demonstrate the complete vertical slice, including the initial package commands.

## Testing

Behavioral tests remain authoritative. Every package boundary change must be covered by focused package tests and the full suite. The MVP gate is a clean Composer install/update path, a green complete test suite, and successful execution of the end-to-end command/resource flow.

## Out of Scope

The Codejitsu MVP does not implement:

- Spark/agent abstractions
- Vessel agent harnesses
- Sensei
- Shinobi's OpenSwoole server/runtime
- distributed Codex
- NATS orchestration
- MCP integration
- ArchIQ integration
- autonomous/self-maintaining workflows

Those are consumers/later layers. Sensei is a specialized Spark that will eventually operate with Codejitsu knowledge and Buildshido principles; it is not a Codejitsu foundation dependency.

## Completion Criteria

Codejitsu MVP is complete when:

1. The existing package workspace is internally consistent and Composer-installable.
2. Package dependencies accurately reflect ownership.
3. Codex owns discovery/index/query/resolve responsibilities required by the current roadmap.
4. Command Scrolls can drive the CLI through discovery and capability execution.
5. Initial package-management commands operate through that same mechanism.
6. Existing Scroll/resource behavior remains green under the full test suite.
7. `.context` accurately records the implemented package architecture and the boundary to Shinobi.
8. The resulting branch is suitable to become the stable Codejitsu foundation for a new `shinobiphp/shinobi` repository.
