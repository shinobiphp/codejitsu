# Codejitsu Context

`.context/` is the durable architectural memory of Codejitsu.

It records what the system is, why important design choices exist, the concepts and contracts that connect the pieces, and the direction toward Shinobi and ArchIQ.

## Authority

Code is authoritative for current behavior.

`.context/` is authoritative for architectural intent, terminology, constraints, rationale, and roadmap.

When implementation intentionally changes a documented decision, update the relevant context document in the same change.

## Structure

- `architecture/` — system structure and boundaries
- `concepts/` — core concepts and contracts
- `decisions/` — durable architectural decisions and rationale
- `roadmap/` — planned evolution and current priorities
- `glossary.md` — canonical terminology

## Working Rule

Prefer updating an existing context document over creating duplicate documentation. Add a decision record when a choice materially changes architecture, interfaces, resource semantics, or runtime behavior.

Context should remain concise enough for humans and future agents to consume.
