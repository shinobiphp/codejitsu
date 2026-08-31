# Minimal Context Package Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a provider-neutral Context memory package with deterministic commands and a minimal TUI.

**Architecture:** A single `ContextMemory` service consumes `ScrollCodex`; Command Scroll handlers and the TUI are thin adapters over it. Managed updates require explicit markers and never regenerate entire files.

**Tech Stack:** PHP 8.4+, Codejitsu Scroll/Codex/Command APIs, PHPUnit 13.

**Spec:** `docs/superpowers/specs/2026-08-31-context-package-design.md`

## Global Constraints

- No AI, Neuron, embeddings, network access, or separate persistence layer.
- Only explicitly marked managed sections may be rewritten.
- Existing Context Scroll URI/source behavior remains compatible.

### Task 1: Package and Memory Service

- [ ] Add `packages/context/composer.json` and root workspace wiring.
- [ ] Write failing tests for list, show, search, check, managed sync, and resume.
- [ ] Implement `ContextMemory` with those deterministic operations.
- [ ] Run focused tests and commit.

### Task 2: Command Scroll Surface

- [ ] Write failing command/CLI tests for `context:*` routes.
- [ ] Add handlers, command metadata, and capabilities using the existing command path.
- [ ] Run focused tests and commit.

### Task 3: Minimal TUI and Verification

- [ ] Write a failing rendering test for a terminal-friendly indexed Context browser.
- [ ] Implement `ContextTui` and route `context:tui` through it.
- [ ] Run `composer check`, `composer audit`, `composer test:installation`, and commit.
