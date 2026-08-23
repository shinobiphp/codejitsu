# Code Standards

These standards apply to Codejitsu source and context-driven implementation work.

## PHP

- Target PHP 8.4+.
- Every PHP file uses `declare(strict_types=1);`.
- Prefer constructor property promotion for constructor-owned state.
- Prefer readonly properties when state is immutable.
- Prefer PHP 8.4 property hooks when they express validation, normalization, or derived access cleanly.
- Prefer asymmetric visibility when public read access and restricted mutation are required.
- Prefer enums, value objects, factories, strategies, and composition over primitive conditionals and inheritance-heavy designs.
- Avoid static helpers except where static behavior is inherently part of bootstrap or framework initialization.
- Keep public classes as behavioral contracts; use internal interfaces only when they provide a real internal wiring boundary.
- Do not add suffixes such as `Interface`, `Helper`, or `Manager` unless they are required for a deliberate naming/aliasing reason.

## Contracts and DTOs

- Contracts describe behavior, not implementation details.
- Commands, Queries, and Events are immutable DTOs.
- Use promoted readonly constructor properties for immutable DTO state whenever practical.
- Keep contracts small and composable.

## Resources and Scrolls

- Scrolls are resources first; their storage representation is an implementation detail.
- Keep logical resource identity separate from physical source location.
- URI paths represent logical resource/reference paths.
- URI `@source` selectors control resolution source/cascade and are not part of resource identity.
- Metadata intended for discovery and indexing should remain separate from hydrated resource behavior.
- Prefer immutable metadata/index representations where practical.

## Architecture

- Favor composition and explicit boundaries over convenience coupling.
- Keep discovery, indexing, resolution, hydration, validation, and execution as distinct responsibilities.
- Codex is the resource indexing/query/resolution boundary; consumers should not bypass it to inspect discovery sources directly.
- Query operations should operate on indexed metadata and should not require hydration of every matching resource.
- Source precedence must remain deterministic and testable.

## Tests

- Tests describe observable behavior and architectural contracts.
- Prefer focused unit tests for value objects, parsers, policies, and resource metadata.
- Use integration tests for discovery, Codex resolution, source cascading, hydration, and execution orchestration.
- Every new architectural behavior should have a regression test.
