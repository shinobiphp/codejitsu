# M1 App Spec Composition Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make Codejitsu author, discover, resolve, compose, validate, and inspect deterministic effective App definitions.

**Architecture:** Extend the existing Scroll type registry with Spec, keep Codex as the URI resolution boundary, and add focused Spec conformance and App composition services. Existing Schema validation performs structural validation; Spec adds typed-reference and exact capability-provider conformance; App composition returns an immutable effective definition for Shinobi M2.

**Tech Stack:** PHP >=8.4, existing Codejitsu Scroll/Codex/Schema/Console packages, Nette NEON, Opis JSON Schema, PHPUnit 13.3.

**Spec:** `.context/architecture/app-spec-composition.ctx`

## Global Constraints

- M1 ends before runtime execution.
- Reuse existing TypeRegistry, ScrollCodex, Schema Scroll and command patterns.
- Codex must not gain App-specific composition behavior.
- A Shinobi App defaults to `spec://shinobi/app`; it does not implicitly extend `app://shinobi`.
- Apps have at most one explicit parent.
- Validate the effective composed App.
- Provider conformance is exact string matching in M1.
- Security/IAM/policy and dependency installation are deferred.

## Review Focus

- A parent cycle must fail with the complete useful inheritance path, not recurse indefinitely.
- Duplicate resource URIs with source/version selectors must deduplicate by normalized identity without silently selecting a different resource.
- A referenced URI that resolves but has the wrong Scroll type must fail conformance.
- A malformed capability `provides` value must fail deterministically instead of being coerced.
- An explicit child Spec must take precedence over inherited/default Spec while an omitted child Spec preserves the inherited/default contract.

---

### Task 1: First-class Spec Scroll type

**Files:**
- Create: `src/Scrolls/Types/Spec.php`
- Modify: `src/Enums/Scrolls/Types.php`
- Test: `tests/Scrolls/TypeRegistryTest.php`
- Test: `tests/Scrolls/Types/SpecTest.php`

**Interfaces:**
- Consumes: existing `Scroll`, `Types`, `TypeDefinition`.
- Produces: `Spec extends Scroll`, `Types::SPEC`, `spec://`, `.spec`, plural `specs`.

- [ ] Add failing registry tests asserting `TypeRegistry::builtins()->forScheme('spec')` and `forExtension('spec')` return the Spec definition.
- [ ] Add a failing Spec hydration test proving `subject` and `schema` must be non-empty strings when present.
- [ ] Add `Spec` and wire every exhaustive `Types` match/map method.
- [ ] Run only TypeRegistry/Spec tests.
- [ ] Commit: `feat: add spec scroll type`.

### Task 2: Capability provider declarations

**Files:**
- Modify: `src/Scrolls/Types/Capability.php`
- Test: `tests/Scrolls/Types/CapabilityTest.php`

**Interfaces:**
- Consumes: Capability attributes.
- Produces: `Capability::provides(): array` returning normalized unique non-empty provider strings.

- [ ] Add tests for empty providers, multiple exact providers, duplicate normalization, and malformed non-string/list input.
- [ ] Implement `provides()` and hydration validation without changing capability execution.
- [ ] Run Capability tests.
- [ ] Commit: `feat: declare capability providers`.

### Task 3: Spec structural schema and conformance result

**Files:**
- Create: `src/Specs/ValidationFailure.php`
- Create: `src/Specs/ValidationResult.php`
- Create: `scrolls/schemas/codejitsu_spec.schema`
- Test: `tests/Specs/ValidationResultTest.php`
- Test: `tests/Scrolls/Types/SpecTest.php`

**Interfaces:**
- Produces: immutable `ValidationFailure(string $path, string $message)`; immutable `ValidationResult` with `valid()`, `failures()`, and throwing assertion method.
- Produces: `schema://codejitsu/spec#1.0.0`.

- [ ] Pin the repository's existing Schema Scroll source format from current schema fixtures before writing the new resource.
- [ ] Add tests for valid/invalid result aggregation and diagnostic paths.
- [ ] Add the Spec schema using only validation keywords already supported by the current JsonSchema adapter.
- [ ] Add a test resolving the schema through normal Codex discovery and validating a representative Spec payload.
- [ ] Run focused Specs/Schema tests.
- [ ] Commit: `feat: define spec validation contract`.

### Task 4: Semantic Spec invocation

**Files:**
- Modify: `src/Scrolls/Types/Spec.php`
- Create: `src/Specs/SpecValidator.php`
- Test: `tests/Specs/SpecValidatorTest.php`

**Interfaces:**
- Consumes: `ScrollCodex`, subject `Scroll`, Spec attributes `subject`, `schema`, `requires.capabilities`.
- Produces: `Spec::__invoke(Scroll $subject): ValidationResult`.
- Produces: `SpecValidator::validate(Spec $spec, Scroll $subject): ValidationResult`.

- [ ] Test incompatible subject type.
- [ ] Test referenced structural Schema validation.
- [ ] Test typed App reference success/failure for extends/config/schemas/capabilities.
- [ ] Test wrong resolved type.
- [ ] Test exact required provider success, inherited/effective provider input, missing provider, and malformed `provides`.
- [ ] Implement validator using Codex resolution and existing Schema::validate(), collecting semantic failures into ValidationResult.
- [ ] Run SpecValidator tests.
- [ ] Commit: `feat: validate semantic spec conformance`.

### Task 5: Effective application model and deterministic composition

**Files:**
- Create: `src/Apps/EffectiveApplication.php`
- Create: `src/Apps/ApplicationComposer.php`
- Modify: `src/Scrolls/Types/App.php` only for small App-specific accessors if they reduce duplication.
- Test: `tests/Apps/ApplicationComposerTest.php`

**Interfaces:**
- Consumes: `ScrollCodex`, App URI or App Scroll.
- Produces: `ApplicationComposer::compose(string|App $app): EffectiveApplication`.
- Produces immutable EffectiveApplication identity, effective data, Spec URI, inheritance chain, and provenance.

- [ ] Test standalone App.
- [ ] Test one and multiple inheritance levels.
- [ ] Test scalar child precedence.
- [ ] Test recursive map merge.
- [ ] Test ordered URI collection merge/deduplication.
- [ ] Test explicit child Spec precedence and omitted child Spec inheritance/default.
- [ ] Test missing parent and full-path cycle diagnostics.
- [ ] Implement recursive composition with no mutation of source Scrolls.
- [ ] Run ApplicationComposer tests.
- [ ] Commit: `feat: compose effective applications`.

### Task 6: Effective App validation and hydration boundary

**Files:**
- Create: `src/Apps/ApplicationResolver.php`
- Test: `tests/Apps/ApplicationResolverTest.php`

**Interfaces:**
- Consumes: `ApplicationComposer`, `ScrollCodex`, default App Spec URI.
- Produces: `ApplicationResolver::resolve(string|App $app): EffectiveApplication` after successful Spec conformance.

- [ ] Test default `spec://shinobi/app` selection when no Spec is declared/inherited.
- [ ] Test explicit Spec selection.
- [ ] Test validation occurs after composition by satisfying a requirement only from a parent capability.
- [ ] Test missing Spec and incompatible Spec subject.
- [ ] Test config/schema/capability references are resolvable and correctly typed.
- [ ] Implement resolver: compose, resolve Spec, invoke Spec on effective App representation, throw a diagnostic conformance exception on invalid result, return EffectiveApplication.
- [ ] Run ApplicationResolver tests.
- [ ] Commit: `feat: resolve validated applications`.

### Task 7: App and Spec structural resources

**Files:**
- Create or place according to existing package/source ownership conventions: App Schema resource and baseline App Spec resource.
- Modify package manifests/registry only if current package-owned Scroll discovery requires declaration.
- Test: `tests/Apps/ApplicationContractIntegrationTest.php`

**Interfaces:**
- Produces baseline resources equivalent to `schema://shinobi/app` and `spec://shinobi/app` in the correct source boundary.
- If Codejitsu must not own Shinobi-named resources, produce only generic fixture/baseline contracts in Codejitsu and document the exact Shinobi resource required for M2.

- [ ] Inspect current package ownership rules before choosing resource location.
- [ ] Add structural App Schema covering M1 fields without over-constraining future metadata.
- [ ] Add baseline App Spec requiring only contracts Codejitsu can prove in M1 fixtures.
- [ ] Add integration fixture with parent App, child App, Config, Schema and Capability Scrolls.
- [ ] Resolve the child end-to-end into EffectiveApplication.
- [ ] Commit: `feat: add app conformance resources`.

### Task 8: CLI authoring and inspection

**Files:**
- Modify: `src/Commands/Make.php` only where templates/default payloads require type-specific behavior.
- Modify/create command handlers following the existing command Scroll/Console pattern discovered in `src/Commands` and bundled command resources.
- Test: `tests/Commands/MakeTest.php`
- Create: `tests/Commands/AppCommandTest.php`
- Create: `tests/Commands/SpecCommandTest.php`

**Interfaces:**
- Produces practical CLI equivalents of `make:spec`, `make:app`, `spec:list`, `app:list`, `spec:validate`, `app:validate`, `app:show`.
- `app:show` consumes ApplicationResolver and prints identity, Spec, inheritance chain, effective references and conformance status.

- [ ] Inspect current command registration resources and preserve their naming convention.
- [ ] Extend `make:scroll`/aliases rather than duplicating scaffolding.
- [ ] Add list commands as typed Codex queries.
- [ ] Add Spec validation command.
- [ ] Add App validation command.
- [ ] Add App show command with deterministic output.
- [ ] Run command-focused tests.
- [ ] Commit: `feat: add app and spec cli workflow`.

### Task 9: Context synchronization and concentrated verification

**Files:**
- Modify: `.context/current-state.ctx`
- Modify: `.context/architecture/app-spec-composition.ctx` only if implementation forced a documented adjustment.
- Modify: `.context/todo.ctx` / roadmap only where M1 status changes.

**Interfaces:**
- Produces: repository context accurately distinguishing implemented M1 behavior from M2 plans.

- [ ] Run the complete default suite once the vertical slice is assembled: `composer test`.
- [ ] Fix regressions and rerun focused failures until green.
- [ ] Run `composer check`.
- [ ] Run `composer test:installation`.
- [ ] Verify CLI manually against the integration fixture: create/list/validate/show.
- [ ] Update current-state with actual test/assertion counts and implemented commands.
- [ ] Scan context for claims contradicted by implementation and correct only affected documents.
- [ ] Commit: `docs: record m1 app composition state`.

### Task 10: Whole-branch review and handoff to M2

**Files:**
- No product files unless review finds a defect.

**Interfaces:**
- Produces: reviewed M1 branch ready to merge and a precise M2 input contract.

- [ ] Review branch diff against ADR 0009 and the architecture spec.
- [ ] Verify no security, runtime boot, dependency installation, provider selection, or unrelated refactor leaked into M1.
- [ ] Verify EffectiveApplication is sufficient for Shinobi to consume without reading raw filesystem Scroll files directly.
- [ ] Run final release gates after any review fixes.
- [ ] Summarize the EffectiveApplication contract that M2 receives.
