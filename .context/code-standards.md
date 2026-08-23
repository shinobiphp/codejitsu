# Code Standards

These standards apply to Codejitsu source and context-driven implementation work.

## PHP

- Target PHP 8.4+.
- Every PHP file uses `declare(strict_types=1);`.
- Prefer constructor property promotion for constructor-owned state.
- Prefer `readonly` properties when state is immutable.
- Prefer PHP 8.4 property hooks when they express validation, normalization, or derived access cleanly.
- Prefer asymmetric visibility when public read access and restricted mutation are required.
- Prefer native types, union/intersection types, `final`, enums, value objects, and first-class callables over docblock-only contracts or legacy patterns.
- Prefer explicit return types and parameter types everywhere practical.
- Prefer immutable objects and explicit state transitions over mutable shared state.
- Prefer factories, strategies, policies, specifications, adapters, and composition when they provide a meaningful boundary; do not force patterns where a simple object is clearer.
- Use attributes for declarative metadata and framework discovery, including resource discovery, handlers, capabilities, commands, listeners, and other extension points where reflection-based discovery is appropriate.
- Keep discovery metadata declarative; avoid scattering registration knowledge through hard-coded class maps when attributes can express the relationship cleanly.
- Use dependency injection rather than service locators or hidden global state.
- Avoid static helpers except where static behavior is inherently part of bootstrap or framework initialization.
- Keep public classes as behavioral contracts; use internal interfaces only when they provide a real internal wiring boundary.
- Do not add suffixes such as `Interface`, `Helper`, or `Manager` unless they are required for a deliberate naming/aliasing reason.
- Prefer small, cohesive classes with one clear reason to change.
- Avoid speculative abstraction; introduce an abstraction when variation, policy, or a meaningful boundary actually exists.

## Modern PHP Design

- Use PHP 8.4 language features deliberately rather than for novelty.
- Prefer property hooks for invariants and normalization that belong to the property itself.
- Prefer asymmetric visibility when a property should be publicly readable but privately/protectedly writable.
- Prefer constructor promotion for dependencies and immutable value state.
- Prefer `readonly` for immutable dependencies and value objects.
- Prefer enums over stringly-typed finite state.
- Prefer value objects over primitive obsession when a value has rules or domain meaning.
- Prefer first-class callables and closures over indirect callback plumbing where they improve clarity.
- Prefer `match` over long conditional chains for exhaustive value mapping.
- Use exceptions for exceptional failure; do not hide failures behind sentinel values when the contract requires a valid result.
- Keep I/O and infrastructure at architectural boundaries; keep domain/resource behavior independently testable.

## Contracts and DTOs

- Contracts describe behavior, not implementation details.
- Commands, Queries, and Events are immutable DTOs.
- Use promoted readonly constructor properties for immutable DTO state whenever practical.
- Keep contracts small and composable.
- Do not create an interface merely because a class exists; use interfaces where polymorphism, infrastructure isolation, or an internal wiring boundary requires one.

## Attributes and Discovery

- Attributes are the preferred mechanism for declarative discovery metadata.
- Discovery should inspect attributes and build an index/registry rather than require application classes to register themselves manually.
- Attributes should express intent; execution behavior belongs in the discovered class/strategy, not inside the attribute.
- Discovery must remain deterministic and cacheable.
- Reflection should be isolated behind discovery/indexing boundaries so runtime consumers do not need to know how discovery works.

## Design Patterns

Use patterns where they solve an actual design problem:

- **Strategy** for interchangeable algorithms, runtimes, resolution policies, or analysis behaviors.
- **Factory** when construction varies by type, configuration, or discovered metadata.
- **Policy/Specification** for composable rules and filtering decisions.
- **Adapter** when translating an external/infrastructure API into a Codejitsu contract.
- **Decorator** when behavior needs to be layered without changing the underlying contract.
- **Repository** only for a meaningful persistence/domain boundary, not as a wrapper around every data source.
- **Pipeline** when ordered transformations are a first-class behavior.
- **Composite** when resources/behaviors genuinely form trees or groups.

Prefer composition over inheritance. Inheritance should express a real substitutable behavioral relationship, not merely code reuse.

## Resources and Scrolls

- Scrolls are resources first; their storage representation is an implementation detail.
- Keep logical resource identity separate from physical source location.
- URI paths represent logical resource/reference paths.
- URI `@source` selectors control resolution source/cascade and are not part of resource identity.
- Metadata intended for discovery and indexing should remain separate from hydrated resource behavior.
- Prefer immutable metadata/index representations where practical.
- Context Scrolls use `.ctx` as their source extension and may contain Markdown as their payload.

## Architecture

- Favor composition and explicit boundaries over convenience coupling.
- Keep discovery, indexing, resolution, hydration, validation, and execution as distinct responsibilities.
- Codex is the resource indexing/query/resolution boundary; consumers should not bypass it to inspect discovery sources directly.
- Query operations should operate on indexed metadata and should not require hydration of every matching resource.
- Source precedence must remain deterministic and testable.
- Keep infrastructure concerns behind contracts and adapters.
- Prefer explicit dependency graphs over hidden coupling.

## Security and Reliability

- Default to zero-trust behavior at boundaries.
- Validate untrusted input before hydration or execution.
- Keep operations idempotent where practical.
- Preserve provenance and traceability for discovered/resolved resources.
- Never silently broaden permissions, source scope, or execution scope.

## Tests

- Tests describe observable behavior and architectural contracts.
- Prefer focused unit tests for value objects, parsers, policies, strategies, attributes, and resource metadata.
- Use integration tests for discovery, Codex resolution, source cascading, hydration, and execution orchestration.
- Every new architectural behavior should have a regression test.
- Test source precedence explicitly, including explicit source selection and fallback chains.
- Test discovered attributes as behavior, not merely reflection mechanics.
