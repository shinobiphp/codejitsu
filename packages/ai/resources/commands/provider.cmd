name: provider
type: command
version: 1.0.0
description: Inspect and validate Provider profiles.
commands:
  list: {description: List Provider profiles., capability: capability://provider-list}
  show: {description: Show a non-secret Provider profile., usage: 'show <name-or-uri>', capability: capability://provider-show}
  test: {description: Validate Provider configuration without a model request., usage: 'test <name-or-uri>', capability: capability://provider-test}
