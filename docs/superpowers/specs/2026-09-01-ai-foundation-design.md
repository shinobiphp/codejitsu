# Codejitsu AI Foundation Design

## Status

Approved for planning on 2026-09-01.

## Purpose

Create the smallest useful `codejitsu/ai` foundation so Codejitsu can dogfood
agents while building later packages. The package introduces four distinct
concepts:

- **Context** is deterministic project memory.
- **Skill** is reusable, parameterized behavior.
- **Spark** is an agent's identity and defaults.
- **Vessel** is the harness and execution policy that runs a Spark.

Neuron AI is the first runtime adapter, but Codejitsu owns the public contracts.
Consumers must not depend on Neuron classes.

## Scope

The first vertical slice provides:

1. Package-owned `spark`, `vessel`, and `provider` Scroll types.
2. A concrete AI convention and schema for the existing `skill` Scroll type.
3. A provider-neutral execution boundary with a Neuron AI adapter.
4. Deterministic prompt assembly from Spark, Skills, Context Scrolls, and user
   input.
5. One-shot CLI execution and an interactive terminal conversation.
6. Context access, controlled managed-section updates, and explicit capability
   policy.
7. Makers and inspection commands for Sparks, Vessels, and Skills.
8. Provider-neutral Tools and Toolsets adapted to Neuron tools and toolkits.
9. A Context Toolset for discovery, reading, and controlled memory updates.

The package remains cataloged but is not added to the root project's Composer
requirements until its package-level contracts and tests pass.

## Non-goals

This slice does not implement:

- multi-agent orchestration or autonomous execution loops;
- RAG, embeddings, vector databases, or persistent conversation memory;
- MCP transports, discovery, or servers;
- arbitrary expression evaluation in prompt templates;
- unrestricted Context or filesystem mutation by an agent;
- web, Astro, or graphical interfaces;
- background jobs, streaming responses, or provider failover.

These are deliberate deferrals, not implicit requirements.

## Architecture

The dependency flow is:

```text
Skill + Context + Tools + user input
            |
          Spark
            |
          Vessel
            |
       VesselRunner
            |
       AiRuntime contract
            |
       NeuronRuntime
            |
   Neuron AI provider interface
```

`VesselRunner` owns resolution, validation, prompt assembly, and policy. An
`AiRuntime` only receives a complete request and returns a response. This keeps
provider and framework details out of Scrolls, terminal clients, and future web
interfaces.

## Scroll Types

### Spark

A Spark Scroll defines an agent rather than an execution environment.

Required fields:

- `name`
- `instructions`

Optional fields:

- `description`
- `model`: a preference or alias, not provider credentials
- `skills`: ordered default Skill references
- `allowedSkills`: Skill references permitted at runtime; omitted means only
  defaults are allowed
- `contexts`: ordered Context Scroll references
- `capabilities`: capabilities the Spark may request
- `tools`: ordered default Tool references
- `allowedTools`: Tool references permitted at runtime; omitted means only
  defaults and Tools supplied by default Toolsets are allowed
- `toolsets`: ordered default Toolset references
- `allowedToolsets`: Toolset references permitted at runtime; omitted means
  only defaults are allowed
- `metadata`

Defaults must also be allowed. Duplicate references are invalid.

### Vessel

A Vessel Scroll defines how and under what policy a Spark runs.

Required fields:

- `name`
- `runtime`
- `spark`: the default Spark

Optional fields:

- `description`
- `allowedSparks`: Sparks that may replace the default during a session;
  omitted means only the default is eligible
- `provider`: required Provider Scroll reference
- `model`: execution-time override
- `contexts`: additional ordered Context references
- `capabilities`: allowlist constrained by the Spark
- `tools`: Tool allowlist constrained by the Spark
- `toolsets`: Toolset allowlist constrained by the Spark
- `limits`: implementation-neutral limits such as turns and output tokens
- `memory`: `session` for the first slice
- `metadata`

Credentials never appear in a Vessel Scroll.

### Provider

A Provider Scroll is a named, non-secret runtime profile. Required fields are
`name`, `adapter`, `model`, and `credentials`. Credential values are references
such as `env://OPENAI_API_KEY`, never literal secrets. Optional `options` hold
non-secret adapter settings. A project provider Catalog indexes available
profiles, while the bundled provider Catalog describes adapters supported by
the package. Vessels reference Provider Scrolls by URI.

Provider Scrolls, Catalogs, caches, command output, and logs must never contain
resolved credential values. A future secret-store adapter may resolve
`secret://...` references without changing Provider or Vessel formats.

### Skill

The core `skill` Scroll type remains generic. The AI package supplies the AI
schema and interpretation instead of registering a competing Scroll type.

Required fields:

- `name`
- `prompt`

Optional fields:

- `description`
- `inputs`: named input definitions with type, required flag, and optional
  default
- `requires.contexts`: additional Context references
- `requires.capabilities`: capabilities required to use the Skill
- `metadata`

Templates support named substitutions such as `{{subject}}`. Values are plain
data. Missing required inputs, unknown inputs, unknown variables, and malformed
templates fail before invoking a runtime. Templates cannot execute PHP,
commands, filters, property traversal, or arbitrary expressions.

Skills compose in their declared order. Runtime-selected skills follow the
Spark's defaults and must be present in `allowedSkills`.

### Tool

A Tool Scroll describes a function the model may call. It does not contain
arbitrary executable source. A Tool delegates execution to a registered
Codejitsu Capability.

Required fields:

- `name`
- `description`
- `capability`: the backing Capability reference
- `inputSchema`: the arguments exposed to the model

Optional fields:

- `consequential`: whether execution requires approval; defaults to `true`
- `maxRuns`: maximum calls during one request
- `metadata`

### Toolset

A Toolset Scroll is an ordered, reusable collection of Tool references.

Required fields:

- `name`
- `tools`

Optional fields:

- `description`
- `guidelines`: instructions explaining how the tools work together
- `metadata`

Toolsets may select Tools but cannot weaken Tool, Spark, or Vessel policy.

## Public Contracts

The AI package requires `codejitsu/context` and exposes small Codejitsu-owned
value objects and interfaces:

- `AiRuntime` executes an `AiRequest` and returns an `AiResponse`.
- `AiRequest` contains assembled instructions, conversation messages, selected
  model, capabilities, and non-secret runtime metadata.
- `AiResponse` contains text plus optional usage and runtime metadata.
- `SparkDefinition`, `VesselDefinition`, `ProviderDefinition`, and `SkillDefinition` are validated,
  immutable projections of their Scrolls.
- `PromptAssembler` deterministically composes the resolved definitions,
  Context content, Skill inputs, and user input.
- `VesselRunner` coordinates resolution, validation, policy, session history,
  and runtime execution.
- `RuntimeRegistry` resolves a Vessel's runtime name to an `AiRuntime`.
- `ToolDefinition` and `ToolsetDefinition` are validated immutable projections.
- `ToolRegistry` resolves allowed Tool definitions and executes their backing
  Capabilities through a policy and approval boundary.
- `ToolApproval` decides whether a consequential call is approved, denied, or
  requires an interactive decision.

The contracts do not expose Neuron messages, agents, providers, or tools.

## Neuron Adapter

`NeuronRuntime` is the first `AiRuntime`. The package depends on
`neuron-core/neuron-ai` and adapts an `AiRequest` to a Neuron agent invocation.
Neuron's `AIProviderInterface` supplies provider portability inside the adapter;
Codejitsu's `AiRuntime` preserves portability beyond Neuron.

The adapter converts each approved Codejitsu Tool into a Neuron
`ToolInterface` and each Toolset into a Neuron-compatible toolkit, including its
guidelines. Neuron controls the model/tool-call loop; Codejitsu remains the
source of truth for discovery, input schema, execution, limits, and approval.

Provider construction is isolated behind a provider factory. The first
implementation only needs one configured provider path to prove the loop, while
unsupported or incomplete configuration fails with a clear error. Tests use a
fake `AiRuntime` and never call an external model.

The factory receives a resolved Provider definition. The first slice resolves
`env://` credential references only and supports the OpenAI adapter. Literal
credentials and unknown reference schemes fail before provider construction.

## Context, Tools, and Capability Policy

The runner resolves referenced Context Scrolls through the existing codex and
includes their content in deterministic order. Installing AI alongside Context
also makes a built-in `context` Toolset available:

- `context.list`: list Context Scrolls available to the session;
- `context.search`: search their names and content;
- `context.show`: read one allowed Context Scroll;
- `context.update-section`: replace one existing managed section in one allowed
  Context Scroll.

`context.update-section` is how a Spark can intelligently maintain memory: the
model chooses the target, managed section, and replacement content through a
structured tool call. The operation is consequential and always passes through
`ToolApproval`. It can only target Contexts allowed by the active Spark and
Vessel, only replace an existing `codejitsu:managed` section, and must write
atomically under a file lock. The updated Scroll is revalidated before the write
is committed. It cannot create files, edit unmanaged prose, accept a filesystem
path, or update multiple Context Scrolls in one call.

Capabilities are identifiers resolved through Codejitsu's existing capability
system. Effective capabilities are the intersection of the Vessel and Spark
allowlists. Every selected Skill requirement must be a subset of that effective
policy; execution fails otherwise. Effective Tools and Toolsets follow the same
intersection: a Vessel can narrow a Spark's permissions but cannot expand them.

Tools are runtime-neutral Scrolls, never raw Neuron or MCP objects. That allows
future adapters, including MCP, to supply implementations without changing
agent definitions.

## CLI Commands

### Makers

- `make:spark <name>`
- `make:vessel <name>`
- `make:skill <name>`
- `make:tool <name>`
- `make:toolset <name>`
- `make:provider <name>`

Each maker validates and normalizes the name, refuses overwrites, writes a
minimal valid Scroll to its registered project directory, and reports the path
and useful next command. Without a name, it asks interactively. Interactive
questions may collect default Skills and Contexts for a Spark, runtime and Spark
for a Vessel, template inputs for a Skill, Capability and input schema for a
Tool, and ordered Tools plus guidelines for a Toolset. Both CLI and TUI creation
use the same maker services.

### Inspection and execution

- `spark:list`
- `spark:show <name-or-uri>`
- `vessel:list`
- `vessel:show <name-or-uri>`
- `skill:list`
- `skill:show <name-or-uri>`
- `skill:render <name-or-uri> [inputs]`
- `tool:list`
- `tool:show <name-or-uri>`
- `toolset:list`
- `toolset:show <name-or-uri>`
- `provider:list`
- `provider:show <name-or-uri>`
- `provider:test <name-or-uri>`
- `ai:run <vessel> [prompt]`
- `ai:tui`

`skill:render` accepts repeatable `--input name=value` options. Interactive use
prompts for any required value that was not supplied.

## Interactive Terminal

`ai:tui` is a thin terminal client over `VesselRunner`.

It supports:

- selection of a Vessel and an eligible Spark;
- a conversational session with in-memory history;
- display of active runtime, provider/model, Spark, Skills, Contexts, and
  Tools/capabilities;
- `/new`, `/clear`, `/context`, `/skills`, `/skill add`, `/skill remove`,
  `/tools`, `/spark`, `/vessel`, `/help`, and `/exit`;
- clean provider, configuration, validation, and runtime errors without ending
  the session.

The TUI asks for explicit confirmation immediately before every consequential
Tool call and shows the Tool name plus validated arguments. `ai:run` denies
consequential Tools by default; a caller must explicitly allow a named Tool with
a repeatable `--approve-tool <name>` option.

## MCP Integration Seam

MCP implementation belongs in a future `codejitsu/mcp` package, not the generic
API package and not this first slice. That package may provide MCP clients,
servers, transports, tool discovery, and adapters from MCP tools to Codejitsu
capabilities.

An MCP package may register Tool implementations or translate discovered MCP
tools into Codejitsu Tool definitions. A Vessel still resolves them through the
same Tool and Capability policy. Therefore adding MCP later does not alter the
AI Scroll formats or terminal client.

## Error Handling

All failures occur before an external request whenever possible. User-facing
errors identify the failing Scroll and field without exposing secrets. The
system distinguishes resolution, validation, policy, configuration, and runtime
failures so both the CLI and future interfaces can report them consistently.

The TUI catches recoverable request errors and returns to its prompt. One-shot
commands return a non-zero exit status for failures.

## Testing and Acceptance

Package-level tests cover:

- Spark, Vessel, and AI Skill validation;
- safe Skill rendering, required/default inputs, and rejected expressions;
- deterministic Skill and Context composition;
- unknown and ambiguous Scroll references;
- capability intersection and rejected Skill requirements;
- Tool and Toolset validation, ordering, limits, and Capability delegation;
- approval denial and approval of named consequential Tools;
- Context Toolset read operations and scoped atomic managed-section updates;
- runtime substitution using a fake `AiRuntime`;
- Neuron request and Tool adaptation without a live API call;
- maker creation, normalization, interactive fallback, and overwrite refusal;
- command output and exit behavior;
- TUI selection, session commands, history clearing, and recoverable failures.

Acceptance requires:

1. A valid Vessel can run a valid Spark through a fake runtime end to end.
2. `ai:tui` can hold a multi-turn in-memory conversation through the same
   `VesselRunner` used by `ai:run`.
3. Skill templates and Context content are assembled deterministically.
4. Invalid references, inputs, policy, and provider configuration fail clearly.
5. A Neuron-backed request can invoke an allowed Codejitsu Tool through the
   fake Capability boundary.
6. A confirmed Context update can replace one managed section while attempts to
   edit unmanaged or unapproved content fail without changing the file.
7. The AI package's tests and the complete Codejitsu suite pass.
8. The root project does not install `codejitsu/ai` during isolated package
   development.

## Future Work

After dogfooding this slice:

1. Persistent sessions and richer Context memory policies.
2. `codejitsu/mcp` with explicit trust and approval policy.
3. Additional AI runtimes or direct provider adapters.
4. Multi-Spark workflows only after real use cases establish the orchestration
   contract.
5. `codejitsu/ui` clients over the same Vessel runner.
