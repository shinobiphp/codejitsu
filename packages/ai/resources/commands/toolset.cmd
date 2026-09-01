name: toolset
type: command
version: 1.0.0
description: Inspect Toolsets.
commands:
  list: {description: List Toolsets., target: Codejitsu\Ai\Commands\AiResources::toolsetList}
  show: {description: Show a Toolset., usage: 'show <name-or-uri>', target: Codejitsu\Ai\Commands\AiResources::toolsetShow}
