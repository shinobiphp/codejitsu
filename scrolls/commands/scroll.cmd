name: scroll
type: command
description: Manage and execute Scrolls.
usage: 'scroll:<subcommand> [arguments] [options]'
commands:
    sign:
        description: Sign one Scroll or every discovered Scroll.
        usage: 'scroll:sign <uri|--all>'
        capability: capability://scroll-sign
    seal:
        description: Seal one Scroll or every discovered Scroll after signature verification.
        usage: 'scroll:seal <uri|--all>'
        capability: capability://scroll-seal
    unseal:
        description: Unseal one Scroll or every discovered Scroll while preserving signatures.
        usage: 'scroll:unseal <uri|--all>'
        capability: capability://scroll-unseal
    verify:
        description: Verify the signature of one Scroll or every discovered Scroll.
        usage: 'scroll:verify <uri|--all>'
        capability: capability://scroll-verify
    run:
        description: Execute a Scroll by URI.
        usage: 'scroll:run <uri> [arguments]'
        capability: capability://scroll-run
