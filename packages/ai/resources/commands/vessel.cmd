name: vessel
type: command
version: 1.0.0
description: Inspect Vessels.
commands:
  list: {description: List Vessels., capability: capability://vessel-list}
  show: {description: Show a Vessel., usage: 'show <name-or-uri>', capability: capability://vessel-show}
