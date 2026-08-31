# ADR-006: CLI drivers and Command Scroll discovery

## Status

Accepted

## Decision

`Codejitsu\Apps\Cli` owns the CLI application lifecycle but delegates terminal parsing, help rendering, and command dispatch to a `Codejitsu\Contracts\Console\Driver`.

Symfony Console is the initial driver. The driver is replaceable without changing Command Scrolls or CLI middleware.

The Codex remains the source of truth for discovered Command Scrolls. A driver receives the discovered commands and an execution callback; execution continues through the CLI pipeline before the Command Scroll runs.

Command Scroll usage metadata supplies positional arguments and options to the Symfony driver. Class-string targets are supported for executable Command Scrolls and are instantiated by the command at execution time.

## Consequences

- Symfony is an implementation detail of the default CLI driver.
- Alternative terminal implementations can be introduced without changing commands.
- Packages and applications extend the CLI by contributing Command Scrolls to the Codex.
- Middleware remains part of the Codejitsu CLI lifecycle rather than being bypassed by the driver.
