# ScrollCodex Discovery and Cache Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move complete Scroll discovery and cache management into `ScrollCodex`, simplify Boot and CLI around the shared Codex, and make help/execution fully driven by Command Scrolls.

**Architecture:** `ScrollCodex` owns resource discovery, source manifesting, cache load/rebuild/invalidate, and typed URI resolution. Boot only supplies the root and receives a loaded Codex; CLI reads Command Scrolls from that Codex and renders generated help for no-argument, `--help`, `-h`, and `help` invocations.

**Tech Stack:** PHP 8.4+, PHPUnit 13, Nette NEON, existing Codejitsu Scroll/URI/Codex/Metadata abstractions, filesystem cache.

**Spec:** `docs/superpowers/specs/2026-08-21-scroll-codex-discovery-cache-design.md`

## Global Constraints

- Use `declare(strict_types=1);` in every PHP file.
- Preserve existing `Codejitsu\Identity` and `Metadata` as authoritative identity/context models.
- Do not add a second registry or identity abstraction.
- Discovery must be extension-driven from `Codejitsu\Enums\Scrolls\Types`.
- Scroll references resolve through the shared `ScrollCodex`.
- Cache invalidation must detect file addition, deletion, rename, modification, and content change.
- CLI help must work for no arguments, `--help`, `-h`, and `help`.

---

### Task 1: Add failing ScrollCodex discovery tests

**Files:**
- Modify: `tests/Scrolls/ScrollCodexTest.php` or create it if absent
- Test fixtures: `tests/fixtures/scrolls/`

**Interfaces:**
- Consumes: `ScrollCodex::load(string $root): static`
- Produces: tests that define the expected complete-resource load behavior

- [ ] **Step 1: Write the failing cold-load test**

```php
public function testItDiscoversAllSupportedScrollTypes(): void
{
    $codex = new ScrollCodex();

    $codex->load($this->fixtureRoot());

    self::assertNotNull($codex->resolve('cmd://hello'));
    self::assertNotNull($codex->resolve('schema://hello'));
    self::assertNotNull($codex->resolve('capability://hello'));
}
```

- [ ] **Step 2: Run the focused test**

Run: `./bin/phpunit --filter testItDiscoversAllSupportedScrollTypes`
Expected: FAIL because `ScrollCodex::load()` does not yet own complete discovery.

- [ ] **Step 3: Add fixtures**

Create three minimal resources under one fixture root:

```text
scrolls/
  hello.cmd
  hello.schema
  hello.capability
```

Use `cmd://hello`, `schema://hello`, and `capability://hello` as the references used by the command fixture.

- [ ] **Step 4: Run the focused test again**

Run: `./bin/phpunit --filter testItDiscoversAllSupportedScrollTypes`
Expected: FAIL at the Codex load operation, confirming the fixtures are parsed and the missing behavior is the implementation.

- [ ] **Step 5: Commit**

```bash
git add tests/Scrolls/ScrollCodexTest.php tests/fixtures/scrolls
git commit -m "test: define complete ScrollCodex discovery"
```

---

### Task 2: Implement extension-driven Scroll discovery

**Files:**
- Create: `src/Scrolls/ScrollDiscovery.php`
- Modify: `src/Scrolls/ScrollCodex.php`
- Test: `tests/Scrolls/ScrollDiscoveryTest.php`

**Interfaces:**
- Consumes: `Types::cases()`, `Types::extension()`, `Types::make()`
- Produces: `ScrollDiscovery::discover(string $root): array` returning hydrated `ScrollContract` instances; `ScrollCodex::load(string $root): static`

- [ ] **Step 1: Write the failing discovery test**

```php
public function testItDiscoversResourcesByDeclaredExtension(): void
{
    $scrolls = (new ScrollDiscovery())->discover($this->fixtureRoot());

    self::assertCount(3, $scrolls);
    self::assertSame(['capability', 'command', 'schema'], array_map(
        static fn (ScrollContract $scroll): string => strtolower($scroll->name),
        array_values(array_filter($scrolls, static fn (ScrollContract $scroll): bool => $scroll->name === 'hello')),
    ));
}
```

- [ ] **Step 2: Run it and verify failure**

Run: `./bin/phpunit --filter testItDiscoversResourcesByDeclaredExtension`
Expected: FAIL because `ScrollDiscovery` does not exist.

- [ ] **Step 3: Implement minimal extension-driven discovery**

`ScrollDiscovery` should:

```php
final class ScrollDiscovery
{
    public function discover(string $root): array
    {
        $types = [];
        foreach (Types::cases() as $type) {
            $types[$type->extension()] = $type;
        }

        $scrolls = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $extension = strtolower($file->getExtension());
            $type = $types[$extension] ?? null;
            if (!$type instanceof Types) {
                continue;
            }

            $data = $this->codec->decode($file->getContents());
            $scrolls[] = $type->make(null, $data);
        }

        return $scrolls;
    }
}
```

Use explicit `Neon` codec construction so discovery does not bootstrap global environment state.

- [ ] **Step 4: Wire `ScrollCodex::load()` to discovery**

```php
public function load(string $root): static
{
    foreach ((new ScrollDiscovery())->discover($root) as $scroll) {
        $this->registerScroll($scroll);
    }

    return $this;
}
```

- [ ] **Step 5: Run focused tests**

Run: `./bin/phpunit --filter 'ScrollDiscovery|ScrollCodex'`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add src/Scrolls/ScrollDiscovery.php src/Scrolls/ScrollCodex.php tests/Scrolls/ScrollDiscoveryTest.php
 git commit -m "feat: make ScrollCodex discover all Scroll resources"
```

---

### Task 3: Add filesystem cache manifest and cache serialization

**Files:**
- Create: `src/Scrolls/ScrollCache.php`
- Modify: `src/Scrolls/ScrollCodex.php`
- Test: `tests/Scrolls/ScrollCacheTest.php`

**Interfaces:**
- Consumes: discovered source paths, `Scroll::toArray()`, NEON codec
- Produces: `ScrollCache::load()`, `ScrollCache::write()`, `ScrollCache::invalidate()`, `ScrollCache::isValid()`

- [ ] **Step 1: Write cache contract tests**

```php
public function testItWritesAndLoadsCache(): void
{
    $cache = new ScrollCache($this->cacheRoot());
    $manifest = $cache->manifestFor($this->fixtureRoot());
    $data = [['name' => 'hello', 'type' => 'command', 'version' => '1.0.0']];

    $cache->write($manifest, $data);

    self::assertTrue($cache->isValid($this->fixtureRoot()));
    self::assertSame($data, $cache->load()->data);
}
```

- [ ] **Step 2: Run focused test and verify failure**

Run: `./bin/phpunit --filter ScrollCacheTest`
Expected: FAIL because `ScrollCache` does not exist.

- [ ] **Step 3: Implement source manifest**

For each supported source file, record relative path, size, mtime, and SHA-256 hash:

```php
[
    'path' => $relativePath,
    'size' => $file->getSize(),
    'mtime' => $file->getMTime(),
    'hash' => hash_file('sha256', $file->getPathname()),
]
```

Sort records by relative path before serializing them so manifest comparison is deterministic.

- [ ] **Step 4: Implement cache payload**

Store:

```neon
format: 1
manifest:
    - path: scrolls/hello.cmd
      size: 123
      mtime: 1234567890
      hash: ...
resources:
    - ...
```

Use an application-private cache directory beneath the configured root, for example `.codejitsu/cache/scrolls.neon`.

- [ ] **Step 5: Implement invalidation**

`isValid()` must return false when the manifest differs in any path, size, mtime, or hash; this covers additions, deletions, renames, and modifications.

- [ ] **Step 6: Run focused cache tests**

Run: `./bin/phpunit --filter ScrollCacheTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add src/Scrolls/ScrollCache.php src/Scrolls/ScrollCodex.php tests/Scrolls/ScrollCacheTest.php
git commit -m "feat: cache discovered Scroll resources"
```

---

### Task 4: Add Codex automatic load, rebuild, and invalidate

**Files:**
- Modify: `src/Scrolls/ScrollCodex.php`
- Test: `tests/Scrolls/ScrollCodexCacheTest.php`

**Interfaces:**
- Consumes: `ScrollCache`, `ScrollDiscovery`
- Produces: `load()`, `rebuild()`, `invalidate()` with static fluent returns

- [ ] **Step 1: Write cache-hit test**

```php
public function testItLoadsFromCacheWithoutRebuildingWhenSourcesAreUnchanged(): void
{
    $codex = (new ScrollCodex())->load($this->fixtureRoot());
    $first = $codex->resolve('cmd://hello')->toArray();

    $second = (new ScrollCodex())->load($this->fixtureRoot());

    self::assertSame($first, $second->resolve('cmd://hello')->toArray());
}
```

- [ ] **Step 2: Write invalidation tests**

```php
public function testItRebuildsWhenAResourceChanges(): void
{
    (new ScrollCodex())->load($this->fixtureRoot());
    file_put_contents($this->commandFile(), $this->changedCommandContents());

    $codex = (new ScrollCodex())->load($this->fixtureRoot());

    self::assertSame('changed', $codex->resolve('cmd://hello')->description());
}
```

Also test add/remove and explicit `invalidate()`/`rebuild()`.

- [ ] **Step 3: Run focused tests and verify failure**

Run: `./bin/phpunit --filter ScrollCodexCacheTest`
Expected: FAIL because automatic cache loading is not implemented.

- [ ] **Step 4: Implement `load()`**

```php
public function load(string $root): static
{
    $cache = $this->cache($root);
    if ($cache->isValid($root)) {
        foreach ($cache->load()->resources as $data) {
            $this->registerScroll($this->hydrateCached($data));
        }
        return $this;
    }

    return $this->rebuild($root);
}
```

- [ ] **Step 5: Implement `rebuild()` and `invalidate()`**

`rebuild()` clears current items, discovers all resources, registers them, and writes cache. `invalidate()` removes only cache artifacts and leaves currently registered in-memory Scrolls untouched.

- [ ] **Step 6: Run focused tests**

Run: `./bin/phpunit --filter ScrollCodexCacheTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add src/Scrolls/ScrollCodex.php tests/Scrolls/ScrollCodexCacheTest.php
git commit -m "feat: add automatic ScrollCodex cache lifecycle"
```

---

### Task 5: Make Boot load the shared Codex and remove command-specific discovery

**Files:**
- Modify: `src/Boot.php`
- Test: `tests/BootTest.php` or `tests/Boot/CliBootTest.php`

**Interfaces:**
- Consumes: `ScrollCodex::load()`
- Produces: CLI startup with all Scrolls loaded before application execution

- [ ] **Step 1: Write failing Boot test**

```php
public function testCliBootLoadsCompleteScrollCodex(): void
{
    $app = Boot::cli('cli', null, $this->fixtureRoot());

    self::assertNotNull($app->kernel->scrolls->resolve('schema://hello'));
    self::assertNotNull($app->kernel->scrolls->resolve('capability://hello'));
    self::assertNotNull($app->kernel->scrolls->resolve('cmd://hello'));
}
```

- [ ] **Step 2: Run and verify failure**

Run: `./bin/phpunit --filter testCliBootLoadsCompleteScrollCodex`
Expected: FAIL because Boot only performs command-specific discovery.

- [ ] **Step 3: Replace command discovery with Codex load**

`Boot::cli()` should do:

```php
$root = $rootDir ?? (defined('CODEJITSU_ROOT') ? CODEJITSU_ROOT : getcwd());
$scrolls = $codex ?? new ScrollCodex();
$scrolls->load($root);
$kernel = Kernel::instance($name ?? 'cli', $scrolls);
return new Cli($kernel);
```

Remove the direct `CommandDiscovery` dependency from Boot.

- [ ] **Step 4: Run Boot and full tests**

Run: `composer test`
Expected: PASS with no risky tests.

- [ ] **Step 5: Commit**

```bash
git add src/Boot.php tests/BootTest.php tests/Boot/CliBootTest.php
git commit -m "refactor: let ScrollCodex own runtime discovery"
```

---

### Task 6: Add cache management Command Scrolls and CLI help semantics

**Files:**
- Modify: `src/Apps/Cli.php`
- Modify: `src/IO/Translators/Cli.php` only if argument parsing needs help normalization
- Create: `scrolls/commands/scroll-cache.cmd`
- Create: `scrolls/commands/scroll-cache-rebuild.cmd`
- Create: `scrolls/commands/scroll-cache-clear.cmd`
- Tests: `tests/Apps/CliTest.php`

**Interfaces:**
- Consumes: loaded Codex and command Scroll execution
- Produces: `--help`, `-h`, `help` equivalent behavior; cache maintenance as regular Command Scrolls

- [ ] **Step 1: Write failing help tests**

```php
public function testHelpFlagRendersAvailableCommands(): void
{
    $exit = $this->runCli(['./bin/codejitsu', '--help']);

    self::assertSame(0, $exit);
    self::assertStringContainsString('Available commands:', $this->stdout());
    self::assertStringContainsString('hello', $this->stdout());
}

public function testShortHelpFlagRendersAvailableCommands(): void
{
    self::assertSame(0, $this->runCli(['./bin/codejitsu', '-h']));
}

public function testHelpCommandRendersAvailableCommands(): void
{
    self::assertSame(0, $this->runCli(['./bin/codejitsu', 'help']));
}
```

- [ ] **Step 2: Verify failure**

Run: `./bin/phpunit --filter 'test.*Help'`
Expected: FAIL until help handling matches all three forms and command cache utilities exist as Scrolls.

- [ ] **Step 3: Add Command Scroll definitions for cache maintenance**

Each cache maintenance command must use a callable target or Capability reference that calls the existing Codex cache operation. The commands must be discoverable like every other `.cmd` resource.

- [ ] **Step 4: Implement help equivalence**

Treat no action, `--help`, `-h`, and `help` as the same usage-rendering path before dispatching a command. Existing `--` flags remain in intent metadata.

- [ ] **Step 5: Test unknown command handling**

```php
public function testUnknownCommandReturnsNonZeroAndRendersUsage(): void
{
    $exit = $this->runCli(['./bin/codejitsu', 'missing']);

    self::assertSame(1, $exit);
    self::assertStringContainsString('Unknown command [missing].', $this->stderr());
    self::assertStringContainsString('Available commands:', $this->stderr());
}
```

- [ ] **Step 6: Run CLI tests**

Run: `./bin/phpunit --filter CliTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add src/Apps/Cli.php src/IO/Translators/Cli.php scrolls/commands tests/Apps/CliTest.php
 git commit -m "feat: add generated CLI help and Scroll cache commands"
```

---

### Task 7: Add end-to-end shared-Codex reference execution test

**Files:**
- Modify: `tests/Apps/CliTest.php`
- Modify: `tests/Scrolls/Types/CommandTest.php`

**Interfaces:**
- Consumes: complete `ScrollCodex`, `Command`, `Schema`, and `Capability` Scrolls
- Produces: proof that CLI execution works against the same loaded Codex used for discovery and help

- [ ] **Step 1: Write the e2e test**

```php
public function testCliExecutesCommandThroughSchemaAndCapabilityReferences(): void
{
    $exit = $this->runCli(['./bin/codejitsu', 'hello', 'B']);

    self::assertSame(0, $exit);
    self::assertStringContainsString('Hello, B!', $this->stdout());
}
```

The fixtures must ensure the Schema validates the input and the Capability produces the output. Do not hardcode those resources into the CLI test harness; they must come from the shared Codex.

- [ ] **Step 2: Run the focused test**

Run: `./bin/phpunit --filter testCliExecutesCommandThroughSchemaAndCapabilityReferences`
Expected: PASS after the existing reference path is loaded through Codex discovery.

- [ ] **Step 3: Run all tests**

Run: `composer test`
Expected: all tests pass with no risky tests.

- [ ] **Step 4: Smoke-test the executable**

Run:

```bash
./bin/codejitsu
./bin/codejitsu --help
./bin/codejitsu -h
./bin/codejitsu help
./bin/codejitsu hello B
./bin/codejitsu missing
```

Expected: usage for the first four, `Hello, B!` for the command, and a non-zero exit plus usage on stderr for the unknown command.

- [ ] **Step 5: Commit**

```bash
git add tests/Apps/CliTest.php tests/Scrolls/Types/CommandTest.php
 git commit -m "test: verify end-to-end Scroll CLI execution"
```

---

### Task 8: Final verification and PR readiness

**Files:**
- Modify only files required by verification failures

**Interfaces:**
- Consumes: all implementation from Tasks 1-7
- Produces: merge-ready feature branch with green local and CI tests

- [ ] **Step 1: Run the complete local suite**

```bash
composer test
```

Expected: `OK` with zero risky tests.

- [ ] **Step 2: Verify Git diff and status**

```bash
git status --short
git diff --check
```

Expected: no unintended changes and no whitespace errors.

- [ ] **Step 3: Push the feature branch**

```bash
git push origin feature/cli-scrolls
```

- [ ] **Step 4: Verify GitHub Actions**

Wait for the branch's required test workflow to report success. Do not merge while CI is pending or failed.

- [ ] **Step 5: Verify PR scope**

The PR should contain only ScrollCodex discovery/cache, Boot integration, CLI help/cache commands, tests, and the design/plan docs.

- [ ] **Step 6: Merge after green CI**

Merge the PR to `main` only after local and CI suites are green. Then add additional Command Scrolls incrementally in separate changes, using the same Codex discovery and reference mechanism.
