# Ollama Starter Vessels Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Run product-neutral starter Sparks and Vessels against the local Codejitsu Ollama model through Neuron AI.

**Architecture:** Extend the existing provider adapter boundary instead of adding another runtime. Store reusable personalities and harness configuration as package-owned Scrolls discovered through the existing Codex.

**Tech Stack:** PHP 8.4+, Codejitsu Scrolls/Codex, Neuron AI, Ollama, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-09-02-ollama-starter-vessels-design.md`

## Global Constraints

- Sensei and Kage are products and must not be bundled as Codejitsu base Sparks.
- OpenAI credentials remain `env://` references; Ollama requires no credentials.
- Use the installed Neuron Ollama provider rather than a parallel HTTP client.
- Reuse the existing Context Toolset and approval policy.
- Add no inheritance mechanism.

---

### Task 1: Ollama Provider Adapter

**Files:**
- Modify: `packages/ai/src/Definitions/ProviderDefinition.php`
- Modify: `packages/ai/src/Neuron/ProviderFactory.php`
- Modify: `packages/ai/tests/Definitions/DefinitionLoaderTest.php`
- Modify: `packages/ai/tests/Neuron/ProviderFactoryTest.php`

**Interfaces:**
- Consumes: `ProviderDefinition::fromArray(array): ProviderDefinition`
- Produces: `ProviderFactory::make(ProviderDefinition, ?string): AIProviderInterface` supporting `ollama`

- [ ] Write failing tests proving Ollama accepts empty credentials and maps URL, model, and parameters.
- [ ] Run the focused tests and confirm the credential/provider failures.
- [ ] Make credential requirements adapter-specific and construct Neuron's Ollama provider.
- [ ] Run the focused tests and confirm they pass.
- [ ] Commit the provider adapter.

### Task 2: Bundled Starter Resources

**Files:**
- Create: `packages/ai/resources/providers/ollama-local.provider`
- Create: `packages/ai/resources/sparks/*.spark`
- Create: `packages/ai/resources/vessels/local-*.vessel`
- Modify: `packages/ai/tests/Definitions/DefinitionLoaderTest.php`
- Modify: `packages/ai/tests/Vessels/VesselRunnerTest.php`

**Interfaces:**
- Consumes: existing package type registry, Context Scrolls, and `toolset://context`
- Produces: `provider://ollama/local`, six starter Sparks, and two local Vessels

- [ ] Write failing discovery/resolution tests for every resource and both complete Vessel graphs.
- [ ] Run focused tests and confirm resources are absent.
- [ ] Add the Provider, Spark, and Vessel Scroll files.
- [ ] Run focused tests and confirm resolution passes.
- [ ] Commit the starter library.

### Task 3: CLI, Documentation, and Release Verification

**Files:**
- Modify: `README.md`
- Modify: `packages/ai/README.md`
- Modify: `.context/current-state.ctx`
- Modify: `.context/roadmap/current.ctx`
- Modify: `.context/todo.ctx`
- Modify: `packages/ai/resources/contexts/operations.ctx`

**Interfaces:**
- Consumes: shipped Provider/Spark/Vessel Scrolls and CLI commands
- Produces: accurate usage and resumable project/package Context

- [ ] Document exact starter resource names and example `ai:run` commands.
- [ ] Run `provider:test ollama/local`, all resource show commands, and a bounded live inference smoke.
- [ ] Run AI tests, root tests, installation test, syntax validation, manifest validation, audit, and `git diff --check`.
- [ ] Update verified counts and live-runtime status in Context.
- [ ] Commit documentation and verified status.
