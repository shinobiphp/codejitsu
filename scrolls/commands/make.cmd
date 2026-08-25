name: make
type: command
description: Create new Scroll resources.
usage: 'make:<subcommand> [arguments] [options]'
commands:
    scroll:
        description: Create a Scroll from a URI.
        usage: 'make:scroll <uri> [--target=<callable>]'
        capability: capability://make-scroll
