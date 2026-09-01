name: skill
type: command
version: 1.0.0
description: Inspect and render Skills.
commands:
  list: {description: List Skills., target: Codejitsu\Ai\Commands\AiResources::skillList}
  show: {description: Show a Skill., usage: 'show <name-or-uri>', target: Codejitsu\Ai\Commands\AiResources::skillShow}
  render: {description: Render a Skill., usage: 'render <name-or-uri> [arguments]', target: Codejitsu\Ai\Commands\AiResources::skillRender}
