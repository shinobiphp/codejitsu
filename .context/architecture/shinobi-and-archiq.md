# Shinobi and ArchIQ Direction

Codejitsu is the foundation/runtime, not the final product.

## Shinobi

Shinobi is the eventual modular platform built above Codejitsu. Its intended concerns include runtime orchestration, service/resource nodes, cognition, capabilities, secure communication, contextual interfaces, and distributed execution.

Codejitsu provides the resource and execution substrate:

```text
Codejitsu
  ├── Scrolls
  ├── Codex
  ├── URI / Identity
  ├── Discovery
  ├── Resolution
  ├── Execution
  └── Runtime
        ↓
Shinobi
  ├── orchestration
  ├── cognition
  ├── service/resource nodes
  ├── security/governance
  ├── contextual UI
  └── distributed execution
```

The boundary matters: Codejitsu should provide generic primitives while Shinobi composes them into platform behavior.

## ArchIQ

ArchIQ is the architecture/code intelligence layer that analyzes repositories and produces actionable understanding of structure, dependencies, risks, and modernization paths.

The long-term relationship is:

```text
ArchIQ
  ↓ analyzes
Codejitsu / Shinobi systems
  ↓ produces structured architectural knowledge
Scrolls / Codex / Context
  ↓ become machine-addressable knowledge
Shinobi / agents
  ↓ use that knowledge
build, repair, explain, and evolve systems
```

ArchIQ should eventually be able to consume multiple languages and repositories, produce normalized graph/analysis resources, and publish useful findings as machine-addressable resources rather than only static reports.

## Self-Maintaining Goal

The end state is not a chatbot bolted onto a framework. The goal is a system whose architecture, source, resource definitions, analysis, and operational context can all be discovered and reasoned about through the same resource-oriented substrate.

That enables future agents to:

- understand the architecture from repository context
- inspect the Codex and resource graph
- analyze source with ArchIQ
- propose or execute changes through capabilities/commands
- validate changes against schemas and tests
- record architectural decisions back into context

This is the beginning of Codejitsu becoming capable of helping build and maintain the ecosystem that contains it.
