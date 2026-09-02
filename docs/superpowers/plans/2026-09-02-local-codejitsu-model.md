# Local Codejitsu Model Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a cataloged, reproducible, interactive Ollama model lifecycle to `codejitsu/ai` and build the balanced local `codejitsu:latest` model.

**Architecture:** The AI package owns Model Scroll recipes, a generic model Catalog, a portable Modelfile, and model lifecycle commands. A generic package setup declaration is compiled into the package registry and coordinated after Composer installation; AI-specific interaction remains in the AI package. Model weights live under `var/models` or Ollama's store and never in Git.

**Tech Stack:** PHP 8.4+, Codejitsu Scrolls/Codex/Catalogs/Command Scrolls, Composer plugin lifecycle, Ollama CLI/API, PHPUnit 13.

**Spec:** `docs/superpowers/specs/2026-09-02-local-codejitsu-model-design.md`

## Global Constraints

- Default recipe is Qwen2.5-Coder-7B-Instruct-abliterated `Q5_K_M`, destination `codejitsu:latest`, context 8192, temperature 0.2, top-p 0.9.
- Model behavior stays neutral; roles belong in Sparks and project facts belong in Context.
- Non-interactive installs never download weights without `CODEJITSU_AUTO_SETUP=1` and a validated source.
- All process arguments are arrays through `Codejitsu\Contracts\ProcessRunner`; no shell interpolation.
- Default tests never download weights or call a live model.
- Existing unrelated changes in root Composer files and `src/PackageManager.php` remain untouched.

---

### Task 1: Register Model Recipes and Catalog

**Files:**
- Modify: `.gitignore`
- Modify: `packages/ai/codejitsu.package`
- Create: `packages/ai/src/Scrolls/Model.php`
- Create: `packages/ai/src/Definitions/ModelDefinition.php`
- Modify: `packages/ai/src/Definitions/DefinitionLoader.php`
- Create: `packages/ai/resources/models/codejitsu.local.model`
- Create: `packages/ai/resources/catalogs/ai-models.catalog`
- Create: `packages/ai/resources/modelfiles/codejitsu/Modelfile`
- Test: `packages/ai/tests/Definitions/ModelDefinitionTest.php`
- Modify: `packages/ai/tests/Scrolls/TypeRegistrationTest.php`

**Interfaces:**
- Produces: `ModelDefinition::fromArray(array): self` with `name`, `runtime`, `destination`, `base`, `size`, `capabilities`, `modelfile`, and `storage`.
- Produces: `DefinitionLoader::model(string): ModelDefinition`.

- [ ] Write tests proving valid recipes normalize, unsafe destinations/paths/references fail, the package registers `model://`, and `ai-models.catalog` indexes `model://codejitsu/local#1.0.0`.
- [ ] Run focused tests and confirm failure because Model support is absent.
- [ ] Implement the Model Scroll/definition/loader, recipe, Catalog, durable Modelfile, and `/var/models/`, `/var/tmp/codejitsu-ai/`, `*.gguf`, `*.safetensors` ignores.
- [ ] Run focused tests and the AI suite; commit `feat(ai): catalog local model recipes`.

### Task 2: Build a Safe Ollama Lifecycle Boundary

**Files:**
- Create: `packages/ai/src/Models/ModelProcess.php`
- Create: `packages/ai/src/Models/ModelfileRenderer.php`
- Create: `packages/ai/src/Models/OllamaModels.php`
- Test: `packages/ai/tests/Models/ModelfileRendererTest.php`
- Test: `packages/ai/tests/Models/OllamaModelsTest.php`

**Interfaces:**
- Produces: `ModelfileRenderer::render(ModelDefinition $model, ?string $base = null): string` replacing exactly one `FROM` directive.
- Produces: `OllamaModels::installed(string): bool`, `list(): ProcessResult`, `show(string): ProcessResult`, `build(ModelDefinition, ?string, ?string): ProcessResult`, and `test(string): ModelTestResult`.
- Consumes: injected `ProcessRunner` and project root; temporary Modelfiles live under `var/tmp/codejitsu-ai`.

- [ ] Write fake-runner tests for exact argument arrays, one-directive rendering, path/reference validation, idempotent installed checks, temporary cleanup, and Ollama error propagation.
- [ ] Run focused tests and confirm failure because the lifecycle boundary is absent.
- [ ] Implement minimal renderer and Ollama service without invoking a shell.
- [ ] Run focused and AI suites; commit `feat(ai): add Ollama model lifecycle`.

### Task 3: Add Model Commands and Interactive Ensure Wizard

**Files:**
- Create: `packages/ai/src/Commands/Models.php`
- Create: `packages/ai/src/Console/ModelSetupIO.php`
- Create: `packages/ai/src/Console/TerminalModelSetupIO.php`
- Create: `packages/ai/src/Models/ModelSetup.php`
- Create: `packages/ai/resources/commands/model.cmd`
- Test: `packages/ai/tests/Commands/ModelsTest.php`
- Test: `packages/ai/tests/Models/ModelSetupTest.php`

**Interfaces:**
- Produces: `model:list`, `model:search`, `model:show`, `model:build`, `model:ensure`, and `model:test`.
- Produces: `ModelSetup::ensure(ModelDefinition, ModelSetupIO, ModelSetupOptions): int`.
- Interactive choices are recommended download, existing GGUF, alternate reference, and skip; confirmation is mandatory before mutation.

- [ ] Write scripted-IO tests for every choice, invalid paths/references, existing-model no-op, declined confirmation, build failure, and successful smoke test.
- [ ] Write command tests proving Catalog search includes installed state and arguments map to setup options.
- [ ] Run focused tests and confirm failure.
- [ ] Implement the commands and wizard with concise error output and no destructive remove command.
- [ ] Run focused and AI suites; commit `feat(ai): add interactive model setup`.

### Task 4: Register Generic Package Setup Actions

**Files:**
- Modify: `src/Scrolls/Types/Package.php`
- Modify: `src/Packages/PackageCompiler.php`
- Modify: `packages/composer-plugin/src/PackageInstaller.php`
- Modify: `packages/composer-plugin/src/Plugin.php`
- Modify: `packages/ai/codejitsu.package`
- Test: `tests/Scrolls/Types/PackageTest.php`
- Test: `tests/Packages/PackageCompilerTest.php`
- Test: `packages/composer-plugin/tests/PackageInstallerTest.php`

**Interfaces:**
- Package manifest field `setup` is a map of action names to `{capability, optional, interactive}`.
- Compiled registry preserves validated setup declarations.
- After cache rebuild, interactive Composer IO offers optional actions; non-interactive execution only occurs with explicit environment opt-in.

- [ ] Write tests rejecting malformed setup declarations and preserving valid declarations in the compiled cache.
- [ ] Write plugin tests proving registry-first ordering, interactive dispatch, skip behavior, and non-interactive opt-in rules without executing Ollama.
- [ ] Run focused tests and confirm failure.
- [ ] Implement generic setup declarations and coordination; declare the AI action as `model:ensure`.
- [ ] Run root, plugin, and AI suites; commit `feat(package): add optional setup actions`.

### Task 5: Documentation, Live Build, and Release Verification

**Files:**
- Modify: `packages/ai/README.md`
- Modify: `README.md`
- Modify: `.context/current-state.ctx`
- Modify: `.context/roadmap/current.ctx`
- Modify: `.context/todo.ctx`

**Interfaces:**
- Produces: documented interactive, scripted, local-file, and alternate-reference workflows plus storage and security rules.

- [ ] Document model Catalog/Scroll fields, commands, setup prompts, environment automation, `var/models`, and future Runpod/Ollama Provider use.
- [ ] Run `composer validate --strict packages/ai/composer.json`, PHP syntax checks, AI tests, root tests, audit, and clean-install tests.
- [ ] Run `model:ensure codejitsu/local` interactively, select the recommended Q5_K_M source, and build `codejitsu:latest`; this is the only step allowed to download weights.
- [ ] Run live smoke tests for direct engineering response, supplied Codejitsu Context, structured JSON, and a dummy tool call; record actual results without committing weights.
- [ ] Commit `docs(ai): document local model workflow` and report the remaining Ollama Provider adapter as the next isolated slice.
