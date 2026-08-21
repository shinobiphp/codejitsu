# Living Context Protocol

This directory is Codejitsu's shared architectural memory. Any human or AI agent working on the repository should treat it as the project's durable context layer.

## Start Here

Before making a non-trivial architectural change:

1. Read `.context/README.md`.
2. Read the relevant documents under `architecture/` and `concepts/`.
3. Read relevant decision records under `decisions/`.
4. Read `roadmap/` when the change affects planned work.
5. Inspect the current source/contracts before assuming the documentation is current.

The repository's source code and tests define current behavior. Context documents explain intent, terminology, constraints, and rationale.

## Living-Context Rule

Context is not a historical archive that is updated only occasionally. It is a living part of the repository.

When a change materially affects:

- architecture or subsystem boundaries
- public contracts or resource semantics
- identity or URI semantics
- discovery, resolution, hydration, or execution behavior
- important security/runtime constraints
- a durable design decision
- the roadmap or long-term direction

update the appropriate context document in the same change or pull request.

Do not update context merely because implementation details changed internally without changing architectural meaning.

## Decision Records

Create a new decision record when the project makes a meaningful architectural choice that future maintainers may otherwise have to rediscover.

Use sequential names:

```text
0001-short-title.md
0002-short-title.md
0003-short-title.md
```

A decision record should state:

- Context
- Decision
- Alternatives considered
- Consequences
- Status

Do not rewrite old decisions to erase history. If a decision is superseded, mark it superseded and create a new decision record explaining the replacement.

## Truth and Conflicts

When context and code disagree:

1. Determine whether the code is intentionally ahead of the documentation.
2. If so, update the context as part of the change.
3. If the documented architecture is still intended and the code is accidental, fix the implementation instead of documenting the bug as design.
4. Never silently invent architecture to make code appear consistent.

Tests are the executable specification for current behavior.

## Agent Behavior

Agents should:

- prefer existing concepts and terminology over inventing synonyms
- preserve established boundaries unless a change explicitly revisits them
- search context before proposing new abstractions
- update context when architectural meaning changes
- reference source files/contracts when documenting current implementation
- distinguish current state from planned state
- avoid copying large amounts of source code into context
- keep documents concise, composable, and easy to retrieve semantically

Agents should not:

- treat `.context/` as executable configuration
- put secrets, credentials, tokens, or environment-specific private data here
- use context documents as a substitute for tests
- claim unfinished roadmap items are implemented
- create duplicate documents for the same concept without a clear reason

## Cross-Agent Portability

The context is intentionally tool-agnostic.

An agent that has never seen the project should be able to become useful by reading this directory and then inspecting the code it references.

The expected workflow is:

```text
read context
  ↓
inspect source
  ↓
form understanding
  ↓
make change
  ↓
run tests
  ↓
update context when architecture changed
```

This protocol applies equally to ChatGPT/Codex, Gemini, Claude, local coding agents, ArchIQ, and future Codejitsu-native agents.

## Self-Referential Goal

Codejitsu is intended to eventually understand and help maintain itself. Therefore the context model must remain structured, versioned, explicit, and machine-consumable enough for future discovery/indexing systems to consume it without relying on one particular LLM's memory.
