name: spec
type: command
description: Inspect and validate specifications.
usage: 'spec:<subcommand> [arguments]'
commands:
    list:
        description: List Spec Scrolls.
        capability: capability://spec-list
    validate:
        description: Validate a subject against a Spec.
        usage: 'spec:validate <spec-uri> [subject-uri]'
        capability: capability://spec-validate
