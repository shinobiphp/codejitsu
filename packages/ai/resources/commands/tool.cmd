name: tool
type: command
version: 1.0.0
description: Inspect Tools.
commands:
  list: {description: List Tools., target: Codejitsu\Ai\Commands\AiResources::toolList}
  show: {description: Show a Tool., usage: 'show <name-or-uri>', target: Codejitsu\Ai\Commands\AiResources::toolShow}
