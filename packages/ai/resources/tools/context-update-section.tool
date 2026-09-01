name: context.update-section
description: Replace one existing managed section in an allowed Context Scroll.
capability: capability://ai-context-update-section
inputSchema:
  type: object
  required: [name, section, content]
  additionalProperties: false
  properties:
    name: {type: string}
    section: {type: string, pattern: '^[a-z0-9_-]+$'}
    content: {type: string, minLength: 1}
consequential: true
maxRuns: 1
