# Scroll Substrates and Sandboxed Execution Design

## Goal

Make executable Codejitsu Scrolls language/runtime agnostic while keeping execution composable, policy-controlled, and tenant-safe.

## Scope

Initial substrates:

- `php` — PHP 8.5 runtime, isolated execution boundary rather than in-process `eval` for production execution.
- `lua` — LuaSandbox extension.
- `javascript` — V8Js extension.
- `wasm` — Wasmtime 48 CLI/runtime with WASI as the capability boundary.

The architecture must permit additional substrates such as Python or other WASM-hosted languages without modifying Scroll types or command dispatch.

## Scroll Model

Executable Scrolls may contain either a traditional callable `target` or inline executable `source`.

Example:

```neon
name: hello/world
type: capability
version: 1.0.0
substrate: php
source: """
<?php
return "Hello " . ($context->arguments[0] ?? "shinobi");
"""
```

`substrate` is optional when `auto` detection is desired. Explicit substrate selection wins over detection.

## Substrate Resolution

Resolution order:

1. Explicit `substrate` attribute.
2. Source detection from the first line/content markers.
3. Configured default substrate (`php`).

Detection initially supports:

- `<?php` → `php`
- `#!/usr/bin/env lua` → `lua`
- `#!/usr/bin/env node` / `javascript` → `javascript`
- `#!/usr/bin/env js` → `javascript`
- `#!/usr/bin/env wasmtime` → `wasm`
- direct `/usr/bin/...` equivalents

Detection is a registry concern, not a responsibility of individual Scroll types.

## Execution Contract

A substrate executor consumes source plus an `ExecutionContext` and returns a value or throws a typed/runtime exception.

The substrate registry maps stable names to executors. `Capability` resolves the executor through the registry and never contains a substrate `switch`.

## Execution Context

Execution context must carry:

- positional/named arguments already available to Scrolls;
- ScrollCodex reference where appropriate;
- tenant/context identity when supplied by the caller;
- execution/resource policy;
- controlled environment values;
- controlled filesystem roots and working directory;
- network permission state.

The context exposed to scripts is the controlled execution context, not the host application/container.

## Security / Sandbox Policy

Default deny:

- no host filesystem access except explicitly mounted/scoped paths;
- no network access unless explicitly granted;
- no unrestricted environment variables;
- no process spawning from script runtimes;
- no access to another tenant's context or storage;
- bounded execution time and memory where the substrate/runtime supports it.

PHP must not use in-process `eval` as the final production execution boundary because PHP code can escape application-level restrictions. The first safe implementation should use an isolated PHP subprocess with a controlled working directory/environment and explicit timeout. Stronger container/process isolation can replace the implementation later without changing the substrate contract.

Lua uses LuaSandbox limits and its restricted API surface.

V8Js uses the V8 isolate/runtime and explicit execution/resource limits where exposed by the installed extension.

WASM uses Wasmtime/WASI with only explicitly granted preopened directories/environment/stdio and no network capability by default.

## Interactive `make:scroll`

Interactive creation should:

1. Display available Scroll types.
2. Display available executable substrates when the selected type is executable.
3. Ask for logical name/identifier.
4. Determine the next version automatically when a matching Scroll exists; default to `1.0.0` otherwise.
5. Open an editor for source content when the selected type supports inline executable source.
6. Persist the selected substrate and source using the Scroll's NEON representation.
7. Preserve multiline source exactly apart from the editor's normal terminal newline behavior.

The editor mechanism must be injectable/testable rather than hard-coded into Scroll domain objects.

## Architecture

```text
Scroll
  -> Scroll Type
  -> Substrate Resolver
      -> Substrate Registry
          -> Executor
              -> Execution Context + Policy
                  -> Sandbox / Runtime
                      -> Result
```

The registry is the composition root for built-in substrates. Scroll types remain unaware of concrete runtime implementations.

## CLI Integration

`scroll:run` executes an executable Scroll through the same substrate pipeline used by direct capability invocation.

`make:scroll` uses the registry to present available substrates and delegates source editing to an editor abstraction.

Existing `scrolls:*`, `scroll:*`, `make:*`, and `hello` behavior must remain compatible unless explicitly superseded by the new interactive flow.

## Testing Requirements

Tests must cover:

- substrate registration and lookup;
- explicit substrate precedence;
- automatic detection;
- default substrate fallback;
- unsupported substrate failure;
- PHP execution;
- Lua execution when extension is available;
- JavaScript execution when V8Js is available;
- WASM execution through Wasmtime when installed;
- execution policy propagation;
- denied filesystem/network/process behavior at the adapter boundary;
- `Capability` source execution through the registry;
- interactive `make:scroll` flow using a fake editor/input layer;
- version discovery/incrementing;
- generated multiline NEON source round-trip;
- end-to-end `scroll:run` for at least PHP and WASM.

Runtime-dependent tests must skip cleanly when an optional runtime is unavailable; CI should still exercise the pure PHP/core tests everywhere.
