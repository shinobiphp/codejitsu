# Architecture Overview

## Purpose

Codejitsu is the composable PHP runtime underneath the Shinobi Platform. Its job is to provide a durable runtime model for resources, discovery, resolution, execution, validation, and eventually long-running orchestration.

The framework is intentionally being built so that higher-level systems can describe capabilities and behavior as resources rather than hard-coded application wiring.

## Current Core

```text
Boot
  -> Kernel
      -> ScrollCodex
          -> ScrollDiscovery
          -> Scrolls
              -> Config
              -> Schema
              -> Capability
              -> Command
              -> App / Kata / Skill
      -> Application
          -> CLI
```

A Scroll is a first-class resource. `Scroll` provides common identity, metadata, hydration, serialization, references, and invocation behavior. Specialized Scroll types provide type-specific behavior.

`ScrollCodex` is the current in-process registry and resolution boundary. It indexes Scrolls by type/name/version and resolves typed URIs. Discovery is currently extension-driven and hydrates Scrolls from resource files. fileciteturn102file0L2-L2 fileciteturn103file0L2-L2 fileciteturn104file0L2-L2

## Resource Model

Scroll types currently include:

- `app://` / `.app`
- `capability://` / `.capability`
- `cmd://` / `.cmd`
- `config://` / `.config`
- `kata://` / `.kata`
- `schema://` / `.schema`
- `skill://` / `.skill`

The extension and URI scheme are part of the Scroll type definition rather than being scattered through application code. fileciteturn105file0L2-L2

## Design Direction

The intended runtime boundary is:

```text
Resource definition
    ↓
Discovery
    ↓
Codex / identity index
    ↓
Resolution
    ↓
Hydration
    ↓
Execution or consumption
```

Discovery should remain separate from hydration and execution. A resource should be identifiable and inspectable without executing it, and eventually help/metadata/index operations should avoid hydration whenever possible.

## Long-Term Runtime

The eventual Shinobi runtime will add long-running execution, event-driven orchestration, asynchronous work, isolation, retries, compensation, cryptographic provenance, and distributed/service-node concerns around this resource model.

Those concerns should compose around the existing boundaries rather than turning `Scroll` into an omnibus runtime object.
