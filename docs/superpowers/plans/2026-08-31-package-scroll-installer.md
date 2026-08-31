# Package Scroll Installer Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Discover installed `codejitsu-pkg` Composer packages, validate their declarative Package Scrolls, compile a deterministic cache, and apply package-owned Scroll types and sources during Codejitsu boot.

**Architecture:** `packages/package` owns the Package Scroll, Composer-metadata discovery, normalized declarations, atomic cache compiler/reader, and runtime registry. `packages/composer-plugin` contains only the Composer lifecycle adapter and delegates compilation to that package. Codejitsu boot loads built-ins, applies cached package declarations, then loads project sources; the CLI exposes inspection, search, mutation aliases, and cache lifecycle commands through existing Command Scrolls.

**Tech Stack:** PHP 8.4+, Composer 2 plugin API, Nette NEON, Codejitsu Scroll/Codex APIs, PHPUnit 13.

**Spec:** `docs/superpowers/specs/2026-08-30-package-scroll-installer-design.md`

## Global Constraints

- Package installation is declarative; Package Scrolls never execute arbitrary PHP during Composer events.
- Only Composer packages with `type: codejitsu-pkg` and `extra.codejitsu.manifest` participate.
- Generated cache data contains plain arrays, has a format version and fingerprint, is deterministic, and is atomically replaceable.
- Paths must be relative, must not traverse upward, and must resolve inside the Composer-provided package root, including through symlinks.
- Invalid declarations fail closed with package and field provenance.
- Type classes are validated syntactically while compiling and for existence/Scroll compatibility only while applying at runtime.
- `packages/ui`, World/Scene/Edition contracts, Astro, and lander behavior remain out of scope.

---

### Task 1: Built-in Package Scroll Contract

**Files:**
- Create: `src/Scrolls/Types/Package.php`
- Modify: `src/Enums/Scrolls/Types.php`
- Create: `packages/package/composer.json`
- Modify: `composer.json`
- Modify: `tests/Packages/WorkspaceManifestTest.php`
- Create: `tests/Scrolls/Types/PackageTest.php`

**Interfaces:**
- Produces: `Package::typeDeclarations(): array<string,array<string,mixed>>`, `Package::sourceDeclarations(): array<string,array{path:string}>`, and normalized package metadata from `toArray()`.
- Produces: built-in `Types::PACKAGE` with extension `package` and scheme `package://`.

- [ ] **Step 1: Write failing behavioral tests** proving a valid manifest normalizes metadata/types/sources and malformed names, versions, type fields, codecs, capabilities, dependencies, configuration URIs, and source paths report the offending field.
- [ ] **Step 2: Run `bin/phpunit tests/Scrolls/Types/PackageTest.php tests/Packages/WorkspaceManifestTest.php`** and confirm failures are caused by the missing Package type/workspace package.
- [ ] **Step 3: Implement the minimal Package Scroll and enum definition**, keeping cross-package conflicts and filesystem containment out of this class.
- [ ] **Step 4: Add the `codejitsu/package` workspace manifest and root path-repository requirement**, then refresh `composer.lock` without updating unrelated dependencies.
- [ ] **Step 5: Run the focused tests and `composer validate --strict`** until green.
- [ ] **Step 6: Commit as `feat: define Package Scroll manifests`**.

### Task 2: Installed Package Discovery

**Files:**
- Create: `src/Packages/InstalledPackage.php`
- Create: `src/Packages/InstalledPackageDiscovery.php`
- Create: `src/Contracts/Packages/InstalledPackages.php`
- Create: `tests/Packages/InstalledPackageDiscoveryTest.php`

**Interfaces:**
- Produces: immutable `InstalledPackage(string $name, string $version, string $root, string $manifest)`.
- Produces: `InstalledPackages::all(string $projectRoot): array` and Composer-installed-metadata implementation.
- Consumes: Composer 2 `installed.php`/`InstalledVersions` metadata without scanning arbitrary directories.

- [ ] **Step 1: Write failing fixture tests** for filtering by Composer type and explicit manifest, root-package participation, lexicographic ordering, missing manifest metadata, missing files, absolute paths, traversal, and symlink escape.
- [ ] **Step 2: Run the focused test and confirm the discovery API is missing.**
- [ ] **Step 3: Implement immutable discovery records and filesystem-safe manifest resolution** with package-provenance exceptions.
- [ ] **Step 4: Run focused and existing package tests.**
- [ ] **Step 5: Commit as `feat: discover installed Codejitsu packages`**.

### Task 3: Deterministic Package Cache Compiler

**Files:**
- Create: `src/Packages/PackageDeclaration.php`
- Create: `src/Packages/PackageCompiler.php`
- Create: `src/Packages/PackageCache.php`
- Create: `src/Packages/PackageException.php`
- Create: `tests/Packages/PackageCompilerTest.php`
- Create: `tests/Packages/PackageCacheTest.php`

**Interfaces:**
- Produces: `PackageCompiler::compile(array $installedPackages): array{format:int,fingerprint:string,packages:list<array<string,mixed>>}`.
- Produces: `PackageCache::read(string $path): ?array`, `write(string $path, array $compiled): void`, `clear(string $path): void`, and `status(string $path): array`.
- Consumes: Package Scroll decoding through the built-in NEON codec and normalized declarations from Task 1.

- [ ] **Step 1: Write failing compiler tests** for deterministic order/fingerprint, manifest-name matching, plain normalized data, known codecs, valid class strings, and duplicate type/extension/scheme/source diagnostics naming both packages.
- [ ] **Step 2: Run the focused compiler test and verify expected failures.**
- [ ] **Step 3: Implement minimal parsing, normalization, aggregate conflict checks, and SHA-256 fingerprinting.**
- [ ] **Step 4: Write failing cache tests** proving successful atomic replacement, empty valid caches, malformed-cache rejection, and preservation of the prior cache when compilation/write fails.
- [ ] **Step 5: Implement sibling-temp-file plus rename writes and strict cache reads.**
- [ ] **Step 6: Run both focused suites and existing Scroll registry tests.**
- [ ] **Step 7: Commit as `feat: compile deterministic package cache`**.

### Task 4: Runtime Package Registry and Boot

**Files:**
- Create: `src/Packages/PackageRegistry.php`
- Create: `src/Packages/PackageBootstrap.php`
- Modify: `src/Boot.php`
- Modify: `src/Scrolls/TypeDefinition.php`
- Create: `tests/Packages/PackageRegistryTest.php`
- Create: `tests/Packages/PackageBootstrapTest.php`

**Interfaces:**
- Produces: `PackageRegistry::apply(array $compiled, ScrollCodex $codex): void`.
- Produces: `PackageBootstrap::boot(string $projectRoot, ScrollCodex $codex): array` that reads cache or compiles in memory/writes when possible.
- Consumes: `ScrollCodex::types()`, `registerSource()`, and `load()`; package type definitions are all registered before package sources are loaded.

- [ ] **Step 1: Write a failing real integration test** with a fixture package class and `.world` source proving the compiled declaration resolves through `ScrollCodex` with package provenance.
- [ ] **Step 2: Run it and confirm runtime application is missing.**
- [ ] **Step 3: Adjust `TypeDefinition` only as needed** so compile-time can validate class-string syntax while runtime application enforces class existence and the Scroll contract.
- [ ] **Step 4: Implement ordered type registration then source loading.**
- [ ] **Step 5: Write failing bootstrap tests** for existing cache, missing-cache compilation, unwritable-cache in-memory fallback, malformed cache failure, and absent packages as a valid no-op.
- [ ] **Step 6: Implement bootstrap and insert it before project default/context loading in `Boot`.**
- [ ] **Step 7: Run package integration, boot, CLI, and full default tests.**
- [ ] **Step 8: Commit as `feat: boot package-owned Scroll resources`**.

### Task 5: Composer Plugin Adapter

**Files:**
- Create: `packages/composer-plugin/composer.json`
- Create: `src/Composer/Plugin.php`
- Create: `src/Composer/PackageInstaller.php`
- Modify: `composer.json`
- Modify: `tests/Packages/WorkspaceManifestTest.php`
- Create: `tests/Composer/PluginTest.php`

**Interfaces:**
- Produces: Composer `PluginInterface`/`EventSubscriberInterface` implementation subscribing to post-install and post-update lifecycle events.
- Produces: `PackageInstaller::rebuild(string $projectRoot): array`, the sole adapter call into discovery/compiler/cache.
- Consumes: Composer's local repository/installation manager through a narrow adapter; never invokes Composer recursively.

- [ ] **Step 1: Add Composer dev/runtime API dependencies and write failing adapter tests** proving subscribed events delegate one rebuild after successful dependency lifecycle events and plugin activation stores Composer/IO context without compiling.
- [ ] **Step 2: Run the focused tests and verify missing plugin behavior.**
- [ ] **Step 3: Implement the thin plugin and installer adapter.**
- [ ] **Step 4: Add the Composer-plugin workspace manifest, root requirement, `allow-plugins` entry, and lock changes.**
- [ ] **Step 5: Run focused tests, strict manifest validation, and optimized autoload validation.**
- [ ] **Step 6: Commit as `feat: rebuild package cache from Composer`**.

### Task 6: Package CLI and Repository Search

**Files:**
- Create: `src/Contracts/Packages/PackageRepository.php`
- Create: `src/Packages/ComposerRepository.php`
- Modify: `src/PackageManager.php`
- Modify: `src/Commands/Packages.php`
- Modify: `scrolls/commands/pkg.cmd`
- Create: `scrolls/capabilities/pkg-search.capability`
- Create: `scrolls/capabilities/pkg-uninstall.capability`
- Create: `scrolls/capabilities/pkg-cache-status.capability`
- Create: `scrolls/capabilities/pkg-cache-rebuild.capability`
- Create: `scrolls/capabilities/pkg-cache-clear.capability`
- Modify: `tests/PackageManagerTest.php`
- Modify: `tests/Commands/PackagesTest.php`
- Modify: `tests/Apps/CliSmokeTest.php`

**Interfaces:**
- Produces: `pkg:search`, `pkg:uninstall`, `pkg:cache:status`, `pkg:cache:rebuild`, and `pkg:cache:clear`; preserves `pkg:remove` as an alias.
- Produces: `PackageRepository::search(string $query, string $root): array` returning normalized Composer metadata filtered to `codejitsu-pkg`.
- Consumes: existing injected process boundary for Composer commands and Task 3/4 cache services.

- [ ] **Step 1: Write failing PackageManager/repository tests** for Composer repository search filtering, uninstall aliasing, structured Composer failures, and no code download/execute during search.
- [ ] **Step 2: Run focused tests and confirm failures.**
- [ ] **Step 3: Implement the minimal repository and manager methods.**
- [ ] **Step 4: Write failing command and real CLI smoke tests** for every new command plus the compatibility alias.
- [ ] **Step 5: Add capabilities/command metadata and handlers through the existing Scroll execution path.**
- [ ] **Step 6: Run focused command, CLI, and package suites.**
- [ ] **Step 7: Commit as `feat: expose Package Scroll lifecycle commands`**.

### Task 7: Release Gates and Context Closure

**Files:**
- Modify: `composer.json`
- Modify: `README.md`
- Modify: `.context/current-state.ctx`
- Modify: `.context/roadmap/current.ctx`
- Modify: `.context/todo.ctx`
- Modify: `.context/architecture/codex.ctx`
- Create: `.context/decisions/0009-package-scroll-installation.ctx`
- Modify: `tests/Installation/CleanInstallTest.php`

**Interfaces:**
- Produces: a release gate proving a tracked checkout installs with the allowed plugin, compiles an empty/fixture cache, boots the CLI, and preserves strict PSR-4.

- [ ] **Step 1: Extend the clean-install test first** to require plugin-safe installation, cache generation, and CLI bootstrap; run it and confirm failure before documentation/config changes.
- [ ] **Step 2: Make the minimal Composer release-script/config changes and get the isolated installation test green.**
- [ ] **Step 3: Update public and `.context` documentation** with implemented behavior, cache location/lifecycle, failure/security boundaries, exact verified counts, and `packages/ui` as the next slice.
- [ ] **Step 4: Run `composer check`, `composer audit`, `composer test:installation`, and `git diff --check`.**
- [ ] **Step 5: Commit as `docs: close Package Scroll installer slice`**.
- [ ] **Step 6: Use `superpowers:verification-before-completion` and `superpowers:finishing-a-development-branch` before integrating.**
