# Scroll Substrate Runtime Design

## Goal

Make executable scrolls substrate-aware and safely extensible so PHP is the first supported runtime while Lua, JavaScript/V8, WASM, and other substrates can be added without changing capability orchestration.

## Current State

`Capability` already accepts `source` plus an optional `substrate` attribute. `substrate: auto` is resolved by a detector that recognizes PHP tags and common `/usr/bin/env` or `/usr/bin` shebangs. The current PHP substrate executes source with in-process `eval()`, which is acceptable only as a temporary prototype and is not an isolation boundary.

## Design

### 1. Substrate contract

Keep the public substrate contract deliberately small:

```php
interface Substrate
{
    public function execute(string $source, ExecutionContext $context): mixed;
}
```

Substrates own execution semantics. Capability orchestration must not know how a language executes.

### 2. Substrate resolution

Introduce a resolver/registry responsible for mapping a normalized substrate name to a substrate implementation. `auto` is resolved through the existing detector before registry lookup.

The default registry contains PHP. Future registrations may provide Lua, V8 JavaScript, WASM, container, or external-process substrates without modifying `Capability`.

Unknown substrates fail with a deterministic `LogicException` naming the requested substrate.

### 3. Detection

Detection remains content-based when `substrate: auto` is used.

Supported MVP signals:

- `<?php` at the beginning of the source => `php`
- `#!/usr/bin/env <runtime>` => normalized runtime name
- `#!/usr/bin/<runtime>` => normalized runtime name
- otherwise use the configured default substrate (`php` for the MVP)

Detection is selection only; it does not execute anything.

### 4. Execution context

Executable source receives an `ExecutionContext` containing invocation arguments and the minimum runtime data needed by the substrate.

The execution context must not expose the application container, arbitrary services, filesystem handles, database connections, or unrestricted codex access to tenant code.

The existing context shape may evolve as isolation is implemented, but substrate code must consume the context through the public contract rather than reaching into global state.

### 5. PHP isolation

PHP execution must move behind an execution boundary rather than calling `eval()` directly from the parent Codejitsu process.

MVP isolation uses a dedicated PHP worker process with:

- a temporary working directory
- a controlled environment
- serialized input/context
- bounded stdin/stdout/stderr
- an execution timeout
- explicit exit-status/error handling
- no inherited application service objects

PHP process-level controls such as `open_basedir` and disabled functions are defense-in-depth only. They are not considered a tenant security boundary.

The long-term isolation boundary is an OS/container/namespace-backed worker substrate. The substrate contract must not prevent that migration.

### 6. Capability integration

`Capability::execute()` resolves a substrate and delegates execution to it. It must not contain a language-specific `match` statement.

The flow becomes:

```text
Capability
  -> normalize substrate
  -> detect when auto
  -> resolve substrate
  -> substrate.execute(source, context)
```

Legacy callable `target` capabilities remain supported and continue through the existing target path.

### 7. Errors

Errors are explicit and typed by boundary:

- invalid/empty source => `InvalidArgumentException`
- unknown substrate => `LogicException`
- unsupported runtime dependency => `LogicException`
- worker startup failure => execution exception with substrate/runtime context
- worker timeout => execution exception identifying the timeout
- non-zero worker exit => execution exception preserving stderr and exit status where safe

No raw child-process implementation details should leak into normal capability APIs.

## Testing Requirements

Tests must prove:

1. PHP detection works for PHP tags.
2. Shebang detection works for `/usr/bin/env` and direct `/usr/bin` forms.
3. Default substrate selection works when no signal exists.
4. Empty/default-disabled detection fails clearly.
5. Substrate resolution returns the registered PHP implementation.
6. Unknown substrates fail deterministically.
7. A PHP capability executes through the substrate and returns its result.
8. Execution context arguments reach PHP source.
9. Capability orchestration does not contain substrate-specific dispatch logic.
10. Worker timeout and non-zero exit are surfaced as execution failures.
11. The parent application process does not evaluate scroll source directly.
12. Existing callable-target capabilities continue to pass.

## Scope Boundary

This change does not require Lua/V8/WASM implementations yet. It establishes the substrate seam and a safe PHP execution path so those runtimes can be added independently.

Interactive `make:scroll` language selection/editor UX is a subsequent feature built on this substrate registry. Its eventual flow is:

```text
make:scroll URI
  -> choose available substrate/type
  -> open configured editor
  -> capture source
  -> detect/validate substrate
  -> write scroll
```

The editor integration must not be coupled to any individual runtime.

## Security Position

Executable scrolls are untrusted by default. No substrate may assume that PHP-level restrictions constitute tenant isolation. The architecture therefore separates capability resolution from process isolation and leaves room for containerized or namespace-isolated execution as the production boundary.
