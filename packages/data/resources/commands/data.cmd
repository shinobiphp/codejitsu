name: data
type: command
version: 1.0.0
description: 'Compile and inspect declarative data graphs.'
commands:
  build: {description: 'Compile an Entity Scroll into PHP data artifacts.', usage: 'build <entity> [--out-dir=path] [--namespace=namespace]', capability: capability://data-build}
