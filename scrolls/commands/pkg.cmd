name: pkg
type: command
description: Manage Composer packages used by Codejitsu.
usage: 'pkg:<subcommand> [package]'
commands:
    list:
        description: List installed Composer requirements.
        usage: 'pkg:list'
        capability: capability://pkg-list
    info:
        description: Show Composer metadata for a package.
        usage: 'pkg:info <package>'
        capability: capability://pkg-info
    install:
        description: Install a Composer package.
        usage: 'pkg:install <package>'
        capability: capability://pkg-install
    remove:
        description: Remove a Composer package.
        usage: 'pkg:remove <package>'
        capability: capability://pkg-remove
    update:
        description: Update a Composer package or the project.
        usage: 'pkg:update [package]'
        capability: capability://pkg-update
