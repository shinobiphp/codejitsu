name: context
type: command
description: Manage deterministic project memory.
usage: 'context:<subcommand> [arguments]'
commands:
    list:
        description: List indexed Context Scrolls.
        capability: capability://context-list
    show:
        description: Show a Context Scroll.
        usage: 'context:show <name-or-uri>'
        capability: capability://context-show
    search:
        description: Search Context names and content.
        usage: 'context:search <query>'
        capability: capability://context-search
    check:
        description: Validate Context memory.
        capability: capability://context-check
    sync:
        description: Replace an explicitly managed section.
        usage: 'context:sync <section> <content>'
        capability: capability://context-sync
    resume:
        description: Render the current agent briefing.
        capability: capability://context-resume
