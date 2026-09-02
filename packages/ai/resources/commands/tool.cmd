name: tool
type: command
version: 1.0.0
description: Inspect Tools.
commands:
  list: {description: List Tools., capability: capability://tool-list}
  show: {description: Show a Tool., usage: 'show <name-or-uri>', capability: capability://tool-show}
