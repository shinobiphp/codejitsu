# Scroll Runtime and Interactive Make Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make executable Scrolls first-class by supporting detected/declared substrates, isolated PHP execution, and an interactive `make:scroll` flow that captures source safely.

**Architecture:** Keep `Capability` as the Scroll-level executable contract and move language execution behind `Codejitsu\Substrate`. Detection uses an explicit `substrate` when present, otherwise shebang/PHP-tag detection with PHP as the default. The MVP sandbox executes source in a short-lived child PHP process with a dedicated working directory, restricted filesystem scope, disabled process/network escape functions, bounded runtime, and JSON context/result transport. Interactive creation remains a console concern in `Make`, while the non-interactive API stays deterministic for tests and automation.

**Tech Stack:** PHP 8.4+, Symfony Console/Process, Nette NEON, PHPUnit 13.

**Spec:** Existing approved scroll runtime/substrate/interactive-make specification from the current Codejitsu workstream.

## Global Constraints

- `declare(strict_types=1);` everywhere.
- Public classes are behavioral contracts; use composition over helpers/statics except bootstrap-compatible entry points.
- Preserve the existing Scroll URI and NEON format.
- Existing non-interactive `make:scroll <uri> [--source=...]` behavior remains compatible.
- Unsupported substrates fail explicitly; no silent execution fallback.
- Executable Scrolls run with least privilege and never receive the parent process environment wholesale.

---

### Task 1: Substrate runtime boundary and safe PHP execution

**Files:**
- Create: `src/Substrate/Runtime.php` — resolves a declared/detected substrate to an executable implementation.
- Create: `src/Substrate/PhpSandbox.php` — executes PHP source in a child process with bounded resources and isolated working directory.
- Modify: `src/Substrate/Detector.php` — recognize `php`, `lua`, `js`, `v8js`, and common interpreter shebang forms while retaining PHP as the default.
- Modify: `src/Substrate/Php.php` — delegate execution to the sandbox implementation.
- Modify: `src/Scrolls/Types/Capability.php` — execute source through the runtime boundary instead of directly evaluating source.
- Modify: `composer.json` — add `symfony/process` if the current dependency graph does not already provide it.
- Test: `tests/Substrate/DetectorTest.php`, `tests/Substrate/PhpSandboxTest.php`, `tests/Scrolls/CapabilityTest.php`.

**Interfaces:**
- `Runtime::execute(string $substrate, string $source, ExecutionContext $context): mixed`
- `PhpSandbox::execute(string $source, ExecutionContext $context): mixed`
- The runtime owns substrate selection; `Capability` only supplies Scroll data and context.

- [ ] Write tests for explicit PHP selection, shebang detection, default PHP selection, unsupported substrate errors, context/result transport, and sandbox escape functions being unavailable.
- [ ] Run the focused substrate/capability tests and verify they fail for the new behavior.
- [ ] Implement the runtime registry with built-in PHP and explicit errors for unavailable Lua/V8JS runtimes.
- [ ] Implement PHP child-process execution using Symfony Process with a temporary working directory, JSON context input/output, timeout, and restricted PHP CLI settings (`open_basedir`, disabled process/network functions, cleared environment, isolated cwd).
- [ ] Run focused tests again and then the full suite.
- [ ] Commit as `feat: sandbox executable scroll substrates`.

### Task 2: Interactive `make:scroll`

**Files:**
- Modify: `src/Commands/Make.php` — support interactive mode when no URI/source options are supplied.
- Create: `src/Console/ScrollMaker.php` — console interaction flow and editor abstraction.
- Create: `src/Console/Editor.php` — launch `$VISUAL`/`$EDITOR`, fallback to `vi`, capture the temporary file contents.
- Modify: `src/Enums/Scrolls/Types.php` — expose ordered interactive type metadata without duplicating type definitions.
- Test: `tests/Commands/MakeTest.php`, `tests/Console/ScrollMakerTest.php`.

**Interfaces:**
- `ScrollMaker::create(ExecutionContext $context): string`
- `Editor::edit(string $initial = ''): string`
- Interactive prompts: Scroll type -> URI/name -> version -> executable/content mode -> editor capture.

- [ ] Add tests proving non-interactive creation remains unchanged and interactive dependencies can be supplied as fakes.
- [ ] Run focused tests and verify the new interactive cases fail.
- [ ] Implement the type menu from `Types::map()` metadata, including capability/command/config/context/schema/kata/skill/app.
- [ ] Prompt for logical URI/name and version; default to `1.0.0` for a new Scroll and increment the patch component when an existing logical name has prior versions.
- [ ] For executable-capable types, prompt for substrate (`auto`, `php`, `lua`, `js/v8js` where available), seed the editor with an appropriate template, and persist the captured source using NEON triple-quoted multiline strings.
- [ ] For non-executable types, use the type's minimal valid payload/template and editor where content is appropriate.
- [ ] Add `--interactive` support while also entering interactive mode when `make:scroll` has no creation payload in a TTY.
- [ ] Run focused and full tests.
- [ ] Commit as `feat: add interactive scroll creation`.

### Task 3: CLI UX and integration coverage

**Files:**
- Modify: `src/Console/UsageRenderer.php` — render nested subcommands, descriptions, and colored sections consistently with Symfony Console conventions.
- Modify: `src/Apps/Cli.php` — ensure namespace usage/help and interactive command dispatch behave correctly.
- Modify: `scrolls/commands/make.cmd` — document interactive mode and options.
- Modify: `scrolls/commands/scroll.cmd` — document `scroll:run` and substrate execution behavior.
- Modify: `scrolls/capabilities/make-scroll.capability` — expose interactive creation defaults.
- Modify: `scrolls/capabilities/scroll-run.capability` — expose runtime execution defaults.
- Test: `tests/Apps/CliTest.php`, `tests/Commands/ScrollRunTest.php`, `tests/Console/UsageRendererTest.php`.

- [ ] Add CLI integration tests for `make:scroll`, `scroll:run`, namespace usage, and colored output when a TTY/decorated formatter is available.
- [ ] Run focused tests and verify failures.
- [ ] Update command Scrolls and usage rendering.
- [ ] Run `composer test` and a manual smoke sequence: `make:scroll`, create PHP Scroll, `scroll:run`, and top-level `make`/`scroll` help.
- [ ] Verify generated NEON decodes and multiline `source: """ ... """` survives a discovery/load/execute cycle.
- [ ] Commit as `feat: polish scroll CLI workflow`.

## Verification

Final verification must show the full PHPUnit suite green and the following manual flow working from a clean checkout:

```bash
composer test
./bin/codejitsu make:scroll
./bin/codejitsu scroll:run capability://<created-scroll> <argument>
./bin/codejitsu make --help
./bin/codejitsu scroll --help
```
