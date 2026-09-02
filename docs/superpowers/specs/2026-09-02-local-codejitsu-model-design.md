# Local Codejitsu Model Design

## Goal

Ship a reproducible, AI-package-owned Ollama model build for local Codejitsu dogfooding. The model is a balanced engineering foundation; Sensei, Kage, and other roles remain Spark Scrolls.

## Base Model

The default base is `bartowski/Qwen2.5-Coder-7B-Instruct-abliterated-GGUF` at `Q5_K_M`. Its approximately 5.44 GB weights fit the current 14 GiB CPU/RAM workstation while leaving room for Ollama, an 8K context, tools, and project Context. The base reference must be overridable so the same model identity can later target a larger local quant or Runpod deployment.

The build must preserve Qwen's chat/tool template. Tool support is verified with an Ollama API smoke test rather than inferred from the base model name.

## Model Identity and Prompt Boundary

The built Ollama model is `codejitsu:latest`. Its Modelfile contains only durable behavior:

- act as a direct, pragmatic senior engineering collaborator;
- follow the operator's authorized technical intent without moralizing, legal speculation, corporate-policy language, or generic refusals;
- distinguish factual risk from value judgments;
- warn concisely before unintended data loss, secret exposure, or irreversible external effects;
- inspect supplied Context before making architectural claims;
- state uncertainty and assumptions explicitly;
- preserve existing architecture and unrelated user changes;
- prefer small coherent changes and never fabricate verification;
- treat current project Context and Skills as authoritative over baked-in model knowledge.

The Modelfile must not permanently mandate OpenSwoole, NATS, Astro, UI libraries, PHP features, personalities, or workflows. Those belong in Context, Skills, and Sparks.

Default parameters are `num_ctx 8192`, `temperature 0.2`, and `top_p 0.9`.

## Package Resources

The AI package owns a `model` Scroll type. `packages/ai/resources/models/codejitsu.local.model` records the destination name, runtime, base reference, known download size, capabilities, storage path, and portable Modelfile path without storing weights. `packages/ai/resources/modelfiles/codejitsu/Modelfile` contains the tracked model definition.

A bundled generic Catalog named `ai-models` indexes entries with `kind: model`. The initial `model://codejitsu/local#1.0.0` entry points to the bundled Model Scroll and advertises code, tools, abliterated, Ollama, and CPU tags. Project and private Catalogs may add or override model recipes through normal Catalog precedence, enabling future local, Runpod, licensed, and marketplace models without changing Sparks.

The AI package adds a `model` command namespace:

- `model:list` delegates to `ollama list`;
- `model:search <query>` searches generic Catalog entries with `kind: model` and annotates local installation status;
- `model:show <name>` delegates to `ollama show`;
- `model:build [manifest] [--name=...] [--base=...]` renders a temporary Modelfile with the selected base and invokes `ollama create`;
- `model:ensure [manifest]` returns immediately when the model exists and otherwise opens the setup wizard;
- `model:test [name]` performs deterministic chat and tool-call smoke tests;
- `model:remove` is excluded from this slice because it is destructive and Ollama already exposes it directly.

The builder uses an injected process boundary, validates model names and references, writes temporary files under the project `var/tmp` convention, streams useful build output, and returns non-zero failures without hiding Ollama errors. No shell interpolation is used.

## Package Setup Lifecycle

The Package Scroll declares an idempotent `model:ensure` setup action. After rebuilding the package registry, the Composer plugin evaluates registered setup actions. An existing `codejitsu:latest` model satisfies the action without output or mutation.

When Composer is interactive and the model is missing, the AI-owned wizard offers four choices: download the recommended model, build from an existing GGUF path, build from another Ollama or Hugging Face reference, or skip. The wizard validates local paths and remote references, displays the destination name, source, approximate known download size, and exact operation, then requests confirmation before building and smoke-testing.

Non-interactive installation never downloads weights implicitly. It reports the pending `model:ensure` command unless `CODEJITSU_AUTO_SETUP=1` and a valid `CODEJITSU_MODEL_SOURCE` are supplied. `CODEJITSU_MODEL_NAME` optionally overrides the destination. The same setup action is available later through `pkg:setup codejitsu/ai` and `model:ensure`.

Package setup is a generic lifecycle boundary: the Composer plugin coordinates registered actions, while the owning package implements domain-specific interaction and execution. A failed or skipped optional model setup does not corrupt the package registry.

## Data and Execution Flow

The model manifest and Modelfile are discovered as package resources. The Build Command resolves the selected manifest, substitutes only the validated `FROM` value, creates `codejitsu:latest`, and then runs smoke tests. Model weights remain in Ollama's managed store and never enter Git or Codejitsu caches.

Downloaded or imported source weights live under `var/models`. Rendered Modelfiles and incomplete downloads live under `var/tmp/codejitsu-ai`. The repository `.gitignore` excludes both locations and model weight formats including `*.gguf` and `*.safetensors`. Versioned Modelfiles and small model/catalog Scrolls remain tracked.

The future Ollama Provider adapter will call Ollama's OpenAI-compatible API. That adapter is a separate follow-up because model lifecycle and inference transport are independent boundaries.

## Verification

Tests cover manifest validation, safe rendering, name/reference rejection, exact process arguments, setup discovery, existing-model idempotence, all interactive choices, non-interactive skipping and opt-in automation, failure propagation, and Command Scroll registration using fake process and questioner boundaries. An opt-in local integration test verifies `ollama create`, direct response behavior, supplied Codejitsu Context, structured output, and a real dummy tool call. It must not download multi-gigabyte weights during the default test suite.
