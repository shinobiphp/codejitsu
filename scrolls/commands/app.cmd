name: app
type: command
description: Compose and validate applications.
usage: 'app:<subcommand> [arguments]'
commands:
    list:
        description: List App Scrolls.
        capability: capability://app-list
    validate:
        description: Validate an effective App.
        usage: 'app:validate <app-uri>'
        capability: capability://app-validate
    show:
        description: Show an effective App definition.
        usage: 'app:show <app-uri>'
        capability: capability://app-show
