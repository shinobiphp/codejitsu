name: make
type: command
version: 1.0.0
description: Create AI Scroll resources.
commands:
  spark: {description: Create a Spark Scroll., usage: 'spark [name] [instructions]', target: Codejitsu\Ai\Commands\MakeAi::spark}
  vessel: {description: Create a Vessel Scroll., usage: 'vessel [name] [runtime] [spark] [provider]', target: Codejitsu\Ai\Commands\MakeAi::vessel}
  skill: {description: Create a Skill Scroll., usage: 'skill [name] [prompt]', target: Codejitsu\Ai\Commands\MakeAi::skill}
  tool: {description: Create a Tool Scroll., usage: 'tool [name] [description] [capability]', target: Codejitsu\Ai\Commands\MakeAi::tool}
  toolset: {description: Create a Toolset Scroll., usage: 'toolset [name] [arguments]', target: Codejitsu\Ai\Commands\MakeAi::toolset}
  provider: {description: Create a Provider Scroll., usage: 'provider [name] [adapter] [model] [api-key-reference]', target: Codejitsu\Ai\Commands\MakeAi::provider}
