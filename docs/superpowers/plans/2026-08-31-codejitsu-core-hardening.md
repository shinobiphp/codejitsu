# Codejitsu Core Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Produce a reproducible, strictly validated Codejitsu core whose package workspace, lazy Codex index, runtime execution, CLI, tests, and documentation are ready for package-layer development.

**Architecture:** Keep the root aggregate and current monorepo package boundaries intact. Introduce an immutable discovered-resource descriptor and loader so Codex queries operate on metadata while hydration occurs only during resolution; harden existing Composer/CLI/process boundaries without adding Package Scroll or UI features.

**Tech Stack:** PHP 8.4+, Composer 2, PHPUnit 13, Symfony Console, Nette NEON, existing Scroll/Codex/Graph abstractions

**Spec:** `docs/superpowers/specs/2026-08-31-codejitsu-core-hardening-design.md`

## Global Constraints

- Preserve public URI schemes, built-in Scroll types, source precedence, command names, and the root `shinobiphp/codejitsu` package.
- Do not create `packages/ui`, Package Scroll implementation, Composer plugin, search/catalog behavior, or new narrative types.
- Metadata queries must perform zero Scroll hydration; resolution hydrates only the selected descriptor and memoizes the result.
- Package fixtures and mutation tests must be local and network-free.
- Remove active files only after reference, autoload, and behavior checks prove them dead.
- Every task ends with focused and full verification and a coherent commit.

---

### Task 1: Make Composer and Workspace Packages Reproducible

**Files:**
- Modify: `.gitignore`
- Add: `composer.lock`
- Modify: `packages/{core,discovery,scrolls,codex,config,schema,console}/composer.json`
- Create: `tests/Packages/WorkspaceManifestTest.php`

**Interfaces:**
- Consumes: existing Composer manifests and ADR 0005 package ownership.
- Produces: warning-free manifests and a committed root lockfile.

- [ ] Write `WorkspaceManifestTest` that loads every `packages/*/composer.json`, asserts no `version`, asserts `license === "proprietary"`, verifies package names and required internal dependencies, and checks each declared PSR-4 path exists from the manifest directory.
- [ ] Run `composer test -- --filter WorkspaceManifestTest` and verify failure on version/license metadata.
- [ ] Remove package `version` fields, add `license`, and correct only demonstrably invalid namespace/path declarations. Do not move source files.
- [ ] Remove `composer.lock` from `.gitignore`, run `composer update --lock`, and add the generated lockfile.
- [ ] Run all seven `composer validate --strict packages/*/composer.json`, root strict validation, optimized strict PSR-4 generation, focused tests, and the full suite.
- [ ] Commit with `chore: harden Composer workspace metadata`.

### Task 2: Introduce Lazy Discovered Resource Descriptors

**Files:**
- Create: `src/Scrolls/DiscoveredResource.php`
- Modify: `src/Scrolls/ScrollDiscovery.php`
- Modify: `src/Scrolls/IndexEntry.php`
- Modify: `src/Scrolls/ScrollCodex.php`
- Create: `tests/Scrolls/LazyIndexTest.php`
- Modify: existing Scroll discovery/Codex tests as required for the new return type.

**Interfaces:**
- Produces `DiscoveredResource` with canonical metadata and `hydrate(): ScrollContract`.
- `ScrollDiscovery::discover(string $root): list<DiscoveredResource>` parses source data once, validates routing metadata, and captures a loader without constructing a Scroll.
- `ScrollCodex` stores `array<string,array<string,IndexEntry>> $index`, descriptor loaders, and memoized Scrolls separately.

- [ ] Add a failing `LazyIndexTest` fixture Scroll whose constructor/hydration increments a counter. Load two fixture resources, query all supported criteria, and assert the count stays zero; resolve one URI twice and assert the count becomes exactly one.
- [ ] Add `DiscoveredResource` as an immutable value with `TypeDefinition $type`, normalized `name`, `version`, `tags`, attributes, references, locator, and a `Closure` loader. Validate names and semantic versions at construction.
- [ ] Change `ScrollDiscovery` to decode Context/NEON payloads into descriptors. The loader calls `TypeDefinition::make(null, $data)` only when invoked.
- [ ] Extend `IndexEntry` with an opaque locator/key needed to select its loader without exposing absolute paths in URI or serialized metadata.
- [ ] Refactor `ScrollCodex::load()` to register descriptors and index entries. Refactor `query()` to filter only entries. Refactor `resolve()` and bare-name resolution to select entries by source cascade, hydrate the selected loader once, bind it, and memoize it.
- [ ] Keep `registerScroll()` compatible by creating an index entry from the supplied object and storing the already-hydrated instance.
- [ ] Update `ofType`, `withTag`, and `withTags` to use query entries and resolve only their selected results, preserving the injected type registry and sources.
- [ ] Run `LazyIndexTest`, all Scroll/Codex tests, and the full suite.
- [ ] Commit with `refactor: separate Scroll indexing from hydration`.

### Task 3: Consolidate Discovery and Storage Compatibility

**Files:**
- Modify: `src/Discovery/ScrollDiscoverer.php`
- Modify: `src/Discovery/DiscoveredScroll.php`
- Modify: `src/Scrolls/Stores/Filesystem.php`
- Modify: `src/Contracts/Scrolls/Store.php`
- Create: `tests/Scrolls/DiscoveryCompatibilityTest.php`

**Interfaces:**
- Consumes: registry-driven `TypeDefinition` and lazy Codex descriptor behavior.
- Produces: one tested compatibility path for legacy directory-based envelope discovery without enum-only switches.

- [ ] Write compatibility tests covering every built-in definition plus a fixture package type through `ScrollDiscoverer`, `Filesystem::getDiscovered`, and `ScrollCodex::discover`.
- [ ] Verify the test fails at any remaining enum-only or codec inconsistency.
- [ ] Normalize shared type lookup, extension, codec, and type-name behavior through `TypeRegistry`; preserve public union signatures required by built-in enum callers.
- [ ] Search for all callers of legacy discovery/store classes. Remove only redundant private helpers with no callers; document retained compatibility APIs in code where their purpose is otherwise ambiguous.
- [ ] Run focused discovery/store tests and the full suite.
- [ ] Commit with `refactor: consolidate Scroll discovery compatibility`.

### Task 4: Make Process Execution Deterministic and Testable

**Files:**
- Modify: `src/Substrate/PhpRunner.php`
- Modify: `src/ExecutionPolicy.php`
- Modify: `tests/Execution/PhpSubstrateTest.php`
- Create: `src/Contracts/ProcessRunner.php`
- Create: `src/ProcessResult.php`
- Create: `src/ProcessRunner.php`
- Modify: `src/PackageManager.php`
- Modify: `src/Commands/Packages.php`
- Modify: `tests/Commands/PackagesTest.php`

**Interfaces:**
- Produces `Contracts\ProcessRunner::run(array $command, string $cwd): ProcessResult` and immutable `ProcessResult(int $exitCode, string $stdout, string $stderr)`.
- `PackageManager` accepts an optional runner and never directly invokes Composer in tests.

- [ ] Add substrate tests using an explicit 3000 ms success policy and a deterministic 50 ms infinite-loop timeout policy. Assert cleanup and timeout diagnostics.
- [ ] Refactor `PhpRunner` process polling so exit-code collection is reliable after `proc_get_status`, pipes/processes close on every path, and deadline termination cannot race normal completion.
- [ ] Add failing PackageManager tests with a fake ProcessRunner for info/install/remove/update success and failure propagation, exact command arrays, and working directory.
- [ ] Introduce the process contract/result/default runner and inject it into PackageManager. Keep production `proc_open` behavior and combined diagnostics.
- [ ] Route package Commands through an injectable/factory seam without changing current CLI names.
- [ ] Run execution/package command tests repeatedly, then the full suite twice to detect timing flakes.
- [ ] Commit with `refactor: harden process execution boundaries`.

### Task 5: Add Release-Grade CLI and Clean-Install Verification

**Files:**
- Create: `tests/Apps/CliSmokeTest.php`
- Create: `tests/Installation/CleanInstallTest.php`
- Modify: `composer.json`
- Modify: `README.md`

**Interfaces:**
- Produces Composer scripts `check`, `check:manifests`, and `check:php` that run the documented local verification surface.

- [ ] Add CLI smoke tests that execute `bin/codejitsu` in subprocesses for root help, `scrolls`, `scrolls:list`, `hello`, `pkg:list`, and invalid input, asserting exit codes and stable identifying output.
- [ ] Add a clean-install test that copies only tracked files to a temporary directory, runs `composer install --no-interaction --no-progress`, then optimized strict PSR-4 generation and a minimal CLI/test smoke using the committed lockfile. Mark it as an explicit installation group if runtime cost warrants separation from the default suite.
- [ ] Add cross-platform PHP lint and workspace-manifest validation scripts using PHP helper commands or Composer script arrays rather than shell-specific glob behavior.
- [ ] Document `composer check` and the optional clean-install verification command in README.
- [ ] Run the focused tests, `composer check`, `composer audit`, and clean-install verification.
- [ ] Commit with `test: add Codejitsu release verification`.

### Task 6: Reconcile Context and Close the Hardening Pass

**Files:**
- Modify: `.context/current-state.ctx`
- Modify: `.context/roadmap/current.ctx`
- Modify: `.context/todo.ctx`
- Modify: `.context/architecture/{overview,codex,scrolls}.ctx`
- Modify: `.context/decisions/0005-codejitsu-mvp-package-boundaries.ctx`
- Modify: `README.md`
- Create: `.context/decisions/0008-lazy-scroll-index.ctx`

**Interfaces:**
- Produces: accurate resumption context and an explicit boundary before Package Scroll and UI work.

- [ ] Audit every unchecked MVP/TODO claim against tests and source; mark only verified items complete and remove stale duplicate wording.
- [ ] Document lazy descriptor/index/hydration behavior and why workspace packages remain monorepo boundaries.
- [ ] Record exact verification commands and current passing counts from fresh output.
- [ ] State the next sequence: Package Scroll/package installer, Composer plugin/cache lifecycle, then `packages/ui` as the first ecosystem consumer.
- [ ] Run documentation-link tests, root/workspace strict validation, optimized PSR-4, PHP lint, Composer audit, full PHPUnit, CLI smoke, clean-install verification, and `git diff --check`.
- [ ] Commit with `docs: close Codejitsu core hardening`.

## Integration

After all tasks pass, use `superpowers:finishing-a-development-branch`. Merge `codex/core-hardening` into `main` only after explicit integration confirmation or an already-stated user choice, rerun the entire verification surface on merged `main`, push, and remove the clean worktree/branch.
