name: pkg
type: command
description: Manage Composer packages used by Codejitsu.
usage: 'pkg:<subcommand> [package]'
commands:
    list:
        description: List available and installed Codejitsu packages.
        usage: 'pkg:list'
        capability: capability://pkg-list
    info:
        description: Show catalog and installation metadata for a Codejitsu package.
        usage: 'pkg:info <package>'
        schema: schema://package
        capability: capability://pkg-info
    search:
        description: Search configured Codejitsu package catalogs.
        usage: 'pkg:search <query>'
        capability: capability://pkg-search
    install:
        description: Install a Composer package.
        usage: 'pkg:install <package>'
        schema: schema://package
        capability: capability://pkg-install
    remove:
        description: Remove a Composer package.
        usage: 'pkg:remove <package>'
        schema: schema://package
        capability: capability://pkg-remove
    uninstall:
        description: Uninstall a Composer package.
        usage: 'pkg:uninstall <package>'
        schema: schema://package
        capability: capability://pkg-uninstall
    update:
        description: Update a Composer package or the project.
        usage: 'pkg:update [package]'
        capability: capability://pkg-update
    cache:
        description: Manage the compiled package cache.
        commands:
            status:
                description: Show package cache status.
                capability: capability://pkg-cache-status
            rebuild:
                description: Rebuild the package cache.
                capability: capability://pkg-cache-rebuild
            clear:
                description: Clear the package cache.
                capability: capability://pkg-cache-clear
