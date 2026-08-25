name: make
type: command
description: Create new Scroll resources.
usage: 'make:<subcommand> [arguments] [options]'
commands:
    scroll:
        description: Create a Scroll from a URI, or interactively when no URI is supplied.
        usage: 'make:scroll [<uri>] [--target=<callable>] [--source=<code>] [--substrate=<name>]'
        capability: capability://make-scroll
