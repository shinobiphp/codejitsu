# Scroll Substrates Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make executable Scrolls runtime-agnostic and safely executable through PHP, Lua, JavaScript, and WebAssembly substrates, while completing the interactive `make:scroll` flow.

**Architecture:** `Capability` delegates executable source to a `SubstrateRegistry`, which resolves a substrate by explicit metadata or source detection and executes it with an `ExecutionContext` plus deny-by-default execution policy. Runtime-specific executors stay isolated behind the substrate contract; CLI creation uses the same registry and an injectable editor abstraction.

**Tech Stack:** PHP 8.4+, PHPUnit 13, LuaSandbox, V8Js, Wasmtime 48/WASI, Symfony Console, Nette NEON.

**Spec:** `docs/superpowers/specs/2026-08-25-scroll-substrates-design.md`

## Global Constraints

- PHP minimum remains `>=8.4`.
- Use `declare(strict_types=1)` in every PHP file.
- Substrate names are stable strings: `php`, `lua`, `javascript`, `wasm`.
- Explicit `substrate` wins over detection; `auto` falls through detection to the configured default `php`.
- Default execution policy denies filesystem, network, unrestricted environment, and process spawning.
- PHP must not use in-process `eval` as the production sandbox boundary.
- Optional runtime tests skip cleanly when their runtime is unavailable.
- `Capability` must not contain a substrate-specific `switch` after this work.
- Existing callable-target Scroll behavior remains supported.
- Preserve multiline source in NEON using the project's triple-quoted representation.

---

### Task 1: Define execution policy and expand execution context

**Files:**
- Create: `src/ExecutionPolicy.php`
- Modify: `src/ExecutionContext.php`
- Test: `tests/ExecutionContextTest.php`

**Interfaces:**
- Produces `Codejitsu\ExecutionPolicy` with immutable defaults for timeout, memory, filesystem roots, environment, and network/process permissions.
- `ExecutionContext` exposes `arguments`, optional `codex`, and `policy`.

- [ ] **Step 1: Write the failing policy/context tests**

```php
public function testDefaultPolicyDeniesExternalCapabilities(): void
{
    $policy = ExecutionPolicy::defaults();

    self::assertFalse($policy->allowNetwork);
    self::assertFalse($policy->allowProcess);
    self::assertSame([], $policy->filesystemRoots);
    self::assertSame([], $policy->environment);
}

public function testExecutionContextCarriesPolicy(): void
{
    $policy = ExecutionPolicy::defaults();
    $context = new ExecutionContext([], null, $policy);

    self::assertSame($policy, $context->policy);
}
```

- [ ] **Step 2: Run the focused test and verify it fails**

Run: `./bin/phpunit tests/ExecutionContextTest.php`
Expected: FAIL because `ExecutionPolicy` and the new context constructor do not exist yet.

- [ ] **Step 3: Implement the minimal immutable policy/context**

```php
final readonly class ExecutionPolicy
{
    public function __construct(
        public int $timeoutMilliseconds = 1000,
        public int $memoryBytes = 67108864,
        public array $filesystemRoots = [],
        public array $environment = [],
        public bool $allowNetwork = false,
        public bool $allowProcess = false,
    ) {}

    public static function defaults(): self
    {
        return new self();
    }
}
```

Add the policy as the third optional `ExecutionContext` constructor parameter and default it to `ExecutionPolicy::defaults()`.

- [ ] **Step 4: Run the focused test and verify it passes**

Run: `./bin/phpunit tests/ExecutionContextTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/ExecutionPolicy.php src/ExecutionContext.php tests/ExecutionContextTest.php
git commit -m "feat: add execution policy context"
```

### Task 2: Replace substrate detection with a registry-backed resolver

**Files:**
- Create: `src/SubstrateRegistry.php`
- Create: `src/Substrate/Resolver.php`
- Modify: `src/Substrate.php`
- Modify: `src/Substrate/Detector.php`
- Test: `tests/Substrate/SubstrateRegistryTest.php`
- Test: `tests/Substrate/DetectorTest.php`

**Interfaces:**
- `SubstrateRegistry::register(string $name, Substrate $substrate): void`
- `SubstrateRegistry::has(string $name): bool`
- `SubstrateRegistry::get(string $name): Substrate`
- `SubstrateRegistry::names(): array`
- `Substrate\Resolver::resolve(?string $requested, string $source): Substrate`

- [ ] **Step 1: Write registry and resolution tests**

```php
public function testRegistryStoresAndReturnsSubstrates(): void
{
    $registry = new SubstrateRegistry();
    $substrate = new FakeSubstrate();

    $registry->register('fake', $substrate);

    self::assertTrue($registry->has('fake'));
    self::assertSame($substrate, $registry->get('fake'));
    self::assertSame(['fake'], $registry->names());
}

public function testExplicitSubstrateWinsOverDetection(): void
{
    $resolver = new Resolver($registryWithPhpAndLua, new Detector());

    self::assertSame($lua, $resolver->resolve('lua', '<?php return 1;'));
}

public function testAutoDetectionFallsBackToPhp(): void
{
    $resolver = new Resolver($registryWithPhpAndLua, new Detector());

    self::assertSame($php, $resolver->resolve('auto', 'return 1;'));
}
```

- [ ] **Step 2: Run focused tests and verify failure**

Run: `./bin/phpunit tests/Substrate/DetectorTest.php tests/Substrate/SubstrateRegistryTest.php`
Expected: FAIL because the registry/resolver API is absent or incomplete.

- [ ] **Step 3: Implement registry and resolver**

Normalize names to lowercase, reject blank names, throw a clear `LogicException` for unknown substrates, and keep detection independent from executor implementations.

- [ ] **Step 4: Expand detector markers**

Support `<?php`, `/usr/bin/env lua`, `/usr/bin/env node`, `/usr/bin/env javascript`, `/usr/bin/env js`, `/usr/bin/env wasmtime`, plus direct `/usr/bin/...` forms. Map `node` and `js` to `javascript`, and `wasmtime` to `wasm`.

- [ ] **Step 5: Run focused tests and verify pass**

Run: `./bin/phpunit tests/Substrate/DetectorTest.php tests/Substrate/SubstrateRegistryTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add src/Substrate.php src/Substrate/Detector.php src/SubstrateRegistry.php src/Substrate/Resolver.php tests/Substrate/DetectorTest.php tests/Substrate/SubstrateRegistryTest.php
git commit -m "feat: add substrate registry and resolution"
```

### Task 3: Implement isolated PHP substrate

**Files:**
- Create: `src/Substrate/PhpRunner.php`
- Modify: `src/Substrate/Php.php`
- Test: `tests/Substrate/PhpTest.php`
- Create: `bin/substrate-php`

**Interfaces:**
- `Php` remains the `Substrate` implementation.
- `PhpRunner` executes a prepared source file in a separate PHP process and returns a structured result/error.

- [ ] **Step 1: Write tests for source execution and policy isolation**

```php
public function testExecutesPhpSourceWithContext(): void
{
    $result = (new Php())->execute('<?php return $context->arguments[0] ?? "missing";', new ExecutionContext(['shinobi']));

    self::assertSame('shinobi', $result);
}

public function testProcessRunnerUsesControlledEnvironment(): void
{
    $result = (new Php())->execute('<?php return getenv("SECRET") ?: "none";', new ExecutionContext([], null, new ExecutionPolicy(environment: [])));

    self::assertSame('none', $result);
}
```

- [ ] **Step 2: Run focused test and verify failure**

Run: `./bin/phpunit tests/Substrate/PhpTest.php`
Expected: FAIL while the executor still uses in-process evaluation.

- [ ] **Step 3: Implement the isolated runner**

Write the source and a tiny runner script into a unique temporary directory, pass only the allowed environment, execute PHP with `proc_open`, enforce the policy timeout, capture stdout/stderr/exit code, decode the serialized result, and delete the temporary directory in `finally`.

The runner must expose only the `ExecutionContext` data needed by scripts and must never pass the host application container, arbitrary environment, or unrestricted working directory.

- [ ] **Step 4: Preserve CLI/source semantics**

Strip a shebang and leading `<?php` only as required by the runner representation; generated source remains valid standalone PHP.

- [ ] **Step 5: Run focused tests**

Run: `./bin/phpunit tests/Substrate/PhpTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add src/Substrate/Php.php src/Substrate/PhpRunner.php bin/substrate-php tests/Substrate/PhpTest.php
git commit -m "feat: sandbox php substrate"
```

### Task 4: Implement LuaSandbox substrate

**Files:**
- Create: `src/Substrate/Lua.php`
- Test: `tests/Substrate/LuaTest.php`
- Modify: `composer.json` only if the extension is represented by an existing platform requirement mechanism.

**Interfaces:**
- `Lua implements Substrate`.

- [ ] **Step 1: Write Lua tests with an availability guard**

```php
public function testExecutesLuaSource(): void
{
    if (!extension_loaded('luasandbox')) {
        self::markTestSkipped('LuaSandbox extension is not installed.');
    }

    self::assertSame('shinobi', (new Lua())->execute('return "shinobi"', new ExecutionContext()));
}
```

- [ ] **Step 2: Run focused test and verify skipped/failing state**

Run: `./bin/phpunit tests/Substrate/LuaTest.php`
Expected: SKIPPED if LuaSandbox is unavailable; otherwise FAIL until implementation exists.

- [ ] **Step 3: Implement LuaSandbox execution**

Instantiate the sandbox, apply memory/time limits from `ExecutionPolicy`, expose only a minimal `context` representation, execute the source, and convert sandbox/runtime errors into a Codejitsu runtime exception without leaking host internals.

- [ ] **Step 4: Run focused test**

Run: `./bin/phpunit tests/Substrate/LuaTest.php`
Expected: PASS or clean SKIP when the extension is unavailable.

- [ ] **Step 5: Commit**

```bash
git add src/Substrate/Lua.php tests/Substrate/LuaTest.php composer.json
 git commit -m "feat: add lua substrate"
```

### Task 5: Implement V8Js JavaScript substrate

**Files:**
- Create: `src/Substrate/Javascript.php`
- Test: `tests/Substrate/JavascriptTest.php`

**Interfaces:**
- `Javascript implements Substrate`.

- [ ] **Step 1: Write guarded JavaScript execution test**

```php
public function testExecutesJavascriptSource(): void
{
    if (!class_exists('V8Js')) {
        self::markTestSkipped('V8Js extension is not installed.');
    }

    self::assertSame(3, (new Javascript())->execute('1 + 2', new ExecutionContext()));
}
```

- [ ] **Step 2: Run focused test and verify failure/skip**

Run: `./bin/phpunit tests/Substrate/JavascriptTest.php`
Expected: SKIP if V8Js is unavailable; otherwise FAIL before implementation.

- [ ] **Step 3: Implement V8 isolate execution**

Instantiate `V8Js`, expose a controlled context object, execute source, and map runtime exceptions to Codejitsu exceptions. Do not expose PHP objects, filesystem APIs, process APIs, or arbitrary globals to the JavaScript isolate.

- [ ] **Step 4: Run focused test**

Run: `./bin/phpunit tests/Substrate/JavascriptTest.php`
Expected: PASS or clean SKIP.

- [ ] **Step 5: Commit**

```bash
git add src/Substrate/Javascript.php tests/Substrate/JavascriptTest.php
git commit -m "feat: add javascript substrate"
```

### Task 6: Implement Wasmtime/WASI substrate

**Files:**
- Create: `src/Substrate/Wasm.php`
- Create: `src/Substrate/Wasmtime.php`
- Test: `tests/Substrate/WasmTest.php`

**Interfaces:**
- `Wasm implements Substrate`.
- `Wasmtime` owns process invocation and WASI argument/environment construction.

- [ ] **Step 1: Write a guarded Wasmtime test**

```php
public function testExecutesWasmModule(): void
{
    $wasmtime = trim((string) shell_exec('command -v wasmtime'));
    if ($wasmtime === '') {
        self::markTestSkipped('Wasmtime is not installed.');
    }

    $result = (new Wasm())->execute($knownMinimalWasmModule, new ExecutionContext());

    self::assertSame(3, $result);
}
```

- [ ] **Step 2: Run focused test and verify it fails/skip**

Run: `./bin/phpunit tests/Substrate/WasmTest.php`
Expected: SKIP if Wasmtime is absent; otherwise FAIL before implementation.

- [ ] **Step 3: Implement module execution**

Treat source as a WASM binary payload (base64/hex or file reference according to the existing Scroll representation), write it to a private temporary directory, invoke `wasmtime` with WASI configured from `ExecutionPolicy`, disable network capability, preopen only allowed filesystem roots, pass only allowed environment, enforce timeout, and capture stdout/stderr/exit status.

- [ ] **Step 4: Define the first stable inline representation**

Use a `sourceEncoding: base64` attribute for binary inline modules and reject ambiguous plain-text WASM source. This keeps NEON valid and avoids corrupting binary bytes.

- [ ] **Step 5: Run focused test**

Run: `./bin/phpunit tests/Substrate/WasmTest.php`
Expected: PASS or clean SKIP.

- [ ] **Step 6: Commit**

```bash
git add src/Substrate/Wasm.php src/Substrate/Wasmtime.php tests/Substrate/WasmTest.php
git commit -m "feat: add wasm substrate"
```

### Task 7: Wire Capability through the substrate registry

**Files:**
- Modify: `src/Scrolls/Types/Capability.php`
- Modify: `src/Boot.php` or the existing composition/bootstrap location
- Test: `tests/Scrolls/Types/CapabilityTest.php`

**Interfaces:**
- `Capability::execute(ExecutionContext $context)` delegates source execution to `Substrate\Resolver`/`SubstrateRegistry`.

- [ ] **Step 1: Write capability tests**

```php
public function testSourceCapabilityUsesRegistry(): void
{
    $capability = $this->capability(['substrate' => 'php', 'source' => '<?php return "hello";']);

    self::assertSame('hello', $capability->execute(new ExecutionContext()));
}

public function testTargetCapabilitiesRemainSupported(): void
{
    $capability = $this->capability(['target' => static fn (ExecutionContext $context): string => 'target']);

    self::assertSame('target', $capability->execute(new ExecutionContext()));
}
```

- [ ] **Step 2: Run focused tests and verify the source path exposes the current hard-coded limitation**

Run: `./bin/phpunit tests/Scrolls/Types/CapabilityTest.php`
Expected: FAIL for non-PHP substrate/registry injection cases.

- [ ] **Step 3: Inject the registry into bootstrap and remove the substrate switch**

`Capability` should resolve the requested substrate through the registry. The registry should be available from the execution context or codex/container without static global state.

- [ ] **Step 4: Register built-ins**

Register `php`, `lua`, `javascript`, and `wasm` in one composition root. Optional runtime executors can be registered only when their dependencies are available; requests for an unavailable runtime produce a clear unsupported/unavailable substrate exception.

- [ ] **Step 5: Run focused tests**

Run: `./bin/phpunit tests/Scrolls/Types/CapabilityTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add src/Scrolls/Types/Capability.php src/Boot.php tests/Scrolls/Types/CapabilityTest.php
git commit -m "feat: route capabilities through substrates"
```

### Task 8: Finish interactive `make:scroll`

**Files:**
- Modify: `src/Commands/Make.php`
- Create: `src/Console/Editor.php`
- Create: `src/Console/Questioner.php` or use the existing console abstraction if present
- Test: `tests/Commands/MakeTest.php`

**Interfaces:**
- `Editor::edit(string $initial = ''): string`
- `Questioner` supplies selectable type/substrate/name/version answers.
- `Make::scroll()` remains callable non-interactively with existing options.

- [ ] **Step 1: Write tests for interactive creation**

```php
public function testInteractiveCreationSelectsTypeSubstrateAndSource(): void
{
    $editor = new FakeEditor('<?php return "hello";');
    $questioner = new FakeQuestioner([
        'type' => 'capability',
        'substrate' => 'php',
        'name' => 'hello/world',
        'version' => null,
    ]);

    $path = Make::interactive($questioner, $editor, $registry);

    self::assertFileExists($path);
    self::assertStringContainsString('substrate: php', file_get_contents($path));
    self::assertStringContainsString('source: """', file_get_contents($path));
}
```

- [ ] **Step 2: Run focused test and verify failure**

Run: `./bin/phpunit tests/Commands/MakeTest.php`
Expected: FAIL because the interactive API/editor flow is not present.

- [ ] **Step 3: Implement interactive prompts**

Use the registered Scroll types for the type menu. For executable types, use the substrate registry names. Ask for name and version; when version is omitted, discover matching Scrolls and increment the patch version from the highest matching version, defaulting to `1.0.0`.

- [ ] **Step 4: Implement editor abstraction**

Use `$EDITOR` when available, otherwise `vi`/`nano` according to the existing CLI conventions. Write a temporary file, launch the editor through the existing process abstraction, read the final content, and remove the temp file. Tests use a fake editor and never launch an interactive process.

- [ ] **Step 5: Encode source as triple-quoted NEON**

Ensure multiline source is emitted using `source: """` and a matching closing delimiter, preserving source content exactly except for the expected terminal newline.

- [ ] **Step 6: Run focused tests**

Run: `./bin/phpunit tests/Commands/MakeTest.php`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add src/Commands/Make.php src/Console/Editor.php src/Console/Questioner.php tests/Commands/MakeTest.php
git commit -m "feat: add interactive scroll creation"
```

### Task 9: Improve CLI command discovery/help for substrates and interactive creation

**Files:**
- Modify: existing CLI command/usage renderer files identified from the current branch
- Modify: `scrolls/commands/make.cmd`
- Modify: `scrolls/commands/scroll.cmd`
- Test: existing CLI/command usage tests plus new focused usage test if required

**Interfaces:**
- Main usage displays command groups and their subcommands in a Symfony Console-like layout.
- `make:scroll --interactive` and/or bare `make:scroll` can enter the interactive creation flow according to the existing command syntax.

- [ ] **Step 1: Add failing usage assertions**

Assert that the main usage contains grouped commands and visible subcommands such as `make:scroll`, `scroll:run`, `scrolls:list`, and `scroll:seal`.

- [ ] **Step 2: Run focused CLI tests and verify failure**

Run: `composer test -- --filter Usage`
Expected: FAIL on missing grouped/subcommand output.

- [ ] **Step 3: Implement grouped/colorized usage output**

Use Symfony Console output styles already available in the project. Keep command discovery data-driven so new Scroll command resources appear without hand-editing a giant usage string.

- [ ] **Step 4: Run focused CLI tests**

Run: `composer test -- --filter Usage`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src scrolls/commands tests
 git commit -m "feat: improve cli command usage"
```

### Task 10: End-to-end substrate smoke tests and full regression

**Files:**
- Modify: `tests/SmokeTest.php` or the current smoke harness
- Create: `tests/Fixtures/wasm/hello.wasm` if fixtures are stored in-repo
- Create: `tests/Fixtures/scripts/...` only where needed

- [ ] **Step 1: Add end-to-end PHP Scroll execution**

Create a temporary capability Scroll with triple-quoted PHP source and invoke the real CLI execution path. Assert the returned output.

- [ ] **Step 2: Add end-to-end WASM execution**

When `wasmtime` exists, execute a minimal fixture module through `scroll:run`. Skip only the runtime-specific assertion when Wasmtime is absent.

- [ ] **Step 3: Add Lua and JavaScript smoke coverage**

Run equivalent source Scrolls when the corresponding PHP extensions are installed; otherwise cleanly skip.

- [ ] **Step 4: Run the complete suite**

Run: `composer test`
Expected: all tests PASS, with optional-runtime tests reported only as SKIPPED when dependencies are unavailable.

- [ ] **Step 5: Run CLI smoke checks**

Run:

```bash
./bin/codejitsu
./bin/codejitsu make:scroll capability://hello/world
./bin/codejitsu scroll:run capability://hello/world world
./bin/codejitsu scrolls:list
```

Expected: grouped help is readable, interactive/noninteractive creation works, the generated capability executes, and listing discovers it.

- [ ] **Step 6: Commit**

```bash
git add tests
 git commit -m "test: cover scroll substrate execution"
```

### Task 11: Verify the branch and prepare PR

**Files:**
- No source changes unless verification exposes a defect.

- [ ] **Step 1: Check repository state**

Run: `git status --short` and confirm only intentional changes exist.

- [ ] **Step 2: Run full verification**

Run: `composer test`.

- [ ] **Step 3: Verify installed runtimes**

Run:

```bash
php -m | grep -Ei 'luasandbox|v8js'
wasmtime --version
```

Record runtime availability; do not make the core test suite depend on optional extensions being present.

- [ ] **Step 4: Push the feature branch**

```bash
git push -u origin feat/scroll-run-runtime
```

- [ ] **Step 5: Open the PR**

Use the repository's GitHub workflow to create a PR from `feat/scroll-run-runtime` into `main` with the substrate design and test summary.
