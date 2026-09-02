name: model
type: command
version: 1.0.0
description: 'Discover, build, and test AI models.'
commands:
  list: {description: List installed Ollama models., capability: capability://model-list}
  search: {description: Search model Catalogs., usage: 'search <query>', capability: capability://model-search}
  show: {description: Show a Model recipe and status., usage: 'show <name-or-uri>', capability: capability://model-show}
  build: {description: Build a Model recipe., usage: 'build <name-or-uri>', capability: capability://model-build}
  ensure: {description: Interactively ensure a Model is available., usage: 'ensure [name-or-uri]', capability: capability://model-ensure}
  test: {description: Smoke-test an Ollama model., usage: 'test [name]', capability: capability://model-test}
