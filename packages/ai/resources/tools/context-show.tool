name: context.show
description: Read one Context Scroll available to this session.
capability: capability://ai-context-show
inputSchema: {type: object, required: [name], additionalProperties: false, properties: {name: {type: string}}}
consequential: false
