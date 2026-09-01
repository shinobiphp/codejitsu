# Codejitsu

**Composable, intent-driven application infrastructure for modern PHP.**

Codejitsu models application capabilities, commands, schemas, configuration, and durable project context as discoverable resources called **Scrolls**. A **Codex** indexes and resolves those resources through stable URIs, while the canonical graph records how they relate.

> Codejitsu is alpha software. Its resource model and CLI are usable, but package boundaries and extension contracts are still being stabilized.

## Core ideas

- **Scrolls** are typed, versioned resources with stable identity, metadata, references, and optional executable behavior.
- **Codex** is the source-aware discovery, index, query, and resolution boundary.
- **Graph** nodes and named edges preserve semantic relationships between resources.
- **Capabilities** execute through explicit substrates and deny-by-default execution policy.
- **Context Scrolls** keep architectural memory readable by humans and addressable by tools.
- **Type registry** lets packages add Scroll types, extensions, URI schemes, codecs, and implementations without adding cases to a core enum.

## Requirements

- PHP 8.4 or newer
- Composer 2

## Install

```bash
composer require shinobiphp/codejitsu
```

For repository development:

```bash
git clone https://github.com/shinobiphp/codejitsu.git
cd codejitsu
composer install
composer test
```

## CLI

```bash
./bin/codejitsu list
./bin/codejitsu scrolls
./bin/codejitsu make
./bin/codejitsu pkg:list
./bin/codejitsu pkg:search ui
./bin/codejitsu pkg:info shinobiphp/codejitsu
./bin/codejitsu context:tui
./bin/codejitsu make:context architecture/runtime
./bin/codejitsu make:catalog private
./bin/codejitsu make:pkg codejitsu/ui 'Astro UI integration'
```

Commands are themselves Scrolls. Their schemas, capabilities, and help metadata travel through the same Codex path as other resources.

## Package workspace

The repository currently develops these package boundaries together:

| Package | Responsibility |
| --- | --- |
| `codejitsu/core` | Shared resource, identity, graph, and kernel primitives |
| `codejitsu/scrolls` | Scroll contracts and resource behavior |
| `codejitsu/codex` | Indexing, querying, and resolution |
| `codejitsu/discovery` | Deterministic discovery strategies |
| `codejitsu/config` | Configuration resources |
| `codejitsu/schema` | Schema validation |
| `codejitsu/console` | Console integration |
| `codejitsu/package` | Package manifests, registry, installer, and cache |
| `codejitsu/composer-plugin` | Composer lifecycle integration |
| `codejitsu/context` | Deterministic project memory and terminal authoring |

The root `shinobiphp/codejitsu` package remains the installable aggregate while these boundaries stabilize.

Package catalogs may be bundled, project-local, or private. `pkg:list`, `pkg:search`, and `pkg:info` merge catalog entries with installed Composer metadata. `make:pkg` scaffolds and catalogs a package without adding it to the root requirements, so it remains visibly `available` until explicitly installed.

## Runtime state

Codejitsu stores disposable project runtime state below `var/`: compiled indexes in `var/cache`, temporary project files in `var/tmp`, and longer-lived task state in `var/work`. These directories are created lazily and their contents are not committed.

## Project context

The [`.context/`](.context/) directory is Codejitsu's durable architectural memory. Start with [the agent context protocol](.context/agent-context.ctx), then read [current state](.context/current-state.ctx) and the relevant architecture, concept, decision, and roadmap resources.

Source and tests define implemented behavior. Context explains intent, terminology, constraints, and roadmap.

At bootstrap, the project `.context/` directory is registered as source `context`. For example, `context://architecture/codex@context#1.0.0` resolves independently of the checkout's absolute path.

Packages can register an immutable `TypeDefinition` with the Codex type registry. The existing `Enums\Scrolls\Types` API remains a compatibility facade for built-in types; new package types do not require a core enum case.

## Development

```bash
composer validate --strict
composer dump-autoload --optimize --strict-psr
composer test
```

Run the complete local release gate with `composer check`. Use `composer test:installation` to verify a disposable tracked checkout can install from the committed lockfile and boot the real CLI.

`tools/check-php.php` intentionally remains standalone pre-bootstrap tooling so syntax errors can be reported even when Codejitsu itself cannot load.

See the [current roadmap](.context/roadmap/current.ctx) and [code standards](.context/code-standards.ctx) before architectural work.

## License

Codejitsu is currently proprietary. No open-source license is granted by this repository.
