name: context.search
description: Search Context Scroll names and content available to this session.
capability: capability://ai-context-search
inputSchema: {type: object, required: [query], additionalProperties: false, properties: {query: {type: string}}}
consequential: false
