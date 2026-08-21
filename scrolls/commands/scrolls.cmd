name: scrolls
type: command
description: Manage Scroll resources.
usage: 'scrolls:<subcommand> [arguments] [options]'
commands:
    hello:
        description: Say hello through a nested Command Scroll.
        usage: 'scrolls:hello [name]'
        schema: schema://hello
        capability: capability://hello
