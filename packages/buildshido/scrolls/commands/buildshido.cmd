name: buildshido
type: command
version: 1.0.0
description: Run Buildshido and compile from a Spec Scroll.
commands:
  build: {description: Build from a Spec Scroll., usage: 'build <spec-scroll> [--out-dir=path] [--dry-run]', capability: capability://buildshido-build}
