# Catalog and Context Tooling Design

## Goal

Finish the package catalog CLI and make Context Scroll authoring practical before beginning the AI or UI packages.

## Package commands

Catalog Scrolls are the discovery source for available Codejitsu packages. `pkg:list`, `pkg:search`, and `pkg:info` read package-kind catalog entries and merge installed Composer metadata over them. `pkg:install` accepts a catalog package name and resolves its `composer://` location before invoking Composer. Removal and updates remain Composer operations against installed packages.

## Creation commands

`make:context <name>` creates `.context/<name>.ctx` and edits its initial Markdown content. `make:catalog <name>` creates a catalog in the project `catalogs/` directory. `make:pkg <vendor/name>` creates a minimal package directory containing `composer.json`, `codejitsu.package`, `src/`, and `tests/`, then adds an available package entry to the project package catalog. It does not modify root Composer requirements or install the package.

Names are normalized logical paths and must reject absolute paths, traversal, empty segments, and overwrites.

## Context terminal UI

`context:tui` becomes an interactive selector over existing Context Scrolls plus Create and Quit actions. Selecting a Scroll edits it through `$EDITOR`, then `$VISUAL`, then `nano`. Create asks for the logical name and uses the same creation service as `make:context`. Direct `context:create` and `context:edit` commands expose the same behavior without duplicating file logic.

## Cleanup

Remove shipped hello/demo command resources and their CLI expectations while retaining test-local synthetic fixtures that verify generic command behavior. Reconcile README and Context Scrolls with implemented package catalogs, commands, and next steps.

Codejitsu-owned runtime state lives below `var/`: compiled state in `var/cache`, project-scoped temporary state in `var/tmp`, and longer-lived task state in `var/work`. Directories are created lazily and their contents are ignored. The standalone PHP syntax checker remains pre-bootstrap tooling because it must still run when application PHP cannot be loaded.

## Verification

New behavior is developed test-first. The release gate is `composer check`, `composer audit --no-interaction`, `composer test:installation`, package cache rebuild, and real CLI smoke coverage.
