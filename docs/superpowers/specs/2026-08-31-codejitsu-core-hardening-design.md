# Codejitsu Core Hardening Design

**Date:** 2026-08-31  
**Status:** Approved scope, pending implementation plan

## Goal

Bring the existing Codejitsu core and workspace boundaries to a clean, reproducible release-candidate baseline before creating `packages/ui` or implementing the Package Scroll installer. This pass tightens implemented behavior and removes contradictions; it does not add new ecosystem product features.

## Completion Standard

The pass is complete when a clean disposable checkout can install dependencies, validate every active Composer manifest, autoload all active namespaces, run the complete test suite, exercise the real CLI, and prove the existing workspace packages and Codex boundaries behave as documented. `main` must be clean and synchronized with its Context Scrolls.

## Scope

### 1. Workspace Package Integrity

The existing `core`, `discovery`, `scrolls`, `codex`, `config`, `schema`, and `console` workspace packages remain monorepo boundaries. This pass will not physically relocate the root `src/` tree or claim the packages are independently published distributions.

Each package manifest will:

- pass `composer validate --strict` without warnings;
- omit hard-coded versions;
- declare the repository's proprietary license consistently;
- expose only namespaces currently owned by that boundary;
- use dependency names and directions consistent with ADR 0005;
- be exercised through disposable Composer path-repository fixtures.

The root aggregate remains the supported distributable package until source extraction is undertaken deliberately. Documentation must distinguish an installable monorepo/path boundary from a separately publishable package artifact.

Because the repository also ships and tests an executable CLI, its root `composer.lock` will be committed for reproducible development and CI. Library consumers continue to resolve dependencies from `composer.json`; the lock does not constrain downstream applications.

### 2. Real Metadata Indexing

The current Codex query API returns `IndexEntry` values but discovery still constructs every Scroll before queries run. This contradicts the documented metadata-only boundary.

Discovery will produce immutable resource descriptors containing the registered type, logical name/path, version, tags, source provenance, reference targets, attributes required for filtering, physical locator, and a lazy hydration callback or equivalent loader. `ScrollCodex` will store descriptors separately from hydrated Scroll instances.

Metadata queries by type, name, version, tag, attributes, source, exact path, path prefix, URI, and outgoing reference must inspect descriptors without hydrating candidates. Direct resolution or invocation hydrates only the selected resource and memoizes it. Explicitly registered Scroll objects remain supported and receive equivalent index entries immediately.

The existing source cascade remains unchanged:

- explicit selectors are authoritative and evaluated left-to-right;
- implicit selection evaluates sources in reverse registration order;
- logical identity excludes absolute filesystem paths and source names;
- package-owned types continue to resolve through the injected `TypeRegistry`.

Legacy discovery and filesystem-store paths will be retained only where they serve a tested public boundary. Overlapping internal logic will be consolidated behind the registry-driven descriptor/loader path. Removal requires proof that no active source or test consumes the path.

### 3. Runtime and CLI Reliability

The PHP substrate timeout must be deterministic under normal process scheduling. The configured execution timeout remains enforceable, but startup/scheduling jitter must not make trivial executions intermittently fail. Tests will cover successful execution comfortably inside the limit and forced termination beyond it without depending on sub-millisecond timing.

The real CLI entrypoint will receive integration coverage for:

- root and namespace help;
- Scroll discovery/listing;
- command resolution and execution through the Codex;
- read-only package listing/information behavior;
- nonzero exit and useful diagnostics for invalid input.

Existing mutating package commands remain Composer adapters in this pass. Their process execution will sit behind a small injectable boundary so install/remove/update behavior can be tested without network access or modifying the developer checkout. `pkg:remove` remains compatible; `pkg:uninstall`, search, Package Scrolls, and cache commands belong to the next approved package-installer slice.

### 4. Repository Hygiene

The verification surface will include:

- root and workspace `composer validate --strict`;
- `composer audit`;
- optimized strict PSR-4 generation;
- PHP syntax checking for tracked PHP source/tests/bin files;
- documentation-link validation;
- full PHPUnit suite;
- real CLI smoke tests;
- disposable clean-install verification;
- a clean Git worktree.

Tracked code will be scanned for stale namespaces, unreachable compatibility artifacts, obsolete pre-graph classes, and duplicated discovery/type logic. Files are removed only when references, autoloading, and behavior tests prove they are dead. Broad stylistic rewrites and speculative abstractions are excluded.

### 5. Documentation and Release Boundary

Root README, current state, roadmap, TODO, architecture Context Scrolls, and ADRs will be reconciled with verified implementation. Completed items will be marked complete; aspirational behavior will not be described as current.

The final state will explicitly record:

- which workspace packages are active monorepo boundaries;
- which package-extraction work remains;
- the true lazy-index/query behavior;
- the verified CLI and package-manager surface;
- Package Scroll/Composer plugin implementation as the next package-layer slice;
- `packages/ui` as subsequent work, not part of hardening.

## Error and Compatibility Policy

- Existing public URI schemes, built-in Scroll types, source precedence, command names, and root Composer package remain compatible.
- New validation errors identify the resource, source/package, and invalid field where available.
- Missing optional roots remain empty sources and never fall back to scanning the current working directory.
- Corrupt resources fail when their metadata must be indexed; lazy hydration does not hide invalid identity or routing metadata.
- Package mutation test doubles cannot weaken production process error propagation.
- No external package installation, publishing, tagging, or release is performed during hardening.

## Test Strategy

Every behavioral correction begins with a focused failing test. Integration fixtures use temporary directories and local path repositories; they do not require network access. Hydration-counting fixtures prove zero hydration for metadata queries and one-time hydration for repeated resolution. Timing tests use explicit margins rather than relying on the default one-second boundary.

The merged result is verified again on `main` before push. A failure at any gate stops integration until corrected.

## Explicitly Deferred

- Package Scroll implementation and the Composer plugin
- `pkg:search`, `pkg:uninstall`, and package-cache commands
- dedicated Shinobi package catalog
- `packages/ui`, Astro, Three.js, or lander code
- World, Scene, Edition, narrative, or achievement schemas
- physical extraction or publication of every workspace package
- Spark, Vessel, Sensei, Shinobi runtime, OpenSwoole, or distributed execution work
