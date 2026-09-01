name: vessel
type: command
version: 1.0.0
description: Inspect Vessels.
commands:
  list: {description: List Vessels., target: Codejitsu\Ai\Commands\AiResources::vesselList}
  show: {description: Show a Vessel., usage: 'show <name-or-uri>', target: Codejitsu\Ai\Commands\AiResources::vesselShow}
