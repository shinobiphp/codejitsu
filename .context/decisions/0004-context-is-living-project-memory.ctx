# 0004 — Context Is Living Project Memory

## Status

Accepted

## Context

AI-assisted development is becoming part of the normal development workflow. Relying on an individual agent's memory makes architectural knowledge transient and prevents other agents from becoming productive quickly.

## Decision

`.context/` is versioned project memory and is intentionally tool-agnostic. Humans and AI agents should read it before non-trivial work and update it when architectural meaning changes.

The repository itself is the portable source of shared architectural context; no single model, chat history, or vendor-specific memory store is authoritative.

## Consequences

Codejitsu can be handed to another agent or future model without losing the reasoning behind its architecture. The same context can eventually be indexed and consumed by Codejitsu, ArchIQ, or Shinobi-native agents.
