# Codejitsu AI Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build an independently testable `codejitsu/ai` package that runs Spark Scrolls in Vessel Scrolls through Neuron AI, composes Skills and Context, exposes policy-controlled Tools/Toolsets, and supports one-shot and interactive terminal use.

**Architecture:** Codejitsu owns immutable definitions, prompt assembly, runtime requests/responses, Tool policy, and the Vessel runner. Neuron is isolated behind `AiRuntime`; its tools adapt Codejitsu Tool Scrolls backed by Capabilities. The existing Context package gains a precise atomic managed-section update used by an approval-gated Context Toolset.

**Tech Stack:** PHP 8.4+, Codejitsu Scroll/Codex/Capability packages, Symfony Console 8.1, Nette Neon, Opis JSON Schema, Neuron AI `^3.16`, PHPUnit 13.

**Spec:** `docs/superpowers/specs/2026-09-01-ai-foundation-design.md`

## Global Constraints

- Keep `codejitsu/ai` cataloged but absent from the root `require` map throughout implementation.
- Install and test AI dependencies from `packages/ai`; do not update the root lock file to install the package.
- No Neuron class may appear in public Codejitsu contracts, Scroll classes, commands, or the TUI.
- Credentials are environment-variable names or configuration references, never Scroll values.
- Skill templates only substitute exact `{{name}}` variables; no expressions, filters, traversal, PHP, or shell evaluation.
- Consequential Tools default to denied; TUI approval is per call and `ai:run` only allows names explicitly passed with `--approve-tool`.
- Context writes may replace one existing managed section in one allowed Context Scroll and must be locked, atomic, and validated.
- No MCP, orchestration, RAG, vector storage, persistent chat history, streaming, or web UI.
- Use TDD for every behavior and make one coherent commit per task.

---

### Task 1: Register AI Scroll Types and Package Dependencies

**Files:**
- Modify: `packages/ai/composer.json`
- Modify: `packages/ai/codejitsu.package`
- Modify: `.gitignore`
- Create: `packages/ai/src/Scrolls/Spark.php`
- Create: `packages/ai/src/Scrolls/Vessel.php`
- Create: `packages/ai/src/Scrolls/Tool.php`
- Create: `packages/ai/src/Scrolls/Toolset.php`
- Create: `packages/ai/tests/Scrolls/TypeRegistrationTest.php`
- Create: `packages/ai/phpunit.xml.dist`
- Create: `packages/ai/tests/bootstrap.php`

**Interfaces:**
- Produces: package-owned Scroll schemes `spark://`, `vessel://`, `tool://`, and `toolset://`.
- Produces: PSR-4 roots `Codejitsu\Ai\` and `Codejitsu\Ai\Tests\`.

- [ ] **Step 1: Add the failing registration test**

```php
public function testPackageRegistersItsScrollTypes(): void
{
    $root = dirname(__DIR__, 2);
    $compiled = (new PackageCompiler())->compile([
        new InstalledPackage('codejitsu/ai', '0.1.0', $root, $root . '/codejitsu.package'),
    ]);
    $types = $compiled['packages'][0]['types'];

    self::assertSame(Spark::class, $types['spark']['class']);
    self::assertSame('spark://', $types['spark']['scheme']);
    self::assertSame(Vessel::class, $types['vessel']['class']);
    self::assertSame(Tool::class, $types['tool']['class']);
    self::assertSame(Toolset::class, $types['toolset']['class']);
}
```

- [ ] **Step 2: Run the package test and verify failure**

Run: `cd packages/ai && composer install --no-interaction && ../../lib/bin/phpunit tests/Scrolls/TypeRegistrationTest.php`

Expected: FAIL because the AI type classes and manifest registrations do not exist.

- [ ] **Step 3: Declare exact dependencies and test bootstrap**

Set `packages/ai/composer.json` requirements to PHP `>=8.4`, Codejitsu core,
scrolls, codex, schema, console, context at `self.version`, and
`neuron-core/neuron-ai: ^3.16`. Add PHPUnit `^13.3` in `require-dev`, a path
repository for `../*`, `vendor-dir: ../../lib/ai`, and the package PHPUnit
script. The bootstrap must require
`dirname(__DIR__, 3) . '/lib/ai/autoload.php'`.
Ignore `packages/*/composer.lock`; Codejitsu packages are libraries and the
package-local lock is only generated while testing.

- [ ] **Step 4: Implement minimal Scroll classes and registrations**

Each class extends `Codejitsu\Scrolls\Scroll` and sets its string `TYPE`, for
example:

```php
final class Spark extends Scroll
{
    public const string TYPE = 'spark';
}
```

Register all four types and project sources in `codejitsu.package`, including
plural names, extensions, schemes, classes, and Neon codecs. Declare provided
capabilities `ai-runtime`, `spark-agents`, and `ai-tools`; require the actual
package capabilities used by the dependency set.

- [ ] **Step 5: Run focused tests and manifest validation**

Run: `cd packages/ai && ../../lib/ai/bin/phpunit tests/Scrolls/TypeRegistrationTest.php && composer validate --strict`

Expected: PASS and a valid package manifest.

- [ ] **Step 6: Commit**

```bash
git add .gitignore packages/ai
git commit -m "feat(ai): register AI scroll types"
```

### Task 2: Validate Immutable Spark, Vessel, Skill, Tool, and Toolset Definitions

**Files:**
- Create: `packages/ai/src/Definitions/SparkDefinition.php`
- Create: `packages/ai/src/Definitions/VesselDefinition.php`
- Create: `packages/ai/src/Definitions/SkillDefinition.php`
- Create: `packages/ai/src/Definitions/ToolDefinition.php`
- Create: `packages/ai/src/Definitions/ToolsetDefinition.php`
- Create: `packages/ai/src/Definitions/DefinitionLoader.php`
- Create: `packages/ai/src/Exceptions/DefinitionException.php`
- Create: `packages/ai/tests/Definitions/DefinitionLoaderTest.php`

**Interfaces:**
- Consumes: `ScrollCodex::resolve(string $uri): ?Scroll` and the AI Scroll classes from Task 1.
- Produces: `DefinitionLoader::__construct(ScrollCodex $codex)`.
- Produces: `spark(string $reference): SparkDefinition`, `vessel(string $reference): VesselDefinition`, `skill(string $reference): SkillDefinition`, `tool(string $reference): ToolDefinition`, `toolset(string $reference): ToolsetDefinition`.
- Produces: readonly definitions whose list fields are normalized ordered lists of non-empty URI strings.

- [ ] **Step 1: Write failing definition tests**

Cover valid hydration plus exact failures for missing required fields, duplicate
references, defaults missing from allowed lists, invalid `inputSchema`,
`maxRuns < 1`, non-boolean `consequential`, and ambiguous names.

```php
public function testSparkDefaultsMustBeAllowed(): void
{
    $this->expectExceptionMessage('Spark [architect] default Skill [review] is not allowed.');
    SparkDefinition::fromArray([
        'name' => 'architect', 'instructions' => 'Review code.',
        'skills' => ['skill://review'], 'allowedSkills' => ['skill://write'],
    ]);
}
```

- [ ] **Step 2: Verify the tests fail**

Run: `cd packages/ai && ../../lib/ai/bin/phpunit tests/Definitions/DefinitionLoaderTest.php`

Expected: FAIL because definition classes are missing.

- [ ] **Step 3: Implement definitions with explicit constructors**

Use readonly classes and named properties. `SparkDefinition` must expose
`name`, `instructions`, `description`, `model`, `skills`, `allowedSkills`,
`contexts`, `capabilities`, `tools`, `allowedTools`, `toolsets`, and
`allowedToolsets`. `VesselDefinition` must expose the matching runtime, default
Spark, allowed Sparks, provider config references, limits, session memory, and
allowlists described in the spec.

- [ ] **Step 4: Implement type-safe resolution**

`DefinitionLoader` resolves a URI or unique name, asserts the resolved Scroll
type, converts attributes into the matching definition, and includes the
reference in every exception. It must not resolve environment secrets.

- [ ] **Step 5: Run focused tests**

Run: `cd packages/ai && ../../lib/ai/bin/phpunit tests/Definitions`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/ai/src/Definitions packages/ai/src/Exceptions packages/ai/tests/Definitions
git commit -m "feat(ai): validate agent definitions"
```

### Task 3: Render Skills and Assemble Deterministic Prompts

**Files:**
- Create: `packages/ai/src/Prompt/SkillRenderer.php`
- Create: `packages/ai/src/Prompt/PromptAssembler.php`
- Create: `packages/ai/src/Prompt/AssembledPrompt.php`
- Create: `packages/ai/tests/Prompt/SkillRendererTest.php`
- Create: `packages/ai/tests/Prompt/PromptAssemblerTest.php`

**Interfaces:**
- Consumes: definitions from Task 2 and a callable `context(string $reference): string`.
- Produces: `SkillRenderer::render(SkillDefinition $skill, array $inputs): string`.
- Produces: `PromptAssembler::assemble(SparkDefinition $spark, array $skills, array $skillInputs, array $contexts, string $userInput): AssembledPrompt`.
- Produces: `AssembledPrompt` readonly properties `instructions`, `context`, `userInput`, and ordered reference metadata.

- [ ] **Step 1: Write failing safe-template tests**

```php
#[DataProvider('invalidTemplates')]
public function testItRejectsExecutableOrUnknownTemplates(string $template): void
{
    $this->expectException(DefinitionException::class);
    $this->renderer->render($this->skill($template), ['subject' => 'core']);
}

public static function invalidTemplates(): array
{
    return [['{{subject|raw}}'], ['{{subject.name}}'], ['{{php()}}'], ['{{unknown}}']];
}
```

Also assert required inputs, defaults, unknown supplied inputs, scalar type
validation, repeated variables, and literal preservation.

- [ ] **Step 2: Verify focused failure**

Run: `cd packages/ai && ../../lib/ai/bin/phpunit tests/Prompt/SkillRendererTest.php tests/Prompt/PromptAssemblerTest.php`

Expected: FAIL because the prompt classes do not exist.

- [ ] **Step 3: Implement exact substitution**

Match only `/{{\s*([a-z][a-z0-9_-]*)\s*}}/i`; compare discovered names to
declared inputs; reject any remaining `{{` or `}}`; normalize substituted
scalar values to strings without evaluating them.

- [ ] **Step 4: Implement ordered assembly**

Assemble Spark instructions, rendered Skills in declared order, Toolset
guidelines in declared order, and Context contents in declared order. Preserve
user input as a separate field rather than interpolating it into instructions.

- [ ] **Step 5: Run focused tests**

Run: `cd packages/ai && ../../lib/ai/bin/phpunit tests/Prompt`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/ai/src/Prompt packages/ai/tests/Prompt
git commit -m "feat(ai): compose skills and context prompts"
```

### Task 4: Add Runtime-Neutral Tool Policy and Capability Execution

**Files:**
- Create: `packages/ai/src/Tools/ToolRegistry.php`
- Create: `packages/ai/src/Tools/ToolCall.php`
- Create: `packages/ai/src/Tools/ToolResult.php`
- Create: `packages/ai/src/Tools/ToolApproval.php`
- Create: `packages/ai/src/Tools/DenyConsequentialTools.php`
- Create: `packages/ai/src/Tools/AllowNamedTools.php`
- Create: `packages/ai/src/Tools/InteractiveToolApproval.php`
- Create: `packages/ai/src/Tools/ToolPolicy.php`
- Create: `packages/ai/tests/Tools/ToolRegistryTest.php`
- Create: `packages/ai/tests/Tools/ToolPolicyTest.php`

**Interfaces:**
- Consumes: `ToolDefinition`, `ToolsetDefinition`, Capability Scroll execution, and Opis JSON Schema.
- Produces: `ToolApproval::approve(ToolCall $call): bool`.
- Produces: `ToolPolicy::resolve(SparkDefinition $spark, VesselDefinition $vessel, array $requestedTools, array $requestedToolsets): array` returning ordered `ToolDefinition` values.
- Produces: `ToolRegistry::execute(ToolDefinition $tool, array $arguments, ToolApproval $approval): ToolResult`.

- [ ] **Step 1: Write failing policy and execution tests**

Assert Vessel allowlists narrow Spark allowlists, Toolsets preserve order and
deduplicate by URI, denied consequential Tools never execute, allowed named
Tools execute once, `maxRuns` is enforced per request, and invalid arguments
fail schema validation before Capability execution.

```php
public function testConsequentialToolIsDeniedBeforeCapabilityRuns(): void
{
    $called = false;
    $registry = $this->registryWithCapability(function () use (&$called): void { $called = true; });

    $this->expectExceptionMessage('Tool [context.update-section] was not approved.');
    try { $registry->execute($this->tool(consequential: true), ['name' => 'state'], new DenyConsequentialTools()); }
    finally { self::assertFalse($called); }
}
```

- [ ] **Step 2: Verify failure**

Run: `cd packages/ai && ../../lib/ai/bin/phpunit tests/Tools`

Expected: FAIL because Tool policy classes do not exist.

- [ ] **Step 3: Implement policy resolution and approvals**

Resolve defaults plus runtime selections, require each selection in both
effective allowlists, expand Toolsets, deduplicate without sorting, and reject
any backing Capability outside the effective capability intersection.

- [ ] **Step 4: Implement validated Capability delegation**

Validate arguments against `inputSchema`, ask approval immediately before
execution, track calls by Tool URI, invoke the bound Capability with an
`ExecutionContext` containing validated arguments, and normalize scalar/array
results into `ToolResult` without exposing executable objects.

- [ ] **Step 5: Run focused tests**

Run: `cd packages/ai && ../../lib/ai/bin/phpunit tests/Tools`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/ai/src/Tools packages/ai/tests/Tools
git commit -m "feat(ai): add policy-controlled tools"
```

### Task 5: Add Atomic Context Memory Tools

**Files:**
- Modify: `src/Context/ContextMemory.php`
- Create: `tests/Context/ContextManagedSectionTest.php`
- Create: `packages/ai/src/Tools/Context/ContextListTool.php`
- Create: `packages/ai/src/Tools/Context/ContextSearchTool.php`
- Create: `packages/ai/src/Tools/Context/ContextShowTool.php`
- Create: `packages/ai/src/Tools/Context/ContextUpdateSectionTool.php`
- Create: `packages/ai/resources/tools/context-list.tool`
- Create: `packages/ai/resources/tools/context-search.tool`
- Create: `packages/ai/resources/tools/context-show.tool`
- Create: `packages/ai/resources/tools/context-update-section.tool`
- Create: `packages/ai/resources/toolsets/context.toolset`
- Create: `packages/ai/tests/Tools/Context/ContextToolsetTest.php`

**Interfaces:**
- Produces: `ContextMemory::updateSection(string $identifier, string $section, string $content): void`.
- Consumes: `ContextMemory::list()`, `search()`, `show()`, and `updateSection()`.
- Produces: Context Tool handlers invokable with validated argument arrays.

- [ ] **Step 1: Write failing managed-section tests**

```php
public function testItOnlyUpdatesOneExistingManagedSection(): void
{
    $memory = $this->memoryWith('state', "# State\n\n<!-- codejitsu:managed agent:start -->\nold\n<!-- codejitsu:managed agent:end -->\n");
    $memory->updateSection('state', 'agent', "new\nvalue");
    self::assertStringContainsString("agent:start -->\nnew\nvalue\n<!--", $memory->show('state'));
}
```

Also assert rejection of unknown Contexts, invalid section names, absent or
duplicate markers, paths, empty content, and a failed validation/write that
leaves the original byte-for-byte unchanged.

- [ ] **Step 2: Verify core test failure**

Run: `lib/bin/phpunit tests/Context/ContextManagedSectionTest.php`

Expected: FAIL because `updateSection()` does not exist.

- [ ] **Step 3: Implement locked atomic replacement**

Resolve the identifier through the codex, require its indexed source to be the
project Context source, and use the index entry's canonical `locator` rather
than accepting a caller path. Acquire an exclusive sibling lock file, replace
exactly one marker pair, validate the candidate with the Context codec/check
rules, write a sibling temporary file, and atomically rename it over the
original while retaining the sibling lock. Clean temporary and lock files on
every exit.

- [ ] **Step 4: Run the core Context suite**

Run: `lib/bin/phpunit tests/Context`

Expected: PASS.

- [ ] **Step 5: Add failing Context Toolset tests and implement handlers**

Test that list/search/show are non-consequential, update is consequential, every
operation is restricted to the active allowed Context URI set, and update calls
the precise `updateSection()` method. Implement Tool Scroll resources with
closed object schemas (`additionalProperties: false`) and register their source
in the AI manifest.

- [ ] **Step 6: Run AI Context Tool tests**

Run: `cd packages/ai && ../../lib/ai/bin/phpunit tests/Tools/Context`

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add src/Context tests/Context packages/ai/src/Tools/Context packages/ai/resources packages/ai/tests/Tools/Context
git commit -m "feat(ai): add controlled context memory tools"
```

### Task 6: Build the Vessel Runner and Runtime Contracts

**Files:**
- Create: `packages/ai/src/Runtime/AiRuntime.php`
- Create: `packages/ai/src/Runtime/AiRequest.php`
- Create: `packages/ai/src/Runtime/AiResponse.php`
- Create: `packages/ai/src/Runtime/ChatMessage.php`
- Create: `packages/ai/src/Runtime/RuntimeRegistry.php`
- Create: `packages/ai/src/Vessels/VesselRunner.php`
- Create: `packages/ai/src/Vessels/VesselSession.php`
- Create: `packages/ai/tests/Vessels/VesselRunnerTest.php`

**Interfaces:**
- Produces: `AiRuntime::run(AiRequest $request): AiResponse`.
- Produces: `RuntimeRegistry::register(string $name, AiRuntime $runtime): void` and `get(string $name): AiRuntime`.
- Produces: `VesselRunner::start(string $vessel, ?string $spark = null, array $skills = [], array $skillInputs = [], array $tools = [], array $toolsets = [], ?ToolApproval $approval = null): VesselSession`.
- Produces: `VesselSession::send(string $prompt): AiResponse`, `clear(): void`, and readonly active-definition accessors.

- [ ] **Step 1: Write failing end-to-end fake-runtime tests**

```php
public function testOneVesselRunsOneSparkThroughSubstitutableRuntime(): void
{
    $runtime = new RecordingRuntime(new AiResponse('review complete'));
    $session = $this->runner($runtime)->start('vessel://workbench');
    $response = $session->send('Review the package.');

    self::assertSame('review complete', $response->text);
    self::assertStringContainsString('You are the architect.', $runtime->lastRequest->instructions);
    self::assertSame('Review the package.', $runtime->lastRequest->messages[0]->content);
}
```

Also test Spark override eligibility, selected Skill eligibility, Context order,
Tool policy, model override precedence, unknown runtime, empty prompts,
multi-turn history, and `clear()`.

- [ ] **Step 2: Verify failure**

Run: `cd packages/ai && ../../lib/ai/bin/phpunit tests/Vessels`

Expected: FAIL because runtime and runner classes do not exist.

- [ ] **Step 3: Implement immutable runtime messages**

`AiRequest` carries instructions, ordered `ChatMessage` history, model,
resolved Tools, approval object, limits, and non-secret metadata. `AiResponse`
carries text, usage, and runtime metadata. Validate roles to `user`,
`assistant`, or `tool`.

- [ ] **Step 4: Implement session resolution and history**

At `start()`, load and validate Vessel/Spark/Skills/Contexts/Tools once, assemble
instructions, and obtain the runtime. At `send()`, append the user message,
invoke the runtime, append the assistant response only on success, and preserve
history after recoverable failures. `clear()` empties messages without changing
active definitions.

- [ ] **Step 5: Run focused tests**

Run: `cd packages/ai && ../../lib/ai/bin/phpunit tests/Vessels tests/Prompt tests/Tools`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/ai/src/Runtime packages/ai/src/Vessels packages/ai/tests/Vessels
git commit -m "feat(ai): run sparks through vessels"
```

### Task 7: Adapt Neuron Providers, Messages, Tools, and Toolsets

**Files:**
- Create: `packages/ai/src/Neuron/NeuronRuntime.php`
- Create: `packages/ai/src/Neuron/NeuronAgent.php`
- Create: `packages/ai/src/Neuron/ProviderFactory.php`
- Create: `packages/ai/src/Neuron/NeuronToolAdapter.php`
- Create: `packages/ai/src/Neuron/NeuronToolkitAdapter.php`
- Create: `packages/ai/tests/Neuron/NeuronRuntimeTest.php`
- Create: `packages/ai/tests/Neuron/ProviderFactoryTest.php`
- Create: `packages/ai/tests/Neuron/NeuronToolAdapterTest.php`

**Interfaces:**
- Consumes: Task 6 `AiRuntime`, Task 4 Tool registry/policy, and Neuron 3.16 public interfaces.
- Produces: `ProviderFactory::make(array $configuration): AIProviderInterface` inside the Neuron namespace only.
- Produces: `NeuronRuntime implements AiRuntime`.

- [ ] **Step 1: Inspect installed Neuron 3.16 signatures**

Run: `rg -n "interface ToolInterface|abstract class Agent|abstract class AbstractToolkit|interface AIProviderInterface" packages/ai/../../lib/ai/neuron-core/neuron-ai/src`

Expected: exact Neuron 3.16 namespaces and method signatures used by the adapter tests. Update adapter-private signatures to match; do not change Codejitsu public contracts.

- [ ] **Step 2: Write failing adapter tests without network calls**

Use a fake Neuron `AIProviderInterface` to record system instructions, messages,
tools, toolkit guidelines, and selected model. Invoke `NeuronToolAdapter`
directly to prove schema arguments cross into `ToolRegistry` and its result
returns to Neuron.

- [ ] **Step 3: Verify failure**

Run: `cd packages/ai && ../../lib/ai/bin/phpunit tests/Neuron`

Expected: FAIL because adapters do not exist.

- [ ] **Step 4: Implement the private Neuron bridge**

Create a request-scoped `NeuronAgent` configured from `AiRequest`; translate
messages both ways; return Codejitsu `AiResponse`. Convert Tools to
`ToolInterface`, Toolsets to toolkit-compatible collections with guidelines,
and route invocations back through Codejitsu approval and Capability execution.

- [ ] **Step 5: Implement one provider path and clear failures**

Support one provider configured by environment-variable references. Reject an
unknown provider, missing environment variable, empty model, or literal secret
field before constructing Neuron objects. Keep the factory extensible through a
map of named builders rather than a switch in the Vessel runner.

- [ ] **Step 6: Run adapter and package tests**

Run: `cd packages/ai && ../../lib/ai/bin/phpunit tests/Neuron tests/Vessels tests/Tools`

Expected: PASS with no outbound request.

- [ ] **Step 7: Commit**

```bash
git add packages/ai/src/Neuron packages/ai/tests/Neuron packages/ai/composer.json
git commit -m "feat(ai): add Neuron runtime adapter"
```

### Task 8: Add Makers and Resource Inspection Commands

**Files:**
- Create: `packages/ai/src/Scaffolding/AiScaffolder.php`
- Create: `packages/ai/src/Commands/MakeAi.php`
- Create: `packages/ai/src/Commands/AiResources.php`
- Create: `packages/ai/resources/commands/make-spark.cmd`
- Create: `packages/ai/resources/commands/make-vessel.cmd`
- Create: `packages/ai/resources/commands/make-skill.cmd`
- Create: `packages/ai/resources/commands/make-tool.cmd`
- Create: `packages/ai/resources/commands/make-toolset.cmd`
- Create: `packages/ai/resources/commands/spark.cmd`
- Create: `packages/ai/resources/commands/vessel.cmd`
- Create: `packages/ai/resources/commands/skill.cmd`
- Create: `packages/ai/resources/commands/tool.cmd`
- Create: `packages/ai/resources/commands/toolset.cmd`
- Create: `packages/ai/resources/capabilities/*.capability`
- Create: `packages/ai/resources/schemas/*.schema`
- Create: `packages/ai/tests/Scaffolding/AiScaffolderTest.php`
- Create: `packages/ai/tests/Commands/AiResourcesTest.php`

**Interfaces:**
- Produces: `AiScaffolder::spark()`, `vessel()`, `skill()`, `tool()`, and `toolset()` returning created absolute paths.
- Produces: `make:spark`, `make:vessel`, `make:skill`, `make:tool`, `make:toolset`, plus list/show/render commands from the spec.
- Consumes: existing `Questioner`, registered `TypeDefinition`, Neon codec, and `DefinitionLoader`.

- [ ] **Step 1: Write failing scaffolder tests**

Assert noninteractive and interactive creation, normalized nested names,
minimal valid payloads, registered directories/extensions, refusal to overwrite,
and no Composer/root package mutation.

```php
public function testMakeSparkCreatesMinimalValidScrollWithoutInstallingPackage(): void
{
    $path = $this->scaffolder->spark('architect', 'You review architecture.');
    self::assertSame($this->root . '/scrolls/sparks/architect.spark', $path);
    self::assertFileDoesNotExist($this->root . '/composer.lock');
}
```

- [ ] **Step 2: Verify failure**

Run: `cd packages/ai && ../../lib/ai/bin/phpunit tests/Scaffolding tests/Commands/AiResourcesTest.php`

Expected: FAIL because maker and resource commands do not exist.

- [ ] **Step 3: Implement the shared scaffolder and maker handlers**

Use one path/name validation routine and one atomic Neon writer. Command handlers
parse supplied arguments; when the name is absent they use `Questioner` for the
fields defined in the spec. Never use an editor for structured payloads.
Register each maker as a direct command Scroll whose `name` contains the full
`make:<type>` name. Do not register another `make` namespace Scroll: the current
console does not merge namespace children across package sources.

- [ ] **Step 4: Implement list/show/render handlers and Scroll resources**

List commands query by exact type and sort by name. Show commands resolve a
unique name or URI and encode normalized non-secret definition data.
`skill:render` accepts repeatable `--input=name=value` tokens, rejects duplicate
keys, and prompts for missing required values only in an interactive terminal.

- [ ] **Step 5: Run command tests**

Run: `cd packages/ai && ../../lib/ai/bin/phpunit tests/Scaffolding tests/Commands`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/ai/src/Scaffolding packages/ai/src/Commands packages/ai/resources packages/ai/tests/Scaffolding packages/ai/tests/Commands
git commit -m "feat(ai): add AI makers and resource commands"
```

### Task 9: Add One-Shot Execution and Interactive Vessel TUI

**Files:**
- Create: `packages/ai/src/Commands/Ai.php`
- Create: `packages/ai/src/Console/AiTui.php`
- Create: `packages/ai/src/Console/ConversationIO.php`
- Create: `packages/ai/src/Console/TerminalConversationIO.php`
- Create: `packages/ai/resources/commands/ai.cmd`
- Create: `packages/ai/resources/capabilities/ai-run.capability`
- Create: `packages/ai/resources/capabilities/ai-tui.capability`
- Create: `packages/ai/resources/schemas/ai-run.schema`
- Create: `packages/ai/tests/Commands/AiTest.php`
- Create: `packages/ai/tests/Console/AiTuiTest.php`

**Interfaces:**
- Consumes: `VesselRunner`, `ToolApproval`, and existing terminal selection conventions.
- Produces: `Ai::run(ExecutionContext $context): string|int` and `Ai::tui(ExecutionContext $context): string|int`.
- Produces: `AiTui::run(ConversationIO $io): int`.
- Produces: `ConversationIO::ask()`, `select()`, `write()`, and `confirmTool(ToolCall $call): bool`.

- [ ] **Step 1: Write failing one-shot command tests**

Assert Vessel/prompt parsing, repeatable `--approve-tool=name`, normal response
output, non-zero failure, and default denial of Context update. Inject a fake
runner; never construct a live provider.

- [ ] **Step 2: Write failing scripted TUI tests**

Use a fake `ConversationIO` transcript to cover Vessel/Spark selection,
multi-turn messages, `/clear`, `/new`, `/context`, `/skills`, `/skill add`,
`/skill remove`, `/tools`, `/spark`, `/vessel`, `/help`, recoverable runtime
errors, approval yes/no, and `/exit`.

- [ ] **Step 3: Verify failure**

Run: `cd packages/ai && ../../lib/ai/bin/phpunit tests/Commands/AiTest.php tests/Console/AiTuiTest.php`

Expected: FAIL because execution commands and TUI do not exist.

- [ ] **Step 4: Implement one-shot execution**

Build `AllowNamedTools` only from parsed option names, start one session, send
one prompt, print response text, and map definition/configuration/policy/runtime
exceptions to concise stderr plus exit code `1`.

- [ ] **Step 5: Implement the thin TUI state machine**

Keep parsing and rendering in `AiTui`; keep all agent behavior in
`VesselSession`. Slash commands either inspect active definitions, clear
history, or restart the session with validated selections. Catch recoverable
errors inside the loop. `InteractiveToolApproval` calls
`ConversationIO::confirmTool()` with the Tool name and validated arguments.

- [ ] **Step 6: Run focused tests**

Run: `cd packages/ai && ../../lib/ai/bin/phpunit tests/Commands tests/Console tests/Vessels`

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add packages/ai/src/Commands packages/ai/src/Console packages/ai/resources packages/ai/tests/Commands packages/ai/tests/Console
git commit -m "feat(ai): add vessel CLI and TUI"
```

### Task 10: Document, Verify, and Prepare the Uninstalled Package

**Files:**
- Modify: `packages/ai/README.md` (create if absent)
- Modify: `README.md`
- Modify: `.context/current-state.ctx`
- Modify: `.context/roadmap/current.ctx`
- Modify: `.context/todo.ctx`
- Modify: `packages/ai/codejitsu.package`
- Modify: `packages/ai/composer.json`

**Interfaces:**
- Produces: documented install/configuration/dogfooding workflow without adding AI to root requirements.

- [ ] **Step 1: Write package documentation**

Document the Context/Skill/Spark/Vessel/Tool/Toolset distinctions, Scroll
examples without secrets, maker commands, one-shot use, TUI commands, provider
environment references, Tool approval behavior, Context managed-section
restrictions, and the deferred MCP boundary.

- [ ] **Step 2: Update project status and command documentation**

Add the cataloged-uninstalled AI package and its commands to the root README.
Record completed foundation behavior and remaining dogfooding/MCP/UI work in
the three Context Scrolls without claiming the package is installed.

- [ ] **Step 3: Verify the package independently**

Run:

```bash
cd packages/ai
composer validate --strict
composer install --no-interaction
../../lib/ai/bin/phpunit
```

Expected: all AI tests pass and no network/model call occurs.

- [ ] **Step 4: Verify Context and full root regression suites**

Run:

```bash
cd ../..
composer test
composer check
composer audit --no-interaction
composer test:installation
```

Expected: all tests/checks pass and no advisories are reported.

- [ ] **Step 5: Verify package isolation and manifests**

Run:

```bash
composer show codejitsu/ai --no-interaction
php bin/codejitsu pkg:list
php bin/codejitsu pkg:cache:rebuild
git diff --check
git status --short
```

Expected: `composer show` reports AI is not installed; `pkg:list` reports the
catalog entry as uninstalled; the installed-package cache still contains only
the existing installed packages; diff check is clean; status only contains
intentional files.

- [ ] **Step 6: Commit documentation and verified package metadata**

```bash
git add packages/ai README.md .context
git commit -m "docs: document AI foundation"
```

- [ ] **Step 7: Review branch history and final diff**

Run: `git log --oneline main..HEAD && git diff --stat main...HEAD && git status --short --branch`

Expected: the design plus ten coherent implementation commits, a clean
worktree, and no root installation of `codejitsu/ai`.
