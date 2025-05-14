class ApiResponses {
  constructor() {
    this.welcomeResponse = {
      response:
        'Welcome to the LaunchPad API Assistant. How can I help you with the API today?',
      sentiment: 'positive',
      quickReplies: [
        'What endpoints are available?',
        'How do I authenticate?',
        'Tell me about inventory management',
      ],
    };
    this.commonResponses = {
      'create user': `To create a new user account via the API, use the following endpoint:

\`POST /api/users\`

Request body parameters:
- \`name\` (string): The user's full name
- \`email\` (string): The user's email address
- \`password\` (string): The user's password
- \`role\` (string): The user's role/permission level (e.g., "admin", "manager", "user")

Example request:
\`\`\`json
{
  "name": "John Smith",
  "email": "john.smith@example.com",
  "password": "secure_password123",
  "role": "manager"
}
\`\`\`

Successful response will return the created user with status code 201.`,

      'add item': `To add a new inventory item to the system, use:

\`POST /api/items\`

Required parameters:
- \`name\` (string): The item name
- \`sku\` (string): Stock Keeping Unit - unique identifier
- \`category_id\` (integer): ID of the category this item belongs to

Optional parameters:
- \`description\` (string): Detailed item description
- \`unit_of_measure_id\` (integer): ID of the unit of measurement
- \`minimum_stock\` (integer): Minimum stock threshold for alerts
- \`image_url\` (string): URL to the item's image

Example:
\`\`\`json
{
  "name": "Ergonomic Office Chair",
  "sku": "CHAIR-ERG-001",
  "category_id": 5,
  "description": "Adjustable ergonomic office chair with lumbar support",
  "unit_of_measure_id": 1,
  "minimum_stock": 10
}
\`\`\``,

      'permission levels': `The LaunchPad API has several user permission levels:

1. **Admin**: Full system access including user management
   - Can access and modify all endpoints
   - Can create, update, and delete users
   - Can configure system settings

2. **Manager**: Can manage inventory but not users
   - Full access to inventory items, suppliers, and locations
   - Can view but not modify user accounts
   - Can generate all reports

3. **User**: Standard access for day-to-day operations
   - Can view inventory items and check in/out items
   - Can create and update items but not delete
   - Limited access to reports

4. **Viewer**: Read-only access
   - Can only view items, categories, and inventory status
   - Cannot make any changes
   
These permission levels can be set when creating a user with \`POST /api/users\` or updated with \`PUT /api/users/{id}\`.`,

      authenticate: `To authenticate with the LaunchPad API, you need to:

1. Obtain an API token using your credentials:
   \`POST /api/auth/login\`
   
   Request body:
   \`\`\`json
   {
     "email": "your.email@example.com",
     "password": "your_password"
   }
   \`\`\`
   
2. The server will respond with an access token:
   \`\`\`json
   {
     "access_token": "eyJhbGciOiJIUzI1NiIsInR5...",
     "token_type": "Bearer",
     "expires_in": 3600
   }
   \`\`\`
   
3. Include this token in all subsequent requests in the Authorization header:
   \`Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5...\``,
    };
  }
  detectTopic(userMessage) {
    const message = userMessage.toLowerCase();

    if (
      message.includes('item') ||
      message.includes('product') ||
      message.includes('inventory')
    ) {
      return 'items';
    } else if (
      message.includes('category') ||
      message.includes('classification')
    ) {
      return 'categories';
    } else if (message.includes('supplier') || message.includes('vendor')) {
      return 'suppliers';
    } else if (
      message.includes('location') ||
      message.includes('place') ||
      message.includes('where')
    ) {
      return 'locations';
    } else if (
      message.includes('user') ||
      message.includes('account') ||
      message.includes('login')
    ) {
      return 'users';
    } else {
      return 'general';
    }
  }

  generateQuickRepliesForTopic(topic) {
    switch (topic) {
      case 'items':
        return [
          'How do I create a new item?',
          'How can I update item quantities?',
        ];
      case 'categories':
        return [
          'How do I list all categories?',
          'How do I assign items to categories?',
        ];
      case 'suppliers':
        return [
          'How do I add a new supplier?',
          'How do I view items from a specific supplier?',
        ];
      case 'locations':
        return [
          'How do I add a new location?',
          'How do I move items between locations?',
        ];
      case 'users':
        return [
          'How do I create a new user account?',
          'What are the user permission levels?',
        ];
      default:
        return [
          'What API endpoints are available?',
          'How do I authenticate with the API?',
        ];
    }
  }
  generateContextualQuickReplies(userMessage) {
    if (!userMessage || userMessage.trim() === '') {
      return [
        'What endpoints are available?',
        'How to make API requests?',
        'Show me authentication methods',
      ];
    }
    const topic = this.detectTopic(userMessage);
    return this.generateQuickRepliesForTopic(topic);
  }

  getApiInfo(topic, userMessage) {
    let content =
      'The LaunchPad Inventory Management API provides endpoints for managing inventory items, categories, suppliers, and locations. Here are some key endpoints:\n\n';

    switch (topic) {
      case 'items':
        content =
          'Here are the main endpoints for working with inventory items:\n\n' +
          'Get all items\n`GET /api/items`\n' +
          'Get a specific item\n`GET /api/items/{id}`\n' +
          'Create a new item\n`POST /api/items`\n' +
          'Update an item\n`PUT /api/items/{id}`\n' +
          'Delete an item\n`DELETE /api/items/{id}`\n';
        break;
      case 'categories':
        content =
          'Here are the main endpoints for working with categories:\n\n' +
          'Get all categories\n`GET /api/categories`\n' +
          'Get a specific category\n`GET /api/categories/{id}`\n' +
          'Create a new category\n`POST /api/categories`\n' +
          'Update a category\n`PUT /api/categories/{id}`\n' +
          'Delete a category\n`DELETE /api/categories/{id}`\n';
        break;
      case 'suppliers':
        content =
          'Here are the main endpoints for working with suppliers:\n\n' +
          'Get all suppliers\n`GET /api/suppliers`\n' +
          'Get a specific supplier\n`GET /api/suppliers/{id}`\n' +
          'Create a new supplier\n`POST /api/suppliers`\n' +
          'Update a supplier\n`PUT /api/suppliers/{id}`\n' +
          'Delete a supplier\n`DELETE /api/suppliers/{id}`\n';
        break;
      case 'locations':
        content =
          'Here are the main endpoints for working with locations:\n\n' +
          'Get all locations\n`GET /api/locations`\n' +
          'Get a specific location\n`GET /api/locations/{id}`\n' +
          'Create a new location\n`POST /api/locations`\n' +
          'Update a location\n`PUT /api/locations/{id}`\n' +
          'Delete a location\n`DELETE /api/locations/{id}`\n';
        break;
      case 'users':
        content =
          'Here are the main endpoints for user management:\n\n' +
          'Get all users\n`GET /api/users`\n' +
          'Get a specific user\n`GET /api/users/{id}`\n' +
          'Create a new user\n`POST /api/users`\n' +
          'Update a user\n`PUT /api/users/{id}`\n' +
          'Delete a user\n`DELETE /api/users/{id}`\n';
        break;
      default:
        content +=
          'Items\n' +
          'Get all items\n`GET /api/items`\n' +
          'Create a new item\n`POST /api/items`\n' +
          'Categories\n' +
          'Get all categories\n`GET /api/categories`\n' +
          'Create a new category\n`POST /api/categories`\n' +
          'Suppliers\n' +
          'Get all suppliers\n`GET /api/suppliers`\n' +
          'Create a new supplier\n`POST /api/suppliers`\n' +
          'Locations\n' +
          'Get all locations\n`GET /api/locations`\n' +
          'Create a new location\n`POST /api/locations`\n';
    }
    return { content };
  }
  matchesQuestion(userMessage, keywords) {
    if (!userMessage) return false;
    const message = userMessage.toLowerCase();
    return keywords.some((keyword) => message.includes(keyword));
  }

  getSpecificQuestionResponse(userMessage) {
    const message = userMessage.toLowerCase();

    if (
      this.matchesQuestion(message, [
        'create user',
        'new user',
        'add user',
        'register user',
      ])
    ) {
      return this.commonResponses['create user'];
    }

    if (
      this.matchesQuestion(message, [
        'permission',
        'role',
        'access level',
        'privileges',
        'user permission',
      ])
    ) {
      return this.commonResponses['permission levels'];
    }

    if (
      this.matchesQuestion(message, [
        'authenticate',
        'login',
        'token',
        'auth',
        'credentials',
      ])
    ) {
      return this.commonResponses['authenticate'];
    }

    if (
      this.matchesQuestion(message, [
        'add item',
        'create item',
        'new item',
        'insert item',
      ])
    ) {
      return this.commonResponses['add item'];
    }

    return null;
  }

  generateApiResponse(userMessage, isInFallbackMode, rateLimitedUntil) {
    if (
      !userMessage ||
      userMessage.toLowerCase().includes('welcome') ||
      userMessage.toLowerCase().includes('greeting')
    ) {
      return this.welcomeResponse;
    }

    const specificResponse = this.getSpecificQuestionResponse(userMessage);
    if (specificResponse) {
      return {
        response: specificResponse,
        sentiment: 'positive',
        quickReplies: this.generateContextualQuickReplies(userMessage),
      };
    }

    const topic = this.detectTopic(userMessage);
    const apiInfo = this.getApiInfo(topic, userMessage);
    let rateMessage = '';

    if (isInFallbackMode) {
      if (Date.now() < rateLimitedUntil) {
        const minutesRemaining = Math.ceil(
          (rateLimitedUntil - Date.now()) / 60000,
        );
        rateMessage =
          "📢 **Notice**: I'm currently in API documentation mode due to service limitations. " +
          "I'm providing information directly from the LaunchPad API documentation. " +
          (minutesRemaining > 0
            ? `Full service should resume in approximately ${minutesRemaining} minutes.\n\n`
            : '\n\n');
      } else {
        rateMessage =
          "I'm using my built-in API documentation to answer your question:\n\n";
      }
    }

    return {
      response: rateMessage + apiInfo.content,
      sentiment: 'neutral',
      quickReplies: this.generateQuickRepliesForTopic(topic),
    };
  }
}

window.ApiResponses = new ApiResponses();
