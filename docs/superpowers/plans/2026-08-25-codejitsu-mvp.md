# Codejitsu MVP Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Finish Codejitsu as a small, package-oriented POC with a working resource-driven CLI, package-management Scrolls, clean Shinobi boundary, and a green behavioral test suite so a separate Shinobi repository can be started immediately afterward.

**Architecture:** Preserve the current first-class Scroll/resource model, with `ScrollCodex` remaining the single discovery/index/resolution boundary and the CLI remaining an application over Command Scrolls. Introduce Composer package boundaries in the existing repository without prematurely splitting every subsystem into separate repositories; keep Core minimal and move only independently meaningful boundaries first. Package-management commands consume the same Scroll/Codex execution path rather than becoming a second command framework.

**Tech Stack:** PHP 8.4+, Composer, PHPUnit 13, Symfony Console/DI/config/finder/filesystem, Nette NEON, `opis/json-schema`, PSR contracts. OpenSwoole is a Shinobi concern and must not remain a required Codejitsu runtime dependency.

**Spec:** `.context/architecture/overview.md`, `.context/architecture/scrolls.md`, `.context/architecture/codex.md`, `.context/architecture/runtime.md`, `.context/roadmap/current.md`, and `.context/glossary.md`.

## Global Constraints

- Code is authoritative for current behavior; `.context/` is authoritative for architectural intent and terminology.
- Scrolls are first-class, discoverable, addressable resources.
- `ScrollCodex` is the single resource discovery/index/resolution boundary.
- CLI flow remains `argv → CliIntent → ScrollCodex → Command Scroll → Schema → Capability → execution`.
- Command namespaces use colon-separated names such as `scrolls:cache:rebuild`.
- Discovery remains separate from hydration and execution.
- Query/index operations must not require hydration merely to inspect metadata.
- Source cascades use explicit left-to-right order and implicit reverse registration order.
- Keep long-running OpenSwoole/runtime orchestration concerns out of Codejitsu.
- Use `declare(strict_types=1);` and the project's established public-contract conventions.
- Do not implement Spark, Vessel, Sensei, Shinobi orchestration, distributed execution, or MCP as part of this MVP.

---

### Task 1: Establish the MVP package workspace

**Files:**
- Create: `packages/core/composer.json`
- Create: `packages/discovery/composer.json`
- Create: `packages/scrolls/composer.json`
- Create: `packages/codex/composer.json`
- Create: `packages/config/composer.json`
- Create: `packages/schema/composer.json`
- Create: `packages/console/composer.json`
- Modify: `composer.json`
- Modify: `lib/`/generated Composer metadata only through Composer commands, never hand-edit vendor output
- Test: package bootstrap/integration tests under `tests/Packages/`

**Interfaces:**
- Consumes: current `src/` namespace and Composer dependency graph.
- Produces: installable package boundaries under `packages/*`, with the root package acting as the development/compatibility aggregate until extraction is complete.

- [ ] **Step 1: Map the current source tree into package ownership before moving code.**
  - Core owns kernel/bootstrap primitives, contracts/value objects that have no higher-level dependency, and lifecycle/container abstractions.
  - Discovery owns discovery strategies, source registration, and discovery metadata.
  - Scrolls owns base/specialized Scroll contracts and resource hydration/serialization behavior.
  - Codex owns indexing, resolution, source selection, and graph-facing registry behavior.
  - Config and Schema own their specialized resource behavior and validation.
  - Console owns CLI translation/application behavior and Command Scroll presentation/execution integration.
  - Leave Crypto, DB, AI, OpenSwoole, and future agent concepts in the root MVP until their contracts are proven; do not create empty packages merely for symmetry.
- [ ] **Step 2: Add Composer path repositories and package metadata for the selected packages.**
- [ ] **Step 3: Move or mirror one package boundary at a time while preserving the public `Codejitsu\\` namespace where practical.**
- [ ] **Step 4: Add package-level autoload declarations and make the root package require the package set.**
- [ ] **Step 5: Run Composer dependency resolution and the complete test suite.**
  - Expected: all existing tests remain green and application bootstrap still works.
- [ ] **Step 6: Commit the package workspace boundary.**

---

### Task 2: Remove Shinobi-only dependencies from Codejitsu

**Files:**
- Modify: `composer.json`
- Modify: affected bootstrap/runtime files under `src/Apps/`, `src/Boot.php`, and related tests
- Test: runtime/bootstrap tests

**Interfaces:**
- Consumes: Task 1 package graph.
- Produces: Codejitsu bootstrappable without OpenSwoole or Shinobi-specific runtime machinery.

- [ ] **Step 1: Write a regression test proving the CLI/runtime bootstrap does not require OpenSwoole.**
- [ ] **Step 2: Remove `openswoole/core` from Codejitsu's required dependencies.**
- [ ] **Step 3: Move any genuinely OpenSwoole-specific code behind a Shinobi boundary or remove it from the MVP path.**
- [ ] **Step 4: Run the bootstrap and full PHPUnit suite.**
- [ ] **Step 5: Commit the dependency boundary.**

---

### Task 3: Finish Codex-owned discovery/index behavior needed by the MVP

**Files:**
- Modify: `src/Codex.php` or its package-owned equivalent
- Modify: `src/Discovery/*` or package-owned equivalent
- Create/modify: `src/Codex/IndexEntry.php` or package-owned equivalent if not already present
- Test: existing Codex/Discovery tests plus `tests/Codex/`

**Interfaces:**
- Consumes: Scroll resource metadata and source-aware URI resolution.
- Produces: lightweight index entries, source provenance, metadata queries, and explicit discovery/cache operations exposed through the Codex.

- [ ] **Step 1: Write tests for metadata-only queries by type, name, tags, source, and URI/path without hydration.**
- [ ] **Step 2: Write tests for source registration and reverse-order implicit resolution plus explicit dot-separated cascades.**
- [ ] **Step 3: Implement the minimal index/query path against the existing resource model.**
- [ ] **Step 4: Add explicit cache rebuild/clear behavior at the Codex boundary.**
- [ ] **Step 5: Add Command Scroll-facing metadata for cache operations without introducing a separate command system.**
- [ ] **Step 6: Run focused Codex/Discovery tests and then the full suite.**
- [ ] **Step 7: Commit the Codex MVP slice.**

---

### Task 4: Make the CLI a complete Scroll-driven vertical slice

**Files:**
- Modify: `src/Apps/Cli.php` or package-owned equivalent
- Modify: `src/IO/*` and command translation classes as needed
- Modify: `src/Commands/*`
- Modify/create: built-in command Scroll resources under the project's resource roots
- Test: `tests/Apps/*`, `tests/Scrolls/*`, and new CLI behavior tests

**Interfaces:**
- Consumes: `CliIntent`, `ScrollCodex`, Command Scrolls, Schema references, Capability references.
- Produces: discoverable and executable CLI commands whose help/listing can be generated from Scroll metadata.

- [ ] **Step 1: Write an integration test for discovering and listing Command Scrolls.**
- [ ] **Step 2: Write an integration test for resolving a command by its canonical colon-separated name.**
- [ ] **Step 3: Write an execution test proving `argv → intent → Codex → Command Scroll → capability → result`.**
- [ ] **Step 4: Ensure namespace help can inspect child definitions without hydrating every child.**
- [ ] **Step 5: Make the existing `bin` entrypoint exercise this path.**
- [ ] **Step 6: Run CLI-focused tests and the full suite.**
- [ ] **Step 7: Commit the CLI vertical slice.**

---

### Task 5: Add the Codejitsu package-management resource model

**Files:**
- Create: `packages/package/composer.json`
- Create: `packages/package/src/Package.php`
- Create: `packages/package/src/Manifest.php`
- Create: `packages/package/src/Manager.php`
- Create: `packages/package/src/Contracts/*` only where a public behavioral contract is required
- Create: `tests/Package/*`

**Interfaces:**
- Consumes: Composer package metadata and Codejitsu resource discovery.
- Produces: a minimal package-management abstraction capable of list/info/install/remove/update operations while delegating actual PHP dependency resolution to Composer.

- [ ] **Step 1: Define the smallest package metadata contract required by Codejitsu.**
- [ ] **Step 2: Write tests for package discovery/listing and package metadata inspection.**
- [ ] **Step 3: Implement the package manager as composition over Composer rather than a replacement dependency solver.**
- [ ] **Step 4: Write tests for install/remove/update command intents using a disposable fixture package or mocked Composer boundary.**
- [ ] **Step 5: Implement install/remove/update with explicit, traceable results and failure propagation.**
- [ ] **Step 6: Run focused package tests and the full suite.**
- [ ] **Step 7: Commit the package-management core.**

---

### Task 6: Expose package management as Command Scrolls

**Files:**
- Create: built-in `pkg` namespace Command Scroll resource
- Create: `pkg:list`, `pkg:info`, `pkg:install`, `pkg:remove`, and `pkg:update` command resources
- Modify: command discovery/index fixtures as needed
- Test: `tests/Package/Commands/*` and CLI integration tests

**Interfaces:**
- Consumes: Task 5 package manager and Task 4 Command Scroll execution path.
- Produces: canonical commands `pkg:list`, `pkg:info`, `pkg:install`, `pkg:remove`, and `pkg:update`.

- [ ] **Step 1: Write schema tests for package command inputs.**
- [ ] **Step 2: Define the parent `pkg` namespace and child command metadata in the serialized resource form.**
- [ ] **Step 3: Define each child command with Schema and Capability references.**
- [ ] **Step 4: Bind the capabilities to the package manager through the existing execution mechanism.**
- [ ] **Step 5: Verify `bin/codejitsu pkg:list` and `bin/codejitsu pkg:info <package>` through the real CLI path.**
- [ ] **Step 6: Verify install/remove/update against disposable fixtures and Composer test dependencies.**
- [ ] **Step 7: Run the complete suite and commit the package command slice.**

---

### Task 7: Establish the final Codejitsu MVP contract and documentation

**Files:**
- Modify: `.context/roadmap/current.md`
- Modify: `.context/glossary.md`
- Create: `.context/decisions/0005-codejitsu-mvp-package-boundaries.md`
- Modify: `.context/architecture/overview.md`
- Modify: `.context/architecture/runtime.md`
- Modify: `composer.json`
- Modify: package README files and root README if present
- Test: full test suite and CLI smoke test

**Interfaces:**
- Consumes: completed package, Codex, CLI, and package-management implementation.
- Produces: an explicit architectural boundary declaring Codejitsu MVP complete and Shinobi as the next repository/runtime layer.

- [ ] **Step 1: Update the architecture overview to describe the actual package boundaries and dependency direction.**
- [ ] **Step 2: Add the ADR recording why MVP uses a package workspace/monorepo and why not every subsystem is extracted yet.**
- [ ] **Step 3: Add `Package`/package-management terminology to the glossary only if it is now a stable Codejitsu concept.**
- [ ] **Step 4: Mark completed roadmap items as implemented and move Shinobi runtime, agentic, and long-running concerns to the next stage.**
- [ ] **Step 5: Verify Composer installation, PHPUnit, CLI discovery, CLI command execution, and package commands from a clean checkout.**
- [ ] **Step 6: Commit the final MVP documentation and release boundary.**

---

## MVP Exit Criteria

Codejitsu is ready to hand off to Shinobi when all of the following are true:

1. A clean Composer install succeeds without OpenSwoole.
2. The package workspace installs and autoloads the selected Codejitsu packages.
3. Scroll discovery, indexing, source-aware resolution, and references work through `ScrollCodex`.
4. Metadata queries do not require hydration of every candidate resource.
5. Command Scrolls drive the CLI end-to-end.
6. Namespace help/listing comes from discovered command metadata.
7. `pkg:list`, `pkg:info`, `pkg:install`, `pkg:remove`, and `pkg:update` execute through the same Command Scroll path.
8. The complete PHPUnit suite is green.
9. `.context/` accurately distinguishes implemented Codejitsu behavior from future Shinobi/Sensei/Vessel/Spark work.
10. A clean Codejitsu checkout can be used as the dependency foundation for a new `shinobiphp/shinobi` repository.

## Deliberate Post-MVP Work

Do not block Codejitsu MVP on:

- Spark/agent abstractions
- Vessel harness implementation
- Sensei UI/TUI or code-generation workflows
- model/provider integrations
- OpenSwoole long-running runtime
- async event/outbox/retry infrastructure
- distributed/service-node resolution
- MCP integration
- ArchIQ integration
- semantic/vector search
- cryptographically signed resource provenance
