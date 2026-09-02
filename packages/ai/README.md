# Codejitsu AI

Provider-neutral AI orchestration for Codejitsu. The package is cataloged and installed by the root development aggregate.

## Resources

- **Context** is durable project memory.
- **Skill** is reusable, parameterized prompt behavior.
- **Spark** defines an agent's instructions and allowed resources.
- **Vessel** is the harness that selects a runtime, Spark, Provider, and policy.
- **Provider** is a non-secret adapter/model profile. Credentials are references such as `env://OPENAI_API_KEY`; secret values never belong in Scrolls.
- **Tool** maps validated model input to a Codejitsu Capability. **Toolsets** compose Tools and guidance.

Create resources with `make:spark`, `make:vessel`, `make:provider`, `make:skill`, `make:tool`, and `make:toolset`. Inspect profiles with `provider:list`, `provider:show`, and `provider:test`.

Run once with `ai:run <vessel> "prompt"`. Override the selected resources with `--spark=<name>`, `--provider=<name>`, and `--model=<name>`. Attach a permitted Skill with `--skill=<name>` and pass its inputs using the query-string form `--input "name=value&name2=value2"`; each input applies to the preceding Skill. Consequential tools are denied unless named with `--approve-tool=<name>`. Open `ai:tui` to select a Vessel, Spark, Provider, optional Skill/model, allowed Context subset, and tool approvals interactively. Context updates remain limited to managed sections of Context Scrolls resolved into the session.

AI commands bind their PHP handlers through Capability Scrolls so arguments and the active Codex arrive in an `ExecutionContext`. Package-local Context Scrolls under `resources/contexts` document the architecture and operating model for Sparks.

Neuron AI is the first runtime adapter. The package includes `provider://groq/free` for fast hosted development with `GROQ_API_KEY` and `provider://ollama/local` for offline inference. The Groq profile uses low reasoning effort and an 8,192-token completion budget for substantial structured responses. Copy `.env.example` to `.env`, set the Groq key, then run `provider:test groq/free`. Public definitions remain provider-neutral. MCP belongs in a future integration package.

The bundled `code` Vessel defaults to the `engineer` Spark; `cognition` defaults to `scribe`. Both allow the bundled `product-designer`, `marketer`, `seo`, and `security` Sparks as well. Hosted Providers receive the selected Context Scrolls and prompt; use Ollama when that material must remain local.

The bundled `product-strategy` Skill generates a prioritized JSON portfolio for Codejitsu, Buildshido, Shinobi, Satori, Kensho, Archiq, Kuzuryu, Ryujin, Daimyo, Sensei, and Shogun. It is permitted by the scribe, product-designer, and marketer Sparks:

```bash
./bin/codejitsu ai:run cognition --spark=product-designer --skill=product-strategy --input "additional_context=Prioritize%20funding." "Analyze the portfolio."
```

## Local models

The bundled `ai-models` Catalog includes `model://codejitsu/local`, a balanced Ollama recipe based on the abliterated Qwen2.5-Coder 7B Q5_K_M model. Model Scrolls describe reproducible builds; tracked Modelfiles contain durable behavior, while personalities remain Sparks.

Use `model:search`, `model:show`, `model:list`, `model:build`, `model:ensure`, and `model:test`. `model:ensure codejitsu/local` offers the recommended download, an existing GGUF path, another model reference, or a clean skip. Package post-install invokes the same optional wizard when interactive. Non-interactive installs never download weights unless `CODEJITSU_AUTO_SETUP=1` and `CODEJITSU_MODEL_SOURCE` are set; `CODEJITSU_MODEL_NAME` optionally changes the destination.

Source weights belong in `var/models`; rendered Modelfiles and partial work use `var/tmp/codejitsu-ai`. Both directories and common weight formats are ignored by Git. Ollama owns final model layers.
