# Minimal Context Package Design

**Status:** Approved for implementation  
**Date:** 2026-08-31

## Purpose

`codejitsu/context` provides deterministic, provider-neutral project memory for humans, agents, and future Vessels/Sparks. It must work without an LLM, network connection, vector store, or AI package.

## Scope

The package owns a `ContextMemory` service and the `context:list`, `context:show`, `context:search`, `context:check`, `context:sync`, `context:resume`, and `context:tui` Command Scrolls. The TUI is a terminal rendering of the same service and contains no separate persistence logic.

`list`, `show`, and `search` operate on Context Scrolls already indexed by `ScrollCodex`. `check` reports invalid local Markdown links and malformed managed-section markers. `sync` only replaces explicitly delimited managed sections and leaves all other prose byte-for-byte unchanged. Version one supports the `verification` managed section supplied as deterministic text by the caller. `resume` combines the current-state, roadmap, and todo Context Scrolls into an agent briefing.

## Managed Sections

Authored files opt in with paired markers:

```markdown
<!-- codejitsu:managed verification:start -->
generated content
<!-- codejitsu:managed verification:end -->
```

Missing markers are a no-op. Duplicate, nested, or unmatched markers fail validation. AI-generated architectural edits remain proposals and are outside this package.

## Package Boundary

The workspace package depends on `codejitsu/core`, `codejitsu/scrolls`, `codejitsu/codex`, and `codejitsu/console`. AI, Neuron, Vessel, Spark, embeddings, semantic search, and full-screen terminal libraries are excluded.

## Completion

The package is complete when the real CLI can list, show, search, check, sync, resume, and render indexed Context Scrolls; managed synchronization demonstrably preserves authored prose; all workspace and release checks pass.
