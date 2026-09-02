# Ollama Starter Vessels Design

## Goal

Make the installed `codejitsu/ai` package immediately usable with the existing local `codejitsu:latest` Ollama model through Neuron AI, while shipping product-neutral starter Sparks and Vessels.

## Provider

`ProviderFactory` supports `openai` and `ollama`. OpenAI retains required `env://` credentials. Ollama requires no credentials and accepts:

- `options.url`, defaulting to `http://127.0.0.1:11434/api`
- `options.parameters`, an optional map passed to Neuron's Ollama provider

Provider validation remains adapter-owned. `provider:test` validates configuration without making an inference request.

## Bundled resources

The package ships `provider://ollama/local` for `codejitsu:latest`.

It ships six product-neutral Sparks:

- `engineer`: implementation, debugging, testing, and review
- `scribe`: Context, architecture, specifications, ADRs, and technical writing
- `product-designer`: product strategy, UX/UI, accessibility, and developer experience
- `marketer`: positioning, messaging, campaigns, and conversion copy
- `seo`: search strategy, technical SEO, and content optimization
- `security`: threat modeling, secure design, and audit guidance

It ships two Neuron/Ollama Vessels:

- `code`, defaulting to `engineer`
- `cognition`, defaulting to `scribe`

Both permit all six bundled Sparks, load the package architecture and operations Context Scrolls, and expose the existing Context Toolset under the existing consequential-action approval policy.

Vessel identity describes purpose rather than Provider infrastructure. Provider or other runtime overrides may later use URI query parameters, but override semantics are outside this slice.

Sensei and Kage remain product concerns and are not bundled. This slice adds no inheritance mechanism.

## Verification

Tests cover credential rules, Ollama provider construction/options, bundled resource discovery, complete Vessel resolution, and command behavior. Live verification runs `provider:test`, resource `show` commands, and `ai:run`; CPU inference latency is reported honestly if it cannot complete promptly.

Documentation and Context Scrolls record only verified behavior.
