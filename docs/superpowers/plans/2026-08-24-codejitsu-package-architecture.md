# Codejitsu Package Architecture Migration Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Turn `shinobiphp/codejitsu` into a Composer monorepo whose packages can later be split into independent repositories without another architectural rewrite, while preserving the canonical Scroll graph already established.

**Architecture:** `codejitsu/core` owns the resource substrate: Graph, Scroll, URI, Codex, discovery, resolution, identity, and contracts. Semantic/application packages own App, Config, Schema, Capability, Runtime, IO translators, and eventually Vessel, Spark, and Sensei. The repository remains `shinobiphp/codejitsu` during development; independent Git repositories are created only after package APIs stabilize.

**Tech Stack:** PHP 8.4+, Composer path repositories, PHPUnit, NEON, existing Codejitsu discovery/URI/Scroll graph implementation.

**Spec:** `.context/current-state.ctx`, `.context/decisions/0004-canonical-scroll-graph.md`, `.context/code-standards.md`.

## Global Constraints

- Keep `shinobiphp/codejitsu` as the development monorepo until the package APIs are stable.
- Scrolls remain data resources; source representation is not a PHP class contract.
- The canonical Scroll representation remains a semantic graph, not an AST or scalar-per-node graph.
- Codex remains the resource indexing/query/resolution boundary.
- URI identity remains independent of physical source layout.
- PHP behavior discovery may use attributes; Scroll resource discovery remains source/path/extension driven.
- PHP 8.4+ and `declare(strict_types=1);` remain mandatory.
- Prefer composition and real boundaries; avoid speculative abstractions.

---

### Task 1: Establish the monorepo package workspace

**Files:**
- Create: `packages/core/composer.json`
- Modify: `composer.json`
- Modify: `phpunit.xml.dist`
- Move: `src/` → `packages/core/src/`
- Test location: `tests/` remains workspace-level initially so the migration can be validated without changing every test path at once.

**Interfaces:**
- Root workspace discovers local packages through Composer path repositories.
- Core remains installable as `shinobiphp/codejitsu-core`.

- [ ] Move the current source tree intact into `packages/core/src` without changing namespaces.
- [ ] Add the Core package manifest with the current runtime dependencies as a temporary compatibility boundary.
- [ ] Change root PSR-4 autoloading to point at `packages/core/src`.
- [ ] Add Composer path repository configuration for `packages/*`.
- [ ] Keep the root package name `shinobiphp/codejitsu`.
- [ ] Keep root tests operational while the package split is underway.
- [ ] Run the full test suite before making semantic package extractions.

### Task 2: Extract IO and application/runtime packages

**Files:**
- Create: `packages/io/`
- Create: `packages/app/`
- Create: `packages/config/`
- Create: `packages/schema/`
- Create: `packages/capability/`
- Create: `packages/runtime/`

**Interfaces:**
- These packages depend on Core, never the reverse.
- IO packages translate external representations or execution intents into Core resources/commands.
- App/Config/Schema/Capability/Runtime packages own semantic Scroll types and behavior.

- [ ] Move IO intent/translator implementations out of Core.
- [ ] Move App application types out of Core.
- [ ] Move Config implementations out of Core.
- [ ] Move Schema implementations out of Core.
- [ ] Move Capability/Command semantic types out of Core.
- [ ] Move execution/runtime implementations out of Core.
- [ ] Leave only contracts/value primitives in Core where another package needs them.
- [ ] Update namespaces only when the new package boundary is semantically clearer; avoid namespace churn merely to make directories pretty.
- [ ] Add package-level tests and root integration coverage.

### Task 3: Make package metadata first-class Scroll resources

**Files:**
- Create package metadata Scrolls under each package's resource directory.
- Modify discovery/Codex integration.

**Interfaces:**
- Package metadata describes identity, dependencies, provided resources, capabilities, and schemas.
- Composer answers which PHP package is installed; Codex answers what that package provides semantically.

- [ ] Define package Scroll shape.
- [ ] Discover package Scrolls from package resource paths.
- [ ] Register them in Codex.
- [ ] Resolve package relationships lazily through Codex.
- [ ] Test package dependency/provides/requires relationships.

### Task 4: Stabilize the Core resource substrate

- [ ] Finalize Graph/Node/Edge behavior.
- [ ] Finalize Scroll over the canonical graph.
- [ ] Finalize URI and source cascade resolution.
- [ ] Finalize Codex query/index/resolution behavior.
- [ ] Ensure source precedence is deterministic and tested.
- [ ] Ensure physical filesystem layout never leaks into logical URI identity.

### Task 5: Vessel

**Package:** `packages/vessel`

- [ ] Define Vessel as the agent harness/container.
- [ ] Compose Spark, Context, Memory, System Instructions, Tools, Capabilities, Runtime, and configuration.
- [ ] Construct Vessels from Scroll graphs.
- [ ] Keep environment ownership in Vessel, not Spark.

### Task 6: Spark

**Package:** `packages/spark`

- [ ] Define the minimal executable agent contract.
- [ ] Separate model/reasoning from environment/context ownership.
- [ ] Support capability/tool invocation through Vessel.
- [ ] Make Spark configuration resource-driven.

### Task 7: Sensei

**Package:** `packages/sensei`

- [ ] Build Sensei as a specialized Spark + Vessel.
- [ ] Add conversational spec definition.
- [ ] Inspect project/resource graphs through Codex.
- [ ] Produce specification and execution-plan Scrolls.
- [ ] Generate code and tests.
- [ ] Execute and validate changes.
- [ ] Feed validated project changes back into the graph.

### Task 8: Repository split

- [ ] Freeze package APIs.
- [ ] Give each package its final Composer name.
- [ ] Create independent Git repositories from the monorepo package histories.
- [ ] Replace path repositories with VCS/Packagist dependencies.
- [ ] Turn `shinobiphp/codejitsu` into the distribution/workspace/meta-package repository.
- [ ] Verify every package installs independently.
- [ ] Verify the complete distribution installs from external repositories with no monorepo assumptions.
