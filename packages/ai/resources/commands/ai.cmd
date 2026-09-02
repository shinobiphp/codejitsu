name: ai
type: command
version: 1.0.0
description: Run and interact with AI Vessels.
commands:
  run: {description: Run a Vessel once., usage: 'run <vessel> <prompt> [--approve-tool=name]', target: Codejitsu\Ai\Commands\Ai::run}
  tui: {description: Open the interactive Vessel console., target: Codejitsu\Ai\Commands\Ai::tui}
