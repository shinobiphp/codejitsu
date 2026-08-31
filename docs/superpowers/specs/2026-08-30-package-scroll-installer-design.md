# Package Scroll Installer and Composer Plugin Design

**Date:** 2026-08-30  
**Status:** Approved design, pending implementation plan

## Purpose

Codejitsu ecosystem packages need a native, deterministic way to contribute Scroll types and resource sources. The package contract should itself be a Scroll rather than a PHP installer convention, and Composer should keep the compiled package state synchronized after dependency changes.

This slice introduces a bootstrap `package` Scroll, a generic declarative installer, and a Composer plugin for packages that explicitly participate in the Codejitsu package registry. Normal Codejitsu packages use Composer type `codejitsu-pkg`; infrastructure that must retain another Composer type, such as `composer-plugin`, participates through the same explicit manifest metadata.

## Constraints

- Package installation is declarative. Package Scrolls do not execute arbitrary PHP during Composer events.
- Composer discovers package manifests and compiles state; normal Codejitsu bootstrap applies that state.
- Package-defined Scroll types remain package-owned and never require new core enum cases.
- The compiled result is deterministic, replaceable, and safe to delete and rebuild.
- Invalid package declarations fail closed with the package name and offending field.
- The implementation must work for normal vendor installs and this repository's path-based package workspace.
- No UI package, World/Scene/Edition schema, Astro integration, or lander code is part of this slice.

## Package Scroll

`package` is a built-in bootstrap Scroll type because Codejitsu must understand package declarations before ecosystem types are registered. Its canonical definition is:

```text
name: package
plural: packages
extension: package
scheme: package://
codec: neon
```

A normal Codejitsu package uses type `codejitsu-pkg` and declares its manifest location:

```json
{
  "name": "shinobiphp/codejitsu-ui",
  "type": "codejitsu-pkg",
  "extra": {
    "codejitsu": {
      "manifest": "codejitsu.package"
    }
  }
}
```

The manifest is a normal NEON-backed Package Scroll:

```neon
name: shinobiphp/codejitsu-ui
version: 0.1.0

types:
  world:
    plural: worlds
    extension: world
    scheme: world://
    class: ShinobiPHP\CodejitsuUi\Scrolls\World
    codec: neon

sources:
  ui:
    path: scrolls
```

Version one supports:

- package `name`, semantic `version`, description, keywords, homepage, documentation URL, and tags;
- `types`, keyed by canonical type name;
- `sources`, keyed by source alias and containing a package-relative path;
- Codejitsu compatibility constraints;
- capabilities provided and required;
- semantic dependencies on other Codejitsu packages or capabilities;
- optional configuration schema URIs.

Composer remains authoritative for installable versions and PHP/Composer dependency resolution. Package Scroll dependencies express Codejitsu runtime semantics and do not override Composer's solver. Packages declare source directories rather than enumerating every bundled Scroll; the Codex discovers their contents normally.

The generic installer derives the physical package root from Composer metadata. Absolute paths, parent traversal, symlink escapes, executable hooks, scripts, services, commands, migrations, and arbitrary configuration mutation are rejected or out of scope.

## Components

### Package Scroll Type

Core provides `Scrolls\Types\Package`. It validates the declarative shape and exposes normalized type and source declarations. This is the only new built-in type required for package bootstrapping. The existing enum remains the built-in compatibility facade and gains the `package` case because bootstrap must recognize `.package` before external registration is possible.

### Package Manifest Discovery

The Composer-facing discovery layer inspects installed packages and selects packages with either the normal Codejitsu type or an explicit infrastructure manifest:

```text
type = codejitsu-pkg OR extra.codejitsu.manifest is declared
extra.codejitsu.manifest = <relative path> for every participating package
```

The root package may also use this shape during monorepo development. Discovery returns Composer package name, installed path, manifest path, and declared version. It never scans arbitrary directories looking for manifests.

### Generic Package Installer

One trusted installer parses every Package Scroll using the built-in Package definition. It verifies:

- manifest package name equals the Composer package name;
- manifest paths remain within the installed package root;
- type definitions pass normal `TypeDefinition` validation;
- declared Scroll classes are syntactically valid class strings;
- codecs are known built-in codecs;
- source aliases and relative paths are valid;
- combined package declarations have no type, extension, scheme, or source conflicts.

Class existence is verified at runtime application, after Composer's autoloader is complete. Cache compilation validates the declaration without instantiating package classes.

### Compiled Package Cache

The installer atomically writes a PHP data file at:

```text
vendor/codejitsu/packages.php
```

The file returns plain arrays only. It contains a format version, deterministic package order, package identity/version, Composer-provided install path, normalized type declarations, normalized sources, and a fingerprint derived from installed package metadata plus manifest contents.

Packages are ordered lexicographically by Composer package name. Declarations retain manifest order within each package. The installer writes a temporary sibling file and renames it into place. Failure leaves the previous valid cache untouched.

The cache is generated state, never authoritative source, and may be removed safely. An empty installation produces a valid cache with no packages. If it is absent at boot, Codejitsu invokes the same compiler and writes atomically when possible; on a read-only installation it uses the compiled result in memory. A malformed existing cache fails closed. Composer lock/package metadata and Package Scroll contents form the cache fingerprint.

### Runtime Package Registry

Codejitsu bootstrap loads built-in types first, then reads the compiled package cache. A `PackageRegistry` applies each declaration in deterministic order:

1. validate cache format;
2. construct and register package-owned `TypeDefinition` values;
3. resolve package-relative source paths against the installed package root recorded by Composer discovery;
4. register and load declared sources through the Codex;
5. retain package provenance for diagnostics and future cache inspection.

Runtime registration uses the existing injected `TypeRegistry` and Codex source APIs. It does not introduce static global registries. A missing cache means no ecosystem packages and is not an error. A malformed cache or missing declared class fails boot with an actionable exception.

### Package Workspace Boundaries

`packages/package` owns the Package Scroll, manifest discovery/validation, cache compiler, cache reader, and runtime `PackageRegistry`. It depends on the existing core/Scroll/Codex boundaries as required, and contains no Composer event integration.

`packages/composer-plugin` owns only the Composer plugin adapter and depends on `packages/package` plus Composer's plugin API. The root aggregate requires both packages. The dependency direction is:

```text
root aggregate
  -> composer-plugin
      -> package
          -> core / scrolls / codex contracts
```

Neither package depends on the root aggregate, preventing a dependency cycle.

### Composer Plugin

A separate workspace package, `packages/composer-plugin`, is a Composer plugin and subscribes to the dependency lifecycle events needed to rebuild after install, update, and removal. It calls the generic manifest discovery/compiler and does not contain a second registration implementation.

The root aggregate requires the plugin so the package cache cannot silently become stale after Codejitsu-managed dependency changes. Composer's standard `allow-plugins` security mechanism remains authoritative; installation must report Composer's normal approval requirement and Codejitsu does not bypass it.

The plugin must avoid recursive Composer execution. Existing `PackageManager` commands continue to invoke Composer normally; successful Composer lifecycle events refresh the package cache.

### Package Commands and Search

The Scroll-driven CLI exposes `pkg:list`, `pkg:search`, `pkg:info`, `pkg:install`, `pkg:uninstall`, `pkg:update`, and `pkg:cache:{status,rebuild,clear}`. `pkg:uninstall` delegates to Composer removal; the existing `pkg:remove` name may remain as a compatibility alias.

Version one combines generic Catalog Scrolls with installed Composer metadata. Catalogs may be public, private, or project-local and package entries use `kind: package`; the same index can later describe Scrolls, Context resources, apps, licenses, and other resource kinds. Installed package information combines Composer metadata, normalized Package Scroll data, and cache status; catalog inspection never downloads or executes package code.

## Data Flow

```text
composer install/update/remove
  -> Codejitsu Composer plugin
  -> discover installed codejitsu-pkg and explicitly manifested infrastructure packages
  -> locate declared codejitsu.package files
  -> generic Package Scroll installer validates all declarations
  -> atomically compile vendor/codejitsu/packages.php

application boot
  -> register built-in Scroll types
  -> load compiled package cache, or compile it if absent
  -> register package-owned types
  -> register package-owned sources
  -> load project default/context sources
  -> application Codex ready
```

Package type definitions must be registered before any package source is discovered so new file extensions can be recognized during the same boot.

## Failure Behavior

- A `codejitsu-pkg` without `extra.codejitsu.manifest` fails cache compilation.
- A missing or unreadable manifest fails with the Composer package name and path.
- Invalid NEON or manifest fields fail with package provenance.
- Duplicate names, extensions, schemes, or source aliases fail before the cache is replaced.
- Paths that are absolute, traverse upward, or escape through symlinks are rejected.
- Runtime class-loading failures identify the package, type name, and class.
- Removed packages disappear on the next successful cache rebuild.
- Composer failure does not independently rewrite the cache through Codejitsu's `PackageManager`; lifecycle events determine successful rebuild timing.

## Security Boundary

Package Scroll installation is data processing, not code execution. The Composer plugin parses manifests and writes normalized data but does not instantiate declared Scroll classes or call package-provided hooks. Runtime necessarily autoloads package classes when registering and using their types; that occurs under the application's normal dependency trust boundary.

Executable lifecycle hooks, migrations, arbitrary file copying, environment changes, and project manifest mutation require a separate future design with explicit policy and consent.

## Testing

Behavioral coverage will include:

- Package Scroll validation and normalization;
- discovery limited to `codejitsu-pkg` packages and infrastructure packages with explicit manifests;
- deterministic compilation regardless of Composer enumeration order;
- atomic cache replacement and preservation after a failed rebuild;
- path traversal and symlink escape rejection;
- duplicate declaration diagnostics with package provenance;
- plugin event subscription and rebuild delegation using Composer fixtures;
- runtime registration of a fixture package-defined type and source;
- install/update/remove cache refresh behavior without network access;
- missing-cache bootstrap behavior;
- compatibility of all existing built-in Scroll behavior and CLI tests.

## Delivery Boundary

This slice is complete when a fixture `codejitsu-pkg` with a Package Scroll can be discovered by the Composer plugin, compiled into the package cache, and applied at runtime such that its custom Scroll type and source resolve through the Codex. Composer validation, strict optimized PSR autoload checks, the full PHPUnit suite, and a clean worktree must pass.

Afterward, development stops before UI implementation. The next branch may create `packages/ui` as the first production `codejitsu-pkg`, beginning with its Package Scroll and type declarations.
