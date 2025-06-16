class AIService {
  constructor() {
    this.baseUrl = 'https://openrouter.ai/api/v1';
    this.model = 'openai/gpt-3.5-turbo';
    this.siteInfo = {
      referer: window.location.origin,
      title: 'API Assistant',
    };
    this.rateLimitedUntil = 0;
    this.rateLimitBackoffPeriod = 10 * 60 * 1000;

    this.isInFallbackMode = true;
    this.apiKey = '';
    this.loadApiKey();

    // Swagger data
    this.swaggerData = null;
    this.hasLoadedSwagger = false;
    this.loadSwaggerData();

    // Base system message
    this.systemMessage = `You are an API assistant for the Inventory Management System. You have comprehensive knowledge of all API endpoints, authentication mechanisms, and usage patterns.

PERSONALITY:
- Be professional, but approachable and conversational
- Occasionally add a touch of humor or wit to keep conversations engaging
- Be confident and direct in your answers - users need accurate API information
- When a user seems frustrated, be extra helpful and solution-oriented
- Speak knowledgeably but avoid unnecessary technical jargon

FORMATTING GUIDELINES:
- Always format API endpoints with backticks: \`GET /api/items\`
- Always use /api as the base path (all endpoints must start with /api/)
- NEVER USE BULLET POINTS FOR PARAMETERS (VERY IMPORTANT)
- Use markdown code blocks with language specifiers for examples:
  \`\`\`javascript
  fetch('/api/items', {
    method: 'GET',
    headers: {
      'Authorization': 'Bearer YOUR_TOKEN'
    }
  })
  .then(response => response.json())
  .then(data => console.log(data));
  \`\`\`
- Use **bold** for important terms or concepts

ENDPOINT LISTING RULES (EXTREMELY IMPORTANT):
- NEVER EVER use bullet points, dashes, asterisks or any symbol when listing endpoints
- NEVER use numbered lists when listing endpoints
- ALWAYS list endpoints in plain text format without any prefixes
- For each endpoint, first put the description on its own line
- Then put the method and path in backticks on the next line
- Add a blank line between each endpoint


CORRECT WAY TO LIST ENDPOINTS (EXAMPLE):

Get a list of all users
\`GET /api/users\`

Create a new user
\`POST /api/users\` 

Get details for a specific user
\`GET /api/users/{id}\` 

Update a user
\`PUT /api/users/{id}\` 

Delete a user
\`DELETE /api/users/{id}\` 

NEVER EVER list endpoints like this (incorrect):
- \`GET /api/users\`: Get all users
* \`POST /api/users\`: Create a user
1. \`GET /api/users/{id}\`: Get a user
\`GET /api/users\` - Get a list of all users

NEVER use bullet points for showing parameters (extremly important):

Parameters:
param1: description
param2: description

ENDPOINT DOCUMENTATION:
When describing endpoints, use this clear structure:

Brief description of what the endpoint does.

\`METHOD /api/path\`


Parameters:
param1: description
param2: description

Example response:
\`\`\`json
{
  "success": true,
  "data": {}
}
\`\`\`

COMPREHENSIVE RESPONSES:
When asked about endpoints, ALWAYS provide a comprehensive list of ALL available endpoints relevant to the question. This includes authentication endpoints, category endpoints, user endpoints, stock endpoints, UOM endpoints, checkInOut endpoints, maintenance endpoints, and any other relevant endpoints.

QUICK REPLIES FORMAT (EXTREMELY IMPORTANT):
- You MUST use bullet points (dashes) for quick replies
- Each quick reply MUST start with a dash (-) followed by a space
- The UI REQUIRES the bullet points to properly detect and display quick replies
- Always include the section title "Quick Replies:"
- Format EXACTLY as shown in the examples below

Quick Replies:
- [Specific, contextual follow-up question 1]
- [Specific, contextual follow-up question 2]
- [Specific, contextual follow-up question 3]

EXAMPLES OF GOOD QUICK REPLIES:
If user asked about authentication:

Quick Replies:
- How do I refresh an expired token?
- What endpoints require authentication?
- Can I test authentication without a real account?

If user asked about item endpoints:

Quick Replies:
- How do I filter items by category?
- What's the format for creating a new item?
- Can I bulk update multiple items?`;
  }

  async loadApiKey() {
    try {
      const response = await fetch('/api/get-env-key');
      if (response.ok) {
        const data = await response.json();
        if (data.apiKey) {
          this.apiKey = data.apiKey;
          this.isInFallbackMode = false;
        } else {
          console.error('API key not found');
        }
      }
    } catch (error) {
      console.error('Error loading API key:', error);
    }
  }

  async loadSwaggerData() {
    try {
      const response = await fetch('/swagger/openapi.json');
      if (response.ok) {
        const yamlText = await response.text();
        this.swaggerData = this.extractApiInfo(yamlText);
        this.hasLoadedSwagger = true;
        this.enhanceSystemMessage();
      }
    } catch (error) {
      console.error('Error loading Swagger data:', error);
    }
  }

  extractApiInfo(yamlText) {
    try {
      const titleMatch = yamlText.match(/title:\s*([^\n]+)/);
      const descriptionMatch = yamlText.match(
        /description:\s*\|([^#]+?)\n\s*version:/s,
      );

      const info = {
        title: titleMatch ? titleMatch[1].trim() : 'Inventory Management API',
        description: descriptionMatch
          ? descriptionMatch[1].trim().replace(/\n\s{2,}/g, '\n')
          : 'REST API for managing inventory items',
        endpoints: {},
      };

      const pathRegex = /\/([^:\s]+):([\s\S]*?)(?=^\/|\s*$)/gm;
      let pathMatch;

      while ((pathMatch = pathRegex.exec(yamlText)) !== null) {
        const path = '/' + pathMatch[1].trim();
        const pathContent = pathMatch[2];

        const methods = {};
        const methodRegex =
          /(get|post|put|delete|patch):([\s\S]*?)(?=^\s{2,}(get|post|put|delete|patch):|$)/gim;
        let methodMatch;

        while ((methodMatch = methodRegex.exec(pathContent)) !== null) {
          const method = methodMatch[1].toUpperCase();
          const methodContent = methodMatch[2];

          const summary =
            (methodContent.match(/summary:\s*([^\n]+)/) || [])[1] || '';
          const description =
            (methodContent.match(/description:\s*([^\n]+)/) || [])[1] || '';

          methods[method] = {
            summary: summary.trim(),
            description: description.trim(),
          };
        }

        if (Object.keys(methods).length > 0) {
          info.endpoints[path] = { methods };
        }
      }

      return info;
    } catch (error) {
      console.warn('Error parsing API info:', error);
      return {
        title: 'Inventory Management API',
        description: 'REST API for managing inventory',
        endpoints: {},
      };
    }
  }

  enhanceSystemMessage() {
    if (!this.swaggerData) return;

    let apiInfo = `\n\nABOUT THE API:\nTitle: ${this.swaggerData.title}\nDescription: ${this.swaggerData.description}\n\nAVAILABLE ENDPOINTS:`;

    for (const path in this.swaggerData.endpoints) {
      apiInfo += `\n${path}`;

      const endpoint = this.swaggerData.endpoints[path];
      for (const method in endpoint.methods) {
        const info = endpoint.methods[method];
        apiInfo += `\n- \`${method} ${path}\` - ${info.summary}`;
      }
    }
  }

  formatResponse(aiResponse, userMessage) {
    let cleanedResponse = aiResponse;
    let quickReplies = [];

    const quickRepliesPattern =
      /(?:Quick Replies:|Suggested Questions:|You could ask:|Try asking about:)[\s]*((?:\s*-\s*[^\n]+\s*)+)$/i;
    const suggestionsMatch = aiResponse.match(quickRepliesPattern);

    if (suggestionsMatch && suggestionsMatch[1]) {
      quickReplies = suggestionsMatch[1]
        .split(/\s*-\s*/)
        .map((s) => s.trim())
        .filter((s) => s && s.length > 0 && s.length < 100)
        .slice(0, 4);

      cleanedResponse = aiResponse.replace(quickRepliesPattern, '').trim();
    }

    return {
      response: cleanedResponse,
      sentiment: 'neutral',
      quickReplies: quickReplies,
    };
  }

  prepareHistory(chatHistory) {
    const messages = [{ role: 'system', content: this.systemMessage }];

    for (const message of chatHistory) {
      if (message.sender === 'user') {
        messages.push({ role: 'user', content: message.text });
      } else if (message.sender === 'assistant') {
        messages.push({ role: 'assistant', content: message.text });
      }
    }

    return messages;
  }

  async getResponse(userMessage, chatHistory = []) {
    try {
      if (
        (this.isInFallbackMode && !this.apiKey) ||
        Date.now() < this.rateLimitedUntil
      ) {
        return {
          response:
            "I'm sorry, but I've reached my query limit. Please try again later or check our documentation for information about the API.",
          sentiment: 'neutral',
          quickReplies: [],
        };
      }

      const chatHistoryCopy = [...chatHistory];
      chatHistoryCopy.push({ sender: 'user', text: userMessage });
      const messages = this.prepareHistory(chatHistoryCopy);

      const relevantInfo = this.findRelevantApiInfo(userMessage);
      if (relevantInfo) {
        messages.push({
          role: 'system',
          content: `Here is information about endpoints relevant to the query: ${relevantInfo}`,
        });
      }

      const response = await this.makeApiRequest({
        model: this.model,
        messages: messages,
        max_tokens: 500,
        temperature: 0.7,
      });

      this.isInFallbackMode = false;
      return this.formatResponse(response, userMessage);
    } catch (error) {
      console.error('Error getting AI response:', error);

      this.isInFallbackMode = true;

      if (this.isRateLimitError(error)) {
        this.rateLimitedUntil = Date.now() + this.rateLimitBackoffPeriod;
      }

      return {
        response:
          "I'm sorry, but I've reached my query limit. Please try again later or check our documentation for information about the API.",
        sentiment: 'neutral',
        quickReplies: [],
      };
    }
  }

  findRelevantApiInfo(userMessage) {
    if (!this.swaggerData || !this.swaggerData.endpoints) return null;
    const userQuery = userMessage.toLowerCase();
    let relevantPaths = [];
    for (const path in this.swaggerData.endpoints) {
      const endpoint = this.swaggerData.endpoints[path];
      const pathLower = path.toLowerCase();
      if (pathLower.includes(userQuery)) {
        relevantPaths.push({ path, endpoint });
        continue;
      }
      for (const method in endpoint.methods) {
        const info = endpoint.methods[method];
        if (info.summary && info.summary.toLowerCase().includes(userQuery)) {
          relevantPaths.push({ path, endpoint });
          break;
        }
      }
    }
    if (relevantPaths.length === 0) return null;
    return relevantPaths
      .slice(0, 3)
      .map(({ path, endpoint }) => {
        const methods = Object.entries(endpoint.methods)
          .map(([method, info]) => `\`${method} ${path}\` - ${info.summary}`)
          .join('\n');
        return `${path}\n${methods}`;
      })
      .join('\n\n');
  }

  async makeApiRequest(payload) {
    if (!this.apiKey) {
      throw new Error('API key missing');
    }

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
      if (response.status === 429) {
        throw new Error('Rate limited');
      } else if (response.status === 401 || response.status === 403) {
        throw new Error('API key invalid');
      }
      throw new Error(`API error: ${response.status}`);
    }

    const completion = await response.json();

    if (completion.error) {
      if (
        completion.error.code === 429 ||
        completion.error.message?.includes('Rate limit')
      ) {
        throw new Error('Rate limited');
      }
      throw new Error('API returned an error');
    }

    if (completion.choices?.[0]?.message?.content) {
      return completion.choices[0].message.content;
    } else if (completion.choices?.[0]?.text) {
      return completion.choices[0].text;
    } else if (completion.output) {
      return typeof completion.output === 'string'
        ? completion.output
        : JSON.stringify(completion.output);
    }

    throw new Error('Invalid response format');
  }

  isRateLimitError(error) {
    return (
      error.message &&
      (error.message.includes('Rate limit') ||
        error.message.includes('rate limit') ||
        error.message.includes('429') ||
        error.message.includes('too many requests') ||
        error.message.includes('quota exceeded') ||
        error.message.includes('exceeded') ||
        error.message.includes('throttl'))
    );
  }
}

window.AIService = new AIService();
