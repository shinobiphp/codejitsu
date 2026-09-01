name: catalog
type: command
description: Inspect and manage Catalog Scrolls.
usage: 'catalog:<subcommand> [arguments]'
commands:
  list:
    description: List configured Catalog Scrolls.
    capability: capability://catalog-list
  show:
    description: Show a Catalog Scroll and its entries.
    usage: 'catalog:show <name-or-uri>'
    schema: schema://catalog-name
    capability: capability://catalog-show
  search:
    description: Search entries across Catalog Scrolls.
    usage: 'catalog:search <query> [kind]'
    schema: schema://catalog-search
    capability: capability://catalog-search
  edit:
    description: Interactively manage writable project catalogs.
    usage: 'catalog:edit'
    capability: capability://catalog-edit
