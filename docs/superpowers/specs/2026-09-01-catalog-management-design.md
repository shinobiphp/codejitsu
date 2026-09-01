# Catalog Management Design

## Goal

Make generic Catalog Scrolls inspectable and safely manageable from the CLI/TUI while preserving package-specific behavior behind extensible action providers.

## Commands

The `catalog` namespace provides `list`, `show`, `search`, and `edit`. List reports Catalog Scroll identity, source, entry count, and effective access. Show renders metadata and entries. Search spans all entries with an optional kind. Edit launches an interactive project-catalog manager.

Each child command declares a Schema Scroll for its positional parameters. `make:catalog` remains the creation command.

## Mutability

Only project catalogs stored below `<project>/catalogs` are writable in version one. Effective access is writable only when the source is project-owned, metadata does not specify `access: read-only`, and the backing file or parent directory is writable. Metadata may restrict storage but never elevate it.

Writes are validated and atomic. The TUI supports browsing, adding, removing, and raw editing through the existing Editor abstraction. Raw editor output is decoded and validated before replacement.

## Entry validation

Catalogs may declare `entrySchemas`, mapping an entry kind to a Schema URI. Universal Catalog validation remains in the Catalog Scroll. Catalog management additionally resolves and applies the kind schema before writing an entry or edited catalog.

## Actions

`CatalogActionProvider` supplies actions for an entry kind. The first provider exposes package info/install/uninstall using the existing PackageManager boundary. TUI actions are refreshed from installed state and consequential actions require confirmation.

Catalog-declared custom actions are data descriptors referencing registered capabilities; catalogs never contain shell commands or inline executable code. Execution support is restricted to capabilities tagged `catalog-action` and is not required for the initial package-provider vertical slice.

## Meta-catalog

A conventional `catalogs/sources.catalog` may contain entries with `kind: catalog`. Those entries are editable like any other entry and describe configured catalog locations, priority, enabled state, and access. This slice indexes and manages them but does not fetch remote locations. Future source adapters will consume them with URI deduplication and cycle detection.

## Cache

The compiled package cache remains derived bootstrap state and moves from executable PHP to bootstrap-safe JSON at `var/cache/codejitsu/packages.json`. `pkg:cache:*` remains its inspection/control surface. A virtual Cache Scroll type is deferred until more than one cache implementation needs a common resource contract.

## Package scaffolds

The package scaffolder generates conventional segmented PHP namespaces (`Codejitsu\\Ai`, `Codejitsu\\Ui`) while leaving generated packages uninstalled and catalog-visible.
