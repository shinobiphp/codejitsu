name: spark
type: command
version: 1.0.0
description: Inspect Sparks.
commands:
  list: {description: List Sparks., capability: capability://spark-list}
  show: {description: Show a Spark., usage: 'show <name-or-uri>', capability: capability://spark-show}
