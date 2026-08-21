# Runtime Architecture

## Current Runtime

Codejitsu currently boots named application runtimes such as the CLI. Boot creates or retrieves a Kernel, loads the ScrollCodex, and hands control to the application.

The desired boot shape remains:

```text
Kernel::boot()
    -> discover()
    -> load()
    -> run()
```

The exact implementation may evolve, but the architectural goal is that bootstrapping establishes the runtime and resource graph before application behavior executes.

## CLI

The CLI is an application over the Scroll runtime, not a separate command framework.

```text
argv
  ↓
Cli translator
  ↓
CliIntent
  ↓
ScrollCodex command lookup
  ↓
Command Scroll
  ↓
Schema reference
  ↓
Capability reference
  ↓
execution
```

Top-level help is generated from discovered Command Scroll metadata. Namespace child definitions are stored in the parent command's serialized data so usage can be displayed without hydrating every child.

## Runtime Direction

The eventual Shinobi runtime is designed for long-running processes and event-driven execution, including OpenSwoole, asynchronous commands/events, synchronous queries, retries, isolation, compensation, and auditable execution.

Codejitsu should keep short-lived CLI concerns from leaking into the core resource model so the same Scrolls can participate in daemon, web, worker, and distributed runtimes.

## Security Direction

Runtime defaults should be zero-trust. Resource resolution and execution should remain explicit, traceable, and eventually support signed/verified resource provenance and execution events.
