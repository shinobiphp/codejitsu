# Glossary

**Scroll** — A first-class Codejitsu resource that can be discovered, addressed, resolved, hydrated, referenced, validated, and optionally executed.

**ScrollCodex / Codex** — The indexed registry and resolution boundary for Scrolls.

**Scroll Type** — A semantic resource category such as command, schema, capability, config, app, kata, or skill.

**Envelope** — The serialized/resource boundary carrying resource data and metadata.

**Identity** — Stable resource identity used for indexing and comparison.

**Identifier** — Immutable string value representing an identity component.

**URI** — Addressing syntax for resolving resources, including type, target, tenant, path, query, and version.

**Resolved** — A resolution DTO describing what a URI resolves to before or during hydration.

**Resolvable** — The target that can be hydrated from a resolution.

**Reference** — A declarative URI dependency from one Scroll to another.

**Capability** — An executable unit of behavior exposed through a Capability Scroll.

**Command Scroll** — An executable Scroll intended for invocation through a command interface, with description/usage metadata and optional Schema/Capability references.

**Namespace Command** — A Command Scroll that owns child command definitions, addressed using colon-separated names such as `scrolls:cache:rebuild`.

**Schema Scroll** — A Scroll that describes/validates structured input or resource data.

**Config Scroll** — A Scroll representing configuration as an addressable resource.

**Context** — Durable architectural memory stored under `.context/`.

**ArchIQ** — The planned code/architecture intelligence layer that analyzes repositories and emits structured architectural knowledge.

**Shinobi** — The eventual platform built on top of Codejitsu, combining runtime, orchestration, cognition, security, service/resource nodes, and contextual interfaces.

**Sovereign Interface** — The broader Shinobi vision for contextual, local-first, distributed interfaces over the runtime and resource mesh.
