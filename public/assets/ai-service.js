class AIService {
  constructor() {
    this.apiKey = window.ENV?.OPENROUTER_API_KEY || '';
    this.baseUrl = 'https://openrouter.ai/api/v1';
    this.model = 'meta-llama/llama-4-scout:free';
    this.siteInfo = {
      referer: window.location.origin,
      title: 'LaunchPad API Assistant',
    };
    this.rateLimitedUntil = 0;
    this.rateLimitBackoffPeriod = 10 * 60 * 1000;
    this.isInFallbackMode = false;
    if (!window.ApiResponses) console.error('ApiResponses module not loaded');
    if (!window.FormattingUtils)
      console.error('FormattingUtils module not loaded');
    this.swaggerData = null;
    this.hasLoadedSwagger = false;
    this.loadSwaggerData();

    this.systemMessage = `You are the LaunchPad API Assistant, an expert on the LaunchPad Inventory Management API. Provide concise, focused responses to user questions about the API.

RESPONSE FORMATTING:
Always format API endpoints using Markdown backticks like \`GET /api/items\` (never without the backticks)
Always use /api as the base path for all endpoints, never use domain names
Every endpoint MUST start with /api/ not just a slash (e.g., use \`GET /api/items\` not \`GET /items\`)
When listing multiple steps or items, use numbered lists with each number on its own line
When listing API endpoints grouped by categories (like "Items", "Categories", "Suppliers"):
  List the category name as a heading
  Under each category heading, list its associated API operations
  Each operation should be on a new line with the format: "Brief description \`METHOD /api/path\`"

For code samples, use triple backticks with the language specifier
Keep responses focused on the API documentation and endpoints`;
  }

  async loadSwaggerData() {
    try {
      const response = await fetch('/swagger/openapi.yaml');
      if (!response.ok) return;

      const yamlText = await response.text();
      this.swaggerData = {
        endpoints: this.extractEndpointsFromYaml(yamlText),
        info: this.extractInfoFromYaml(yamlText),
      };

      this.hasLoadedSwagger = true;
      this.enhanceSystemMessageWithApiInfo();
    } catch (error) {}
  }

  extractEndpointsFromYaml(yamlText) {
    const endpoints = {};
    const pathRegex = /^(\s*)\/([^:]+):/gm;
    let pathMatch;

    while ((pathMatch = pathRegex.exec(yamlText)) !== null) {
      const indent = pathMatch[1].length;
      const path = '/' + pathMatch[2];
      const pathEndPos = this.findPathSectionEnd(
        yamlText,
        pathMatch.index + pathMatch[0].length,
        indent,
      );
      const pathSection = yamlText.substring(pathMatch.index, pathEndPos);
      const pathTagsMatch = pathSection.match(/tags:\s*\n\s*-\s*([^\n]+)/);
      const pathTags = pathTagsMatch ? [pathTagsMatch[1].trim()] : [];
      const methodRegex = /^\s{2,}(get|post|put|delete|patch):/gim;
      let methodMatch;

      const pathInfo = {
        path,
        tags: pathTags,
        methods: {},
      };

      while ((methodMatch = methodRegex.exec(pathSection)) !== null) {
        const method = methodMatch[1].toLowerCase();
        const methodIndent = methodMatch[0].match(/^\s+/)[0].length;
        const methodEndPos = this.findPathSectionEnd(
          pathSection,
          methodMatch.index + methodMatch[0].length,
          methodIndent,
        );
        const methodSection = pathSection.substring(
          methodMatch.index,
          methodEndPos,
        );

        const descriptionMatch = methodSection.match(
          /description:\s*([^\n]+)(?:\n\s+([^\n]+))?/,
        );
        const description = descriptionMatch
          ? descriptionMatch[2]
            ? `${descriptionMatch[1]} ${descriptionMatch[2]}`
            : descriptionMatch[1]
          : '';

        const summaryMatch = methodSection.match(/summary:\s*([^\n]+)/);
        const summary = summaryMatch ? summaryMatch[1] : '';

        const methodTagsMatch = methodSection.match(
          /tags:\s*\n\s*-\s*([^\n]+)/,
        );
        const methodTags = methodTagsMatch
          ? [methodTagsMatch[1].trim()]
          : pathTags;

        pathInfo.methods[method] = {
          summary,
          description,
          tags: methodTags,
          parameters: this.extractParameters(methodSection),
          requestBody: this.extractRequestBodyProps(methodSection),
          responses: this.extractResponses(methodSection),
        };
      }

      endpoints[path] = pathInfo;
    }

    return endpoints;
  }

  extractInfoFromYaml(yamlText) {
    const titleMatch = yamlText.match(/title:\s*([^\n]+)/);
    const descriptionMatch = yamlText.match(
      /description:\s*\|([^#]+?)\n\s*version:/s,
    );

    return {
      title: titleMatch ? titleMatch[1].trim() : 'Inventory Management API',
      description: descriptionMatch
        ? descriptionMatch[1].trim().replace(/\n\s{2,}/g, '\n')
        : 'REST API for managing inventory items, locations, categories, and suppliers.',
    };
  }
  findPathSectionEnd(text, startPos, baseIndent) {
    const lines = text.substring(startPos).split('\n');
    let endPos = startPos;
    for (let i = 0; i < lines.length; i++) {
      const line = lines[i];
      if (line.trim() === '') {
        endPos += line.length + 1;
        continue;
      }
      const lineIndent = line.match(/^\s*/)[0].length;
      if (lineIndent <= baseIndent && i > 0) break;
      endPos += line.length + 1;
    }
    return endPos;
  }

  extractRequestBodyProps(methodText) {
    const props = [];
    const requestBodySection = methodText.match(
      /requestBody:[\s\S]*?properties:([\s\S]*?)(?:required:|responses:|$)/,
    );
    if (!requestBodySection) return props;

    const propertyRegex =
      /\s{2,}(\w+):\s*\n\s+type:\s+(\w+)(?:[\s\S]*?description:\s+([^\n]+))?/g;
    let propMatch;
    while ((propMatch = propertyRegex.exec(requestBodySection[1])) !== null) {
      props.push({
        name: propMatch[1],
        type: propMatch[2],
        description: propMatch[3] ? propMatch[3] : '',
      });
    }
    return props;
  }
  extractParameters(methodText) {
    const parameters = [];
    const parametersSection = methodText.match(
      /parameters:([\s\S]*?)(?:responses:|requestBody:|$)/,
    );
    if (!parametersSection) return parameters;

    const paramRegex =
      /\s+-\s+in:\s+(\w+)\s+name:\s+(\w+)(?:[\s\S]*?description:\s+([^\n]+))?(?:[\s\S]*?schema:\s+[\s\S]*?type:\s+(\w+))?/g;
    let paramMatch;
    while ((paramMatch = paramRegex.exec(parametersSection[1])) !== null) {
      parameters.push({
        in: paramMatch[1],
        name: paramMatch[2],
        description: paramMatch[3] ? paramMatch[3].trim() : '',
        type: paramMatch[4] ? paramMatch[4].trim() : 'string',
      });
    }
    return parameters;
  }

  extractResponses(methodText) {
    const responses = {};
    const responsesSection = methodText.match(
      /responses:([\s\S]*?)(?:\n\s*\w+:|$)/,
    );
    if (!responsesSection) return responses;

    const responseRegex =
      /\s{2,}['"]?(\d+)['"]?:\s*\n\s+description:\s+([^\n]+)/g;
    let responseMatch;
    while ((responseMatch = responseRegex.exec(responsesSection[1])) !== null) {
      responses[responseMatch[1]] = responseMatch[2];
    }
    return responses;
  }

  enhanceSystemMessageWithApiInfo() {
    if (!this.swaggerData || !this.swaggerData.info) return;

    const endpointsByTag = {};
    Object.keys(this.swaggerData.endpoints).forEach((path) => {
      const endpoint = this.swaggerData.endpoints[path];

      Object.keys(endpoint.methods).forEach((method) => {
        const methodInfo = endpoint.methods[method];
        const tags =
          methodInfo.tags && methodInfo.tags.length
            ? methodInfo.tags
            : ['General'];

        tags.forEach((tag) => {
          if (!endpointsByTag[tag]) {
            endpointsByTag[tag] = [];
          }

          endpointsByTag[tag].push({
            method: method.toUpperCase(),
            path,
            summary: methodInfo.summary || 'No summary provided',
          });
        });
      });
    });
    const apiInfo = `
ABOUT THE API:
Title: ${this.swaggerData.info.title}
Description: ${this.swaggerData.info.description}

AVAILABLE ENDPOINTS BY CATEGORY:
${Object.keys(endpointsByTag)
  .map((tag) => {
    return `${tag}:
${endpointsByTag[tag]
  .map((endpoint) => `${endpoint.method} ${endpoint.path}: ${endpoint.summary}`)
  .join('\n')}`;
  })
  .join('\n\n')}

Use this information to provide accurate, detailed, and helpful responses about the API. When users ask about specific endpoints, provide detailed information about parameters, request/response formats, and usage examples.
`;

    this.systemMessage += apiInfo;
  }
  extractQuickReplies(text) {
    const quickReplies = [];
    const suggestionsMatch = text.match(
      /(?:suggested questions:|quick replies:|you could ask:|try asking about:)(.+?)$/is,
    );

    if (suggestionsMatch && suggestionsMatch[1]) {
      return suggestionsMatch[1]
        .split(/[•\-\*\n]+/)
        .map((s) => s.trim())
        .filter((s) => s && s.length > 0 && s.length < 60)
        .slice(0, 3);
    }
    return quickReplies;
  }

  classifySentiment(userMessage) {
    if (userMessage.includes('?')) {
      return 'confused';
    } else if (userMessage.includes('!')) {
      return 'positive';
    }
    return 'neutral';
  }
  formatResponse(aiResponse, userMessage) {
    const sentiment = this.classifySentiment(userMessage);

    aiResponse = aiResponse.replace(
      /(`GET|`POST|`PUT|`DELETE|`PATCH)[^\n]+\n\n+(?=\w+[^:]+:)/g,
      '$&\n',
    );

    if (
      window.FormattingUtils &&
      typeof window.FormattingUtils.standardizeApiEndpoints === 'function'
    ) {
      aiResponse = window.FormattingUtils.standardizeApiEndpoints(aiResponse);
    }

    let quickReplies = this.extractQuickReplies(aiResponse);

    if (quickReplies.length > 0) {
      aiResponse = aiResponse
        .replace(
          /(?:suggested questions:|quick replies:|you could ask:|try asking about:)(.+?)$/is,
          '',
        )
        .trim();
    }

    if (
      quickReplies.length === 0 &&
      window.ApiResponses &&
      typeof window.ApiResponses.generateContextualQuickReplies === 'function'
    ) {
      quickReplies =
        window.ApiResponses.generateContextualQuickReplies(userMessage);
    }

    return {
      response: aiResponse,
      sentiment: sentiment,
      quickReplies: quickReplies,
    };
  }

  findRelevantApiInfo(userMessage) {
    if (!this.swaggerData || !this.swaggerData.endpoints) {
      return null;
    }

    const userQuery = userMessage.toLowerCase();
    const relevantEndpoints = [];

    Object.keys(this.swaggerData.endpoints).forEach((path) => {
      const endpoint = this.swaggerData.endpoints[path];
      const normalizedPath = path.replace(/[{}]/g, '').toLowerCase();

      const pathParts = normalizedPath.split('/').filter((p) => p);
      const isPathRelevant = pathParts.some(
        (part) =>
          userQuery.includes(part) ||
          (part.endsWith('s') && userQuery.includes(part.slice(0, -1))) ||
          (!part.endsWith('s') && userQuery.includes(part + 's')),
      );

      const methods = Object.keys(endpoint.methods);
      const isMethodRelevant = methods.some(
        (method) =>
          userQuery.includes(method) ||
          (method === 'get' &&
            (userQuery.includes('list') ||
              userQuery.includes('find') ||
              userQuery.includes('get') ||
              userQuery.includes('fetch'))) ||
          (method === 'post' &&
            (userQuery.includes('create') ||
              userQuery.includes('add') ||
              userQuery.includes('insert') ||
              userQuery.includes('new'))) ||
          (method === 'put' &&
            (userQuery.includes('update') ||
              userQuery.includes('edit') ||
              userQuery.includes('modify') ||
              userQuery.includes('change'))) ||
          (method === 'delete' &&
            (userQuery.includes('delete') ||
              userQuery.includes('remove') ||
              userQuery.includes('destroy'))),
      );

      if (isPathRelevant || isMethodRelevant) {
        relevantEndpoints.push({
          path,
          methods: endpoint.methods,
          relevanceScore: isPathRelevant ? 2 : 1,
        });
      }
    });

    relevantEndpoints.sort((a, b) => b.relevanceScore - a.relevanceScore);

    if (relevantEndpoints.length > 0) {
      const topEndpoints = relevantEndpoints.slice(0, 3);

      return topEndpoints
        .map((endpoint) => {
          const methods = Object.keys(endpoint.methods);

          const methodInfo = methods
            .map((method) => {
              const info = endpoint.methods[method];

              const pathParams =
                info.parameters?.filter((p) => p.in === 'path') || [];
              const queryParams =
                info.parameters?.filter((p) => p.in === 'query') || [];

              const pathParamsInfo =
                pathParams.length > 0
                  ? `Path parameters: ${pathParams
                      .map((p) => `${p.name} (${p.type}): ${p.description}`)
                      .join('; ')}`
                  : '';

              const queryParamsInfo =
                queryParams.length > 0
                  ? `Query parameters: ${queryParams
                      .map((p) => `${p.name} (${p.type}): ${p.description}`)
                      .join('; ')}`
                  : '';

              const requestProps =
                info.requestBody && info.requestBody.length > 0
                  ? `Request body parameters: ${info.requestBody
                      .map((p) => `${p.name} (${p.type}): ${p.description}`)
                      .join('; ')}`
                  : '';

              const responseInfo = info.responses
                ? `Responses: ${Object.entries(info.responses)
                    .map(([code, desc]) => `${code} - ${desc}`)
                    .join('; ')}`
                : '';

              return `${method.toUpperCase()} ${endpoint.path}
Summary: ${info.summary || 'No summary provided'}
Description: ${info.description || 'No description provided'}
${pathParamsInfo ? pathParamsInfo + '\n' : ''}${
                queryParamsInfo ? queryParamsInfo + '\n' : ''
              }${requestProps ? requestProps + '\n' : ''}${responseInfo}`;
            })
            .join('\n\n');

          return methodInfo;
        })
        .join('\n\n---\n\n');
    }

    return null;
  }

  prepareHistory(chatHistory) {
    const messages = [];

    messages.push({
      role: 'system',
      content: this.systemMessage,
    });

    for (const message of chatHistory) {
      if (message.sender === 'user') {
        messages.push({
          role: 'user',
          content: message.text,
        });
      } else if (message.sender === 'assistant') {
        messages.push({
          role: 'assistant',
          content: message.text,
        });
      }
    }

    return messages;
  }
  async getResponse(userMessage, chatHistory = []) {
    try {
      if (Date.now() < this.rateLimitedUntil) {
        return this.generateApiResponse(userMessage);
      }

      await this.waitForSwaggerData();

      const relevantApiInfo = this.findRelevantApiInfo(userMessage);

      const chatHistoryCopy = [...chatHistory];
      chatHistoryCopy.push({ sender: 'user', text: userMessage });

      const messages = this.prepareHistory(chatHistoryCopy);

      if (relevantApiInfo) {
        messages.push({
          role: 'system',
          content: `Here is specific information about API endpoints relevant to the user's query:

${relevantApiInfo}

IMPORTANT FORMATTING REMINDERS:
Always format API endpoints with backticks like \`GET /api/items\`
Always use /api as the base path for all endpoints, never use domain names
Every endpoint MUST start with /api/ (e.g., use \`GET /api/items\` not \`GET /items\`)

Use this information to provide an accurate and specific response.`,
        });
      }

      const payload = {
        model: this.model,
        messages: messages,
        max_tokens: 2048,
        temperature: 0.7,
      };

      const response = await this.makeApiRequest(payload);

      this.isInFallbackMode = false;

      return this.formatResponse(response, userMessage);
    } catch (error) {
      this.isInFallbackMode = true;

      if (this.isRateLimitError(error)) {
        this.rateLimitedUntil = Date.now() + this.rateLimitBackoffPeriod;
      }
      return this.generateApiResponse(userMessage);
    }
  }
  async waitForSwaggerData() {
    if (this.hasLoadedSwagger) return;

    return new Promise((resolve) => {
      const checkInterval = setInterval(() => {
        if (this.hasLoadedSwagger) {
          clearInterval(checkInterval);
          resolve();
        }
      }, 500);

      setTimeout(() => {
        clearInterval(checkInterval);
        resolve();
      }, 5000);
    });
  }
  async makeApiRequest(payload) {
    const response = await fetch(`${this.baseUrl}/chat/completions`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${this.apiKey}`,
        'HTTP-Referer': this.siteInfo.referer,
        'X-Title': this.siteInfo.title,
      },
      body: JSON.stringify(payload),
    });

    if (!response.ok) {
      const errorData = await response.json();
      if (response.status === 429) {
        this.rateLimitedUntil = Date.now() + this.rateLimitBackoffPeriod;
        throw new Error('Rate limited');
      }
      throw new Error(
        `API error: ${response.status} - ${
          errorData.error?.message || JSON.stringify(errorData)
        }`,
      );
    }

    const completion = await response.json();
    if (completion.choices?.[0]?.message?.content) {
      return completion.choices[0].message.content;
    } else {
      throw new Error('Invalid response format');
    }
  }
  isRateLimitError(error) {
    return (
      error.message &&
      (error.message.includes('Rate limit') ||
        error.message.includes('429') ||
        error.message.includes('too many requests') ||
        error.message.includes('network') ||
        error.message.includes('timeout'))
    );
  }
  generateApiResponse(userMessage) {
    if (
      !window.ApiResponses ||
      typeof window.ApiResponses.generateApiResponse !== 'function'
    ) {
      return {
        response:
          "I'm sorry, but I'm currently unable to connect to the API service. Please try again later.",
        sentiment: 'neutral',
        quickReplies: [
          'What endpoints are available?',
          'How do I authenticate?',
          'Show me example requests',
        ],
      };
    }

    return window.ApiResponses.generateApiResponse(
      userMessage,
      this.isInFallbackMode,
      this.rateLimitedUntil,
    );
  }
}

window.AIService = new AIService();
