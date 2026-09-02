name: model
type: command
version: 1.0.0
description: 'Discover, build, and test AI models.'
commands:
  list: {description: List installed Ollama models., target: Codejitsu\Ai\Commands\Models::list}
  search: {description: Search model Catalogs., usage: 'search <query>', target: Codejitsu\Ai\Commands\Models::search}
  show: {description: Show a Model recipe and status., usage: 'show <name-or-uri>', target: Codejitsu\Ai\Commands\Models::show}
  build: {description: Build a Model recipe., usage: 'build <name-or-uri>', target: Codejitsu\Ai\Commands\Models::build}
  ensure: {description: Interactively ensure a Model is available., usage: 'ensure [name-or-uri]', target: Codejitsu\Ai\Commands\Models::ensure}
  test: {description: Smoke-test an Ollama model., usage: 'test [name]', target: Codejitsu\Ai\Commands\Models::test}
