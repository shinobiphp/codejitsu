# Context Scrolls and Extensible Type Registry Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Finish Codejitsu's Context Scroll vertical slice, publish a polished root GitHub README, and make Scroll types package-owned and registry-driven so `packages/ui` can be started next without modifying core enums.

**Architecture:** `.context/` becomes a named Codex source containing Markdown-backed `.ctx` resources with logical `context://` identities; the root `README.md` becomes the human and GitHub entrypoint. A new immutable Scroll type definition and registry replace enum switches in discovery, storage, URI resolution, creation, and hydration while preserving the existing built-in API during migration.

**Tech Stack:** PHP 8.4+, Composer, PHPUnit 13, Nette NEON, existing Codejitsu Graph/Scroll/Codex packages

**Spec:** `.context/current-state.ctx`, `.context/architecture/scrolls.md`, `.context/architecture/codex.md`, `.context/code-standards.md`, `.context/decisions/0001-scrolls-are-first-class-resources.md`, `.context/decisions/0002-codex-is-resource-index.md`

## Global Constraints

- Source and tests remain authoritative for implemented behavior; Context Scrolls describe intent and current state.
- `.context/` is the canonical named source for project Context Scrolls.
- Root `README.md` is the public GitHub entrypoint; `.context/README.md` is removed after its durable content is incorporated.
- Context content remains readable Markdown stored in `.ctx` files.
- Logical URI identity never depends on the absolute filesystem path.
- Codex remains the single discovery, index, query, and resolution boundary.
- Querying indexed metadata must not hydrate every resource.
- Packages register semantic Scroll types; core does not grow a new enum case for each package.
- Built-in type behavior and URI schemes remain backward compatible during migration.
- No `packages/ui` directory, UI Scroll types, Astro code, or lander code is created in this plan.

---

### Task 1: Publish the Root GitHub README and Define Context Migration

**Files:**
- Create: `README.md`
- Delete: `.context/README.md`
- Modify: `.context/agent-context.md`
- Modify: `.context/current-state.ctx`
- Test: `tests/Documentation/ContextLinksTest.php`

**Interfaces:**
- Consumes: Current `.context/README.md`, architecture, roadmap, and CLI behavior.
- Produces: A root project entrypoint and valid internal context links.

- [ ] **Step 1: Write a failing documentation-link test**

Create `tests/Documentation/ContextLinksTest.php` that recursively reads root and `.context` Markdown/Context documents, extracts relative Markdown links, and asserts each local target exists. Assert `README.md` exists and `.context/README.md` does not.

- [ ] **Step 2: Run the focused test and verify failure**

Run: `composer test -- --filter ContextLinksTest`

Expected: FAIL because root `README.md` is absent and `.context/README.md` exists.

- [ ] **Step 3: Create the polished root README**

Write a concise GitHub README containing: project promise, current alpha status, Scroll/Codex/Graph concepts, requirements, install command, CLI examples, package map, Context Scroll workflow, testing command, roadmap link, and license status. Do not claim unimplemented registry, UI, Sensei, Shinobi, or Archiq behavior.

- [ ] **Step 4: Move context entry instructions and repair links**

Delete `.context/README.md`. Update `.context/agent-context.md` to start with root `README.md`, then `.context/agent-context.md`, `current-state.ctx`, and relevant Context Scrolls. Replace every `.context/README.md` reference with the correct root or context target.

- [ ] **Step 5: Run documentation and full tests**

Run: `composer test -- --filter ContextLinksTest && composer test`

Expected: both commands exit 0.

- [ ] **Step 6: Commit**

```bash
git add README.md .context tests/Documentation/ContextLinksTest.php
git commit -m "docs: publish Codejitsu project entrypoint"
```

---

### Task 2: Convert Durable Context Documents into Context Scrolls

**Files:**
- Rename: `.context/architecture/*.md` -> `.context/architecture/*.ctx`
- Rename: `.context/concepts/*.md` -> `.context/concepts/*.ctx`
- Rename: `.context/decisions/*.md` -> `.context/decisions/*.ctx`
- Rename: `.context/roadmap/current.md` -> `.context/roadmap/current.ctx`
- Rename: `.context/agent-context.md` -> `.context/agent-context.ctx`
- Rename: `.context/code-standards.md` -> `.context/code-standards.ctx`
- Rename: `.context/glossary.md` -> `.context/glossary.ctx`
- Rename: `.context/ideas.md` -> `.context/ideas.ctx`
- Rename: `.context/todo.md` -> `.context/todo.ctx`
- Modify: `tests/Documentation/ContextLinksTest.php`
- Create: `tests/Scrolls/ContextSourceFixtureTest.php`

**Interfaces:**
- Consumes: Existing raw-Markdown Context discovery.
- Produces: Logical names such as `architecture/codex`, `decisions/0002-codex-is-resource-index`, and `roadmap/current` from `.ctx` paths.

- [ ] **Step 1: Write failing migration tests**

Assert every durable file below `.context/` except generated/tool files uses `.ctx`; discover `.context/`; assert names are relative without `.context` or `.ctx`; assert path segments become tags; assert content remains byte-for-byte Markdown.

- [ ] **Step 2: Run focused tests and verify failure**

Run: `composer test -- --filter 'ContextSourceFixtureTest|ContextLinksTest'`

Expected: FAIL while `.md` context documents remain.

- [ ] **Step 3: Rename resources with Git history preserved**

Use `git mv` for each durable document, keep `current-state.ctx` in place, and update all internal links and instructions to `.ctx` targets.

- [ ] **Step 4: Verify discovery behavior**

Keep Markdown parsing raw: heading and prose remain the payload. Do not introduce front matter. Path-derived tags remain deterministic and lowercase.

- [ ] **Step 5: Run focused and full tests**

Run: `composer test -- --filter 'ContextSourceFixtureTest|ContextLinksTest' && composer test`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add .context tests/Documentation/ContextLinksTest.php tests/Scrolls/ContextSourceFixtureTest.php
git commit -m "refactor: make project context discoverable scrolls"
```

---

### Task 3: Register `.context` as a Named Codex Source

**Files:**
- Modify: `src/Scrolls/ScrollCodex.php`
- Modify: `src/Scrolls/ScrollDiscovery.php`
- Modify: `src/Boot.php`
- Create: `tests/Scrolls/ContextSourceTest.php`

**Interfaces:**
- Consumes: `ScrollCodex::registerSource()`, Context discovery, source-aware URIs.
- Produces: bootstrap registration for source `context` and resolution such as `context://architecture/codex@context#1.0.0` using the repository's canonical selector syntax.

- [ ] **Step 1: Write failing source integration tests**

Build a temporary `.context` fixture with nested `.ctx` files. Assert bootstrap/discovery registers source `context`, metadata records source provenance, direct URI resolution works, and a missing context source produces an empty source rather than scanning the working directory.

- [ ] **Step 2: Run the focused test and verify failure**

Run: `composer test -- --filter ContextSourceTest`

Expected: FAIL because `.context` is not registered automatically.

- [ ] **Step 3: Implement explicit source registration**

Add one bootstrap path that registers the project-root `.context` directory as source `context`. Reuse existing discovery and source precedence; do not add Context-specific resolution logic to Codex.

- [ ] **Step 4: Preserve provenance and logical identity**

Ensure indexed entries record source `context` while identity remains `context://<relative-name>#<version>`. Absolute source paths must not enter IDs or serialized metadata.

- [ ] **Step 5: Run source and full tests**

Run: `composer test -- --filter ContextSourceTest && composer test`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add src/Scrolls/ScrollCodex.php src/Scrolls/ScrollDiscovery.php src/Boot.php tests/Scrolls/ContextSourceTest.php
git commit -m "feat: register project context as a Codex source"
```

---

### Task 4: Complete Targeted Context Query and Relationship Retrieval

**Files:**
- Modify: `src/Scrolls/IndexEntry.php`
- Modify: `src/Scrolls/ScrollCodex.php`
- Modify: `src/Scrolls/Scroll.php`
- Create: `tests/Scrolls/ContextQueryTest.php`

**Interfaces:**
- Consumes: Existing Codex `query`, source selection, Scroll graph references.
- Produces: targeted context queries by name/path, tags, source, URI, and outgoing reference without hydrating unrelated resources.

- [ ] **Step 1: Write failing query tests**

Index multiple Context Scrolls and a non-Context Scroll. Assert filters by `type=context`, logical path prefix, tag, source `context`, and referenced URI return only matching index entries. Instrument hydration and assert metadata-only queries perform zero hydrations.

- [ ] **Step 2: Run the focused test and verify failure**

Run: `composer test -- --filter ContextQueryTest`

Expected: FAIL for missing path-prefix or relationship metadata behavior.

- [ ] **Step 3: Add only required index metadata**

Expose normalized logical path, tags, source, and reference targets on immutable index entries. Extend the existing query API rather than adding a Context-specific repository.

- [ ] **Step 4: Implement graph-aware targeted retrieval**

Resolve outgoing references only for selected Context Scrolls after the metadata query chooses them. Return results through the Codex and preserve deterministic source precedence.

- [ ] **Step 5: Run query and full tests**

Run: `composer test -- --filter ContextQueryTest && composer test`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add src/Scrolls/IndexEntry.php src/Scrolls/ScrollCodex.php src/Scrolls/Scroll.php tests/Scrolls/ContextQueryTest.php
git commit -m "feat: support targeted Context Scroll retrieval"
```

---

### Task 5: Define the Extensible Scroll Type Contract

**Files:**
- Create: `src/Scrolls/TypeDefinition.php`
- Create: `src/Scrolls/TypeRegistry.php`
- Create: `src/Contracts/Scrolls/TypeRegistry.php`
- Create: `tests/Scrolls/TypeRegistryTest.php`

**Interfaces:**
- Produces: `TypeDefinition`, `TypeRegistry::register()`, `get()`, `forExtension()`, `forScheme()`, `has()`, and `all()`.

- [ ] **Step 1: Write failing registry tests**

Test registration and lookup by normalized name, extension, and scheme; deterministic order; duplicate-name/extension/scheme rejection; invalid class rejection; and a package-defined fake `world` type.

- [ ] **Step 2: Run the focused test and verify failure**

Run: `composer test -- --filter TypeRegistryTest`

Expected: FAIL because the registry does not exist.

- [ ] **Step 3: Implement immutable type definitions**

`TypeDefinition` contains normalized `name`, `plural`, `extension`, `scheme`, `scrollClass`, `codec`, and optional `schemaUri`. Require `scrollClass` to implement the Scroll contract and schemes to end in `://`.

- [ ] **Step 4: Implement conflict-safe registration**

Reject collisions with exceptions naming both registrations. Return definitions in stable registration order. Registry lookups are case-insensitive but return canonical definitions.

- [ ] **Step 5: Run registry and full tests**

Run: `composer test -- --filter TypeRegistryTest && composer test`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add src/Scrolls/TypeDefinition.php src/Scrolls/TypeRegistry.php src/Contracts/Scrolls/TypeRegistry.php tests/Scrolls/TypeRegistryTest.php
git commit -m "feat: define extensible Scroll types"
```

---

### Task 6: Register Built-in Types Through the Registry

**Files:**
- Modify: `src/Boot.php`
- Modify: `src/Enums/Scrolls/Types.php`
- Modify: `src/Scrolls/ScrollCodex.php`
- Create: `tests/Scrolls/BuiltinTypeRegistrationTest.php`

**Interfaces:**
- Consumes: `TypeRegistry` from Task 5 and existing enum definitions.
- Produces: one bootstrap registry containing app, capability, command, config, context, kata, schema, and skill definitions.

- [ ] **Step 1: Write failing compatibility tests**

Assert all current built-ins are registered with their existing class, extension, scheme, plural, and codec. Assert existing enum callers still resolve the same values during migration.

- [ ] **Step 2: Run focused tests and verify failure**

Run: `composer test -- --filter BuiltinTypeRegistrationTest`

Expected: FAIL because bootstrap does not expose a type registry.

- [ ] **Step 3: Add registry ownership to the composition root**

Construct and populate the registry once in Boot, inject it into Codex/discovery/store collaborators, and expose it through the existing application composition boundary without static global state.

- [ ] **Step 4: Turn the enum into a compatibility facade**

Keep enum cases and helpers temporarily for existing consumers, but route new discovery and construction through `TypeDefinition`. Add deprecation comments only where an actual replacement exists.

- [ ] **Step 5: Run compatibility and full tests**

Run: `composer test -- --filter BuiltinTypeRegistrationTest && composer test`

Expected: PASS with no built-in URI or extension changes.

- [ ] **Step 6: Commit**

```bash
git add src/Boot.php src/Enums/Scrolls/Types.php src/Scrolls/ScrollCodex.php tests/Scrolls/BuiltinTypeRegistrationTest.php
git commit -m "refactor: register built-in Scroll types"
```

---

### Task 7: Migrate Discovery, Storage, URI Resolution, and Creation

**Files:**
- Modify: `src/Discovery/ScrollDiscoverer.php`
- Modify: `src/Scrolls/ScrollDiscovery.php`
- Modify: `src/Scrolls/Stores/Filesystem.php`
- Modify: `src/Scrolls/Envelope.php`
- Modify: `src/Contracts/Scrolls/Envelope.php`
- Modify: `src/Commands/Make.php`
- Modify: `src/Uri/Drivers/Scroll.php`
- Create: `tests/Scrolls/PackageTypeIntegrationTest.php`

**Interfaces:**
- Consumes: Injected `TypeRegistry`.
- Produces: end-to-end discovery, hydration, resolution, persistence, and creation for package-defined types.

- [ ] **Step 1: Write a failing package-type integration test**

Register a fixture `world` type with `.world`, `world://`, NEON, and a fixture Scroll class. Discover `fixtures/worlds/demo.world`, index it, resolve `world://demo#1.0.0`, persist/reload it, and verify the make command offers `world` from the registry.

- [ ] **Step 2: Run the focused test and verify failure**

Run: `composer test -- --filter PackageTypeIntegrationTest`

Expected: FAIL because discovery and storage still switch on the enum.

- [ ] **Step 3: Replace enum iteration and switches**

Resolve source extensions, plural directories, codecs, schemes, and Scroll construction from `TypeDefinition`. Remove only switches made redundant by registry lookups; retain enum compatibility at public boundaries until callers migrate.

- [ ] **Step 4: Preserve typed envelope identity**

Change envelope type storage to the canonical registered type name while accepting legacy enum-backed values. Unknown types fail closed with a message containing the name and source.

- [ ] **Step 5: Drive `make:scroll` from registered types**

List registry definitions in stable order and create the selected package-owned type using its extension, codec, and Scroll class. Do not add a special case for `world`.

- [ ] **Step 6: Run integration and full tests**

Run: `composer test -- --filter PackageTypeIntegrationTest && composer test`

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add src/Discovery/ScrollDiscoverer.php src/Scrolls/ScrollDiscovery.php src/Scrolls/Stores/Filesystem.php src/Scrolls/Envelope.php src/Contracts/Scrolls/Envelope.php src/Commands/Make.php src/Uri/Drivers/Scroll.php tests/Scrolls/PackageTypeIntegrationTest.php
git commit -m "refactor: load Scroll types from packages"
```

---

### Task 8: Close the Codejitsu Slice and Stop Before UI

**Files:**
- Modify: `README.md`
- Modify: `.context/current-state.ctx`
- Modify: `.context/roadmap/current.ctx`
- Modify: `.context/todo.ctx`
- Modify: `.context/architecture/scrolls.ctx`
- Modify: `.context/architecture/codex.ctx`
- Create: `.context/decisions/0007-package-owned-scroll-types.ctx`

**Interfaces:**
- Consumes: Completed Context source and type registry.
- Produces: Accurate resumption context and an explicit UI-package starting contract.

- [ ] **Step 1: Update current-state documentation**

Record exact implemented behavior, public registry interfaces, source precedence, migration compatibility, passing test count, and the next slice. Mark completed Context and registry checklist items without erasing historical decisions.

- [ ] **Step 2: Record the package-owned type decision**

Document why packages register types, why core retains a temporary enum facade, collision policy, package bootstrap responsibility, and how future `packages/ui` will register world/scene/edition without core changes.

- [ ] **Step 3: Define the UI starting line without creating UI files**

State that the next branch may create `packages/ui`; its first task is to register UI-owned types against the completed registry. Do not specify unvalidated World/Scene schemas as implemented behavior.

- [ ] **Step 4: Run clean verification**

Run:

```bash
composer validate --strict
composer dump-autoload --strict-psr
composer test
git status --short
```

Expected: validation and tests exit 0; status contains only the intended documentation updates before commit.

- [ ] **Step 5: Commit**

```bash
git add README.md .context
git commit -m "docs: close Context and Scroll registry slice"
```

## Completion Boundary

The work is complete when root `README.md` is the polished GitHub entrypoint, `.context/` is a registered and queryable Context Scroll source, all durable context resources use `.ctx`, package-defined Scroll types work end to end through the registry, built-in behavior remains compatible, all tests pass, and `main` is ready for a new UI branch. `packages/ui` must not exist at completion.
