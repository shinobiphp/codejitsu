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
./bin/codejitsu pkg:info shinobiphp/codejitsu
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

The root `shinobiphp/codejitsu` package remains the installable aggregate while these boundaries stabilize.

## Project context

The [`.context/`](.context/) directory is Codejitsu's durable architectural memory. Start with [the agent context protocol](.context/agent-context.ctx), then read [current state](.context/current-state.ctx) and the relevant architecture, concept, decision, and roadmap resources.

Source and tests define implemented behavior. Context explains intent, terminology, constraints, and roadmap.

## Development

```bash
composer validate --strict
composer dump-autoload --strict-psr
composer test
```

See the [current roadmap](.context/roadmap/current.ctx) and [code standards](.context/code-standards.ctx) before architectural work.

## License

Codejitsu is currently proprietary. No open-source license is granted by this repository.
