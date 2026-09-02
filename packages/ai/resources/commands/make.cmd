name: make
type: command
version: 1.0.0
description: Create AI Scroll resources.
commands:
  spark: {description: Create a Spark Scroll., usage: 'spark [name] [instructions]', capability: capability://make-spark}
  vessel: {description: Create a Vessel Scroll., usage: 'vessel [name] [runtime] [spark] [provider]', capability: capability://make-vessel}
  skill: {description: Create a Skill Scroll., usage: 'skill [name] [prompt]', capability: capability://make-skill}
  tool: {description: Create a Tool Scroll., usage: 'tool [name] [description] [capability]', capability: capability://make-tool}
  toolset: {description: Create a Toolset Scroll., usage: 'toolset [name] [arguments]', capability: capability://make-toolset}
  provider: {description: Create a Provider Scroll., usage: 'provider [name] [adapter] [model] [api-key-reference]', capability: capability://make-provider}
