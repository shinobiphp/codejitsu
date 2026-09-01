# Catalog Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add generic catalog CLI/TUI management, schema validation, package actions, meta-catalog support, and JSON cache storage.

**Architecture:** `CatalogIndex` remains read-only discovery, `CatalogManager` owns validated project writes, and `CatalogTui` orchestrates existing Questioner/Editor boundaries. Entry actions are provided by kind-specific providers rather than executed from catalog data.

**Tech Stack:** PHP 8.5, NEON Scrolls, JSON Schema, Composer, PHPUnit 13.

**Spec:** `docs/superpowers/specs/2026-09-01-catalog-management-design.md`

## Global Constraints

- Catalog data cannot contain shell commands or inline executable actions.
- Read-only metadata cannot elevate a non-writable source.
- Remote catalog locations are indexed but not fetched in this slice.
- Catalog and cache writes must be atomic.

---

### Task 1: Generic catalog inspection commands

**Files:** `src/Catalog/CatalogIndex.php`, `src/Commands/Catalogs.php`, `scrolls/commands/catalog.cmd`, catalog capabilities/schemas, tests.

- [ ] Write failing tests for catalog list/show/search and schemas.
- [ ] Run focused tests and verify expected failures.
- [ ] Implement read-only command behavior.
- [ ] Run focused tests to green.

### Task 2: Validated project catalog storage

**Files:** `src/Catalog/CatalogManager.php`, `src/Scrolls/Types/Catalog.php`, catalog entry schemas, tests.

- [ ] Write failing tests for access, atomic raw edit, add/remove, and entry-schema validation.
- [ ] Run focused tests and verify expected failures.
- [ ] Implement the minimal storage and validation boundary.
- [ ] Run focused tests to green.

### Task 3: Interactive catalog management and actions

**Files:** `src/Catalog/CatalogTui.php`, catalog action provider contracts/implementations, command handler, tests.

- [ ] Write failing tests for browse/add/remove/raw-edit and package action selection.
- [ ] Run focused tests and verify expected failures.
- [ ] Implement TUI and package provider behavior with confirmation.
- [ ] Run focused tests to green.

### Task 4: Meta-catalog, cache JSON, and scaffold correction

**Files:** `catalogs/sources.catalog`, package cache/bootstrap tests, `ProjectScaffolder`, generated AI/UI Composer manifests.

- [ ] Write failing tests for catalog-kind source entries, JSON cache payloads, and segmented namespaces.
- [ ] Run focused tests and verify expected failures.
- [ ] Implement JSON cache and correct scaffold namespaces.
- [ ] Run focused tests to green.

### Task 5: Documentation, verification, and integration

**Files:** `README.md`, `.context/*.ctx`, verification counts.

- [ ] Document catalog commands, access, actions, sources, and JSON cache.
- [ ] Run `composer check`, audit, and clean-install verification.
- [ ] Smoke-test real catalog and package commands.
- [ ] Commit, fast-forward merge to `main`, and clean the feature worktree.
