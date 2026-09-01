name: spark
type: command
version: 1.0.0
description: Inspect Sparks.
commands:
  list: {description: List Sparks., target: Codejitsu\Ai\Commands\AiResources::sparkList}
  show: {description: Show a Spark., usage: 'show <name-or-uri>', target: Codejitsu\Ai\Commands\AiResources::sparkShow}
