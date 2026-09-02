name: skill
type: command
version: 1.0.0
description: Inspect and render Skills.
commands:
  list: {description: List Skills., capability: capability://skill-list}
  show: {description: Show a Skill., usage: 'show <name-or-uri>', capability: capability://skill-show}
  render: {description: Render a Skill., usage: 'render <name-or-uri> [arguments]', capability: capability://skill-render}
