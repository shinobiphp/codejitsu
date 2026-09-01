# Catalog and Context Tooling Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Finish catalog-backed package commands and add safe Context/catalog/package creation workflows.

**Architecture:** Extend the generic `CatalogIndex` with lookup/search behavior, keep Composer mutations behind `PackageManager`, and centralize writable Context operations in `ContextMemory`. Purpose-specific make handlers reuse registered Scroll metadata and existing console editor/questioner contracts.

**Tech Stack:** PHP 8.5, Composer 2, PHPUnit 13, NEON Scroll resources, Symfony Console adapters.

**Spec:** `docs/superpowers/specs/2026-08-31-catalog-context-tools-design.md`

## Global Constraints

- AI and UI packages are not created in this slice.
- `make:pkg` must not add root Composer requirements or install packages.
- Filesystem names must reject traversal and overwrites.
- Production demo resources are removed; useful test-local fixtures remain.

---

### Task 1: Catalog-backed package commands and runtime cache

**Files:** `src/Catalog/CatalogIndex.php`, `src/PackageManager.php`, `src/Commands/Packages.php`, `src/Packages/PackageBootstrap.php`, `scrolls/commands/pkg.cmd`, package tests, `var/.gitignore`.

- [ ] Add failing tests for catalog search, info, install resolution, and installed overrides.
- [ ] Run focused tests and confirm the missing behavior fails.
- [ ] Implement the minimal catalog query/lookup and PackageManager integration.
- [ ] Run focused tests to green.

### Task 2: Context create/edit and interactive TUI

**Files:** `src/Context/ContextMemory.php`, `src/Context/ContextTui.php`, `src/Commands/Contexts.php`, Context command/capability Scrolls, Context tests.

- [ ] Add failing tests for safe path resolution, creation, editing, and menu actions.
- [ ] Run focused tests and confirm failure.
- [ ] Implement create/edit using existing `Editor` and `Questioner` contracts.
- [ ] Run focused tests to green.

### Task 3: Purpose-specific make commands

**Files:** make command handlers/resources, creator tests, catalog fixtures.

- [ ] Add failing tests for `make:context`, `make:catalog`, and non-installed `make:pkg` output.
- [ ] Run focused tests and confirm failure.
- [ ] Implement minimal scaffolders and catalog entry persistence.
- [ ] Run focused tests and real CLI smoke tests to green.

### Task 4: Remove demos and reconcile documentation

**Files:** shipped hello resources, affected CLI tests, `README.md`, `.context/*.ctx`.

- [ ] Remove production demo resources and update tests to exercise real commands.
- [ ] Update package/catalog/context documentation and completed roadmap items.
- [ ] Run `git diff --check` and focused CLI tests.

### Task 5: Release verification and integration

**Files:** verification-derived Context counts if changed.

- [ ] Run `composer check`.
- [ ] Run `composer audit --no-interaction`.
- [ ] Run `composer test:installation`.
- [ ] Rebuild the package cache and smoke-test relevant commands.
- [ ] Commit, fast-forward merge to `main`, and remove merged worktrees/branches.
