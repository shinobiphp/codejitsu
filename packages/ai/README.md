# Codejitsu AI

Provider-neutral AI orchestration for Codejitsu. The package is cataloged but intentionally not installed by the root project.

## Resources

- **Context** is durable project memory.
- **Skill** is reusable, parameterized prompt behavior.
- **Spark** defines an agent's instructions and allowed resources.
- **Vessel** is the harness that selects a runtime, Spark, Provider, and policy.
- **Provider** is a non-secret adapter/model profile. Credentials are references such as `env://OPENAI_API_KEY`; secret values never belong in Scrolls.
- **Tool** maps validated model input to a Codejitsu Capability. **Toolsets** compose Tools and guidance.

Create resources with `make:spark`, `make:vessel`, `make:provider`, `make:skill`, `make:tool`, and `make:toolset`. Inspect profiles with `provider:list`, `provider:show`, and `provider:test`.

Run once with `ai:run <vessel> "prompt"`. Consequential tools are denied unless named with `--approve-tool=<name>`. Open an interactive session with `ai:tui`; consequential calls require confirmation. Context updates are limited to managed sections of Context Scrolls explicitly resolved into the session.

Neuron AI is the first runtime adapter and OpenAI is the first Provider adapter. Public definitions remain provider-neutral. MCP belongs in a future integration package.
