name: make
type: command
description: Create new Scroll resources.
usage: 'make:<subcommand> [arguments] [options]'
commands:
    scroll:
        description: 'Create a Scroll from a URI, or interactively when no URI is supplied.'
        usage: 'make:scroll [<uri>] [--target=<callable>] [--source=<code>] [--substrate=<name>]'
        capability: capability://make-scroll
    context:
        description: Create and edit a project Context Scroll.
        usage: 'make:context <name>'
        capability: capability://make-context
    catalog:
        description: Create a project Catalog Scroll.
        usage: 'make:catalog <name>'
        capability: capability://make-catalog
    pkg:
        description: Scaffold an uninstalled Codejitsu package and catalog it.
        usage: 'make:pkg <vendor/name> [description]'
        capability: capability://make-pkg
