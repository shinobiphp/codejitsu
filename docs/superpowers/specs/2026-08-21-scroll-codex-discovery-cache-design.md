# ScrollCodex Discovery and Cache

## Goal
Make `ScrollCodex` the single owner of Scroll discovery, indexing, caching, invalidation, and resolution so every runtime can load the complete Scroll set without hardcoded type-specific discovery in `Boot` or applications.

## Scope
The change covers the existing Codejitsu Scroll pipeline and CLI:

- recursively discover supported Scroll resources from configured Scroll roots
- determine Scroll type from the declared extension mapping
- hydrate and register all discovered Scrolls in one Codex
- persist a cache containing the indexed resource metadata and source manifest
- reuse a valid cache on subsequent launches
- invalidate the cache when source files are added, removed, modified, or renamed
- explicitly clear or rebuild the cache
- make `Boot` load the Codex rather than discovering commands itself
- make CLI command listing consume the Codex
- support `./bin/codejitsu`, `./bin/codejitsu --help`, `./bin/codejitsu -h`, and `./bin/codejitsu help`
- preserve cross-Scroll references such as `schema://hello` and `capability://hello`

## Architecture
`Boot` creates/configures a `ScrollCodex` and asks it to load the configured resource roots. `ScrollCodex` first checks its cache manifest; on a valid hit it hydrates the cached index, otherwise it discovers all resource files, hydrates Scrolls, registers them by canonical identity, and writes a new cache.

Discovery is extension-driven by `Codejitsu\Enums\Scrolls\Types`, not by application-specific directory names. A source tree may contain `.cmd`, `.schema`, `.capability`, `.config`, and future Scroll extensions under the configured Scroll roots.

The cache is an implementation detail of the Codex. It contains a manifest of source files and signatures plus serialized Scroll resource data sufficient to rebuild the in-memory index without reparsing every source file. The cache is invalidated explicitly or when the manifest no longer matches the current resource tree.

## Cache semantics

A cache hit is valid only when:

1. the cache exists and is readable
2. the cache format version is supported
3. every discovered source file has the same relative path, size, modification time, and content hash recorded in the manifest
4. no new source file exists outside the manifest
5. no recorded source file has disappeared

A cache miss or invalid cache triggers a full rebuild.

Cache operations are:

- `load()` — load a valid cache or rebuild automatically
- `rebuild()` — force rediscovery and rewrite the cache
- `invalidate()` — remove the cache without rediscovering

## CLI behavior

No arguments, `--help`, `-h`, and `help` render generated command usage from command Scrolls in the Codex.

Unknown commands render usage to stderr and exit non-zero.

Command execution resolves the Command Scroll from the Codex and invokes it. Command Scroll references must resolve through the same Codex, allowing commands to validate against Schema Scrolls and delegate execution to Capability Scrolls.

## Testing

The implementation must include tests for:

- discovery of multiple Scroll types from extension-driven source files
- cache creation after a cold load
- cache hit without reparsing unchanged sources
- automatic cache invalidation after source modification
- automatic cache invalidation after source addition/removal
- explicit invalidate and rebuild behavior
- command and reference resolution from the shared Codex
- CLI no-argument usage
- CLI `--help`, `-h`, and `help`
- unknown command usage/error handling
- end-to-end command execution with Schema and Capability references

## Non-goals

This change does not introduce a new identity model, new persistence backend, or a second resource registry. Existing `Codejitsu\Identity`/metadata, Store, URI, Scroll, and Codec abstractions remain authoritative. Future cache stores may replace the initial filesystem implementation without changing the ScrollCodex contract.
