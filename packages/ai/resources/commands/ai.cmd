name: ai
type: command
version: 1.0.0
description: Run and interact with AI Vessels.
commands:
  run: {description: Run a Vessel once., usage: 'run <vessel> [--spark=name] [--provider=name] [--model=name] [--skill=name] [--input=query] [--approve-tool=name] <prompt>', capability: capability://ai-run}
  tui: {description: Open the interactive Vessel console., capability: capability://ai-tui}
