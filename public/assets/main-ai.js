class FormattingUtils {
  static standardizeApiEndpoints(text) {
    if (!text) return '';

    return text
      .replace(
        /\b(GET|POST|PUT|DELETE|PATCH)\s+\/(?!api)([\w\/-{}]+)/gi,
        '$1 /api/$2',
      )
      .replace(
        /`(GET|POST|PUT|DELETE|PATCH)\s+\/(?!api)([\w\/-{}]+)`/gi,
        '`$1 /api/$2`',
      );
  }

  static markdownToHtml(text) {
    if (!text) return '';

    let formattedText = text.replace(/^\s*\d+\.\s+/gm, '- ');
    formattedText = formattedText.replace(
      /(`GET|`POST|`PUT|`DELETE|`PATCH)[^`]+`\s*:/g,
      '$1`',
    );

    formattedText = formattedText.replace(
      /(GET|POST|PUT|DELETE|PATCH)\s*\n(\/api\/[^\s]+)\s*\n\s*\n([^\n]+)/gi,
      (match, verb, path, desc) => {
        const verbLower = verb.toLowerCase();
        return `<div class='endpoint-description'>${desc}</div><div class='swagger-ui'><div class='opblock opblock-${verbLower}'><div class='opblock-summary-method ${verbLower}'>${verb}</div><div class='opblock-summary-path'>${path}</div></div></div>`;
      },
    );

    // Existing endpoint formatting
    formattedText = formattedText.replace(
      /`(GET|POST|PUT|DELETE|PATCH)\s+([^`\n]+)`/gi,
      (match, verb, path) => {
        const verbLower = verb.toLowerCase();
        if (!path.startsWith('/api/') && !path.startsWith('http')) {
          path = `/api/${path.replace(/^\//, '')}`;
        }
        return `<div class='swagger-ui'><div class='opblock opblock-${verbLower}'><div class='opblock-summary-method ${verbLower}'>${verb}</div><div class='opblock-summary-path'>${path}</div></div></div>`;
      },
    );

    formattedText = formattedText.replace(
      /<\/div><\/div><\/div>/g,
      '</div></div></div><br>',
    );

    formattedText = formattedText.replace(
      /```(json|javascript|html|css|yaml|bash|xml)?\n([\s\S]*?)```/gm,
      (match, language, code) => {
        const lang = language || '';
        let highlightedCode = code.replace(/</g, '&lt;').replace(/>/g, '&gt;');

        if (lang.toLowerCase() === 'json') {
          highlightedCode = highlightedCode
            .replace(
              /"([^"]+)"(\s*:)/g,
              '<span class="key">"$1"</span><span class="colon">$2</span>',
            )
            .replace(/:\s*"([^"]+)"/g, ': <span class="string">"$1"</span>')
            .replace(/:\s*(-?\d+\.?\d*)/g, ': <span class="number">$1</span>')
            .replace(/:\s*(true|false)/g, ': <span class="boolean">$1</span>')
            .replace(/:\s*(null)/g, ': <span class="null">$1</span>')
            .replace(/([{}\[\]])/g, '<span class="bracket">$1</span>')
            .replace(/,/g, '<span class="comma">,</span>');
        }

        return `<pre class="code-block ${lang}">${highlightedCode}</pre>`;
      },
    );

    // Format inline code
    formattedText = formattedText.replace(
      /`([^`]+)`/g,
      (match, code) => `<code>${code}</code>`,
    );

    formattedText = formattedText
      .replace(/\*\*([^*]+)\*\*/g, (match, text) => `<strong>${text}</strong>`)
      .replace(/\*([^*]+)\*/g, (match, text) => `<em>${text}</em>`);

    // lists
    formattedText = formattedText
      .replace(/^\s*[\-\*]\s+(.+)$/gm, '<li>$1</li>')
      .replace(/(<li>.*<\/li>)\s+(<li>)/g, '$1$2');

    if (formattedText.includes('<li>')) {
      formattedText = '<ul>' + formattedText + '</ul>';
      formattedText = formattedText.replace(/<\/ul>\s*<ul>/g, '');
    }

    return formattedText;
  }

  // chat message element
  static createMessageElement(content, sender, messageId = '') {
    const icon = sender === 'assistant' ? '🤖' : '👤';
    const idAttribute = messageId ? ` id="${messageId}"` : '';

    if (sender === 'user') {
      const escapedContent = this.escapeHtml(content);
      return `<div${idAttribute} class="chat-message ${sender}"><span class="icon" aria-hidden="true">${icon}</span>${escapedContent}</div>`;
    }

    return `<div${idAttribute} class="chat-message ${sender}"><span class="icon" aria-hidden="true">${icon}</span>${content}</div>`;
  }

  static escapeHtml(html) {
    const escapeMap = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;',
    };
    return html.replace(/[&<>"']/g, (match) => escapeMap[match]);
  }

  static formatQuickReplies(replies) {
    if (!replies || !Array.isArray(replies) || replies.length === 0) return '';

    const quickRepliesHtml = replies
      .map(
        (reply) =>
          `<button class="quick-reply" onclick="sendMessage('${reply.replace(
            /'/g,
            "\\'",
          )}')">${reply}</button>`,
      )
      .join('');

    return `<div class="quick-replies">${quickRepliesHtml}</div>`;
  }
}

window.FormattingUtils = FormattingUtils;

/* Themes:
 * - 'default'
 * - 'alternate'
 * - 'moon'
 * - 'purple'
 * - 'solarized'
 * - 'bluePlanet'
 * - 'deepSpace'
 * - 'saturn'
 * - 'kepler'
 * - 'mars'
 * - 'laserwave'
 * - 'elysiajs'
 * - 'fastify'
 */

Scalar.createApiReference('#app', {
  url: '/swagger/openapi.json',
  theme: 'purple',
  hideDarkModeToggle: false,
  showSidebar: true,
  hideTestRequestButton: false,
  hideClientButton: true,
  withDefaultFonts: true,
  showAuthButtonInHeader: true,
  authPersistence: true,
});

document.addEventListener('DOMContentLoaded', function () {
  // typing effect for header
  const typingElement = document.getElementById('typing-text');
  if (typingElement) {
    const text = typingElement.textContent || '';
    typingElement.textContent = '>';
    let i = 0;
    const typingSpeed = 30;

    function typeNextCharacter() {
      if (i < text.length) {
        typingElement.textContent = '> ' + text.substring(0, i + 1);
        i++;
        setTimeout(typeNextCharacter, typingSpeed);
      } else {
        typingElement.classList.add('typing-complete');
      }
    }
    setTimeout(typeNextCharacter, 500);
  }

  setupEventListeners();

  // load previous chat history
  try {
    loadChatHistory();
  } catch (e) {
    console.warn('Error loading chat history:', e);
  }

  // Initialize chat if empty
  setTimeout(() => {
    const chat = document.getElementById('ai-chat');
    if (chat && !chat.querySelector('.chat-message')) {
      typeWelcomeMessage();
    }
  }, 500);
});

// Chat state
let isProcessing = false;
let chatHistory = [];

async function waitForAIServiceReady(maxWaitMs = 3000) {
  const start = Date.now();
  while (!window.AIService && Date.now() - start < maxWaitMs) {
    await new Promise((res) => setTimeout(res, 100));
  }
}

async function typeWelcomeMessage() {
  try {
    const chat = document.getElementById('ai-chat');
    if (!chat) return;
    document
      .querySelectorAll('.chat-message.assistant.welcome-message')
      .forEach((msg) => msg.remove());

    if (!window.AIService) {
      await waitForAIServiceReady();
    }

    const fallbackGreeting =
      'Welcome to the API Assistant. How can I help you today?';

    if (
      window.AIService &&
      typeof window.AIService.getResponse === 'function'
    ) {
      try {
        const welcomeResponse = await window.AIService.getResponse(
          'Provide a very short, friendly welcome greeting for users.',
          [],
        );
        await addMessageToChat(
          welcomeResponse.response,
          welcomeResponse.quickReplies || [],
          'assistant',
          true,
        );
        chatHistory.push({
          sender: 'assistant',
          text: welcomeResponse.response,
        });
        return;
      } catch (error) {
        console.error('Error getting AI welcome message:', error);
      }
    }

    await addMessageToChat(fallbackGreeting, [], 'assistant', true);
    chatHistory.push({ sender: 'assistant', text: fallbackGreeting });
  } catch (error) {
    console.error('Error in welcome message:', error);
  }
}

// Process user input
async function sendMessage(inputText) {
  const actionBtn = document.getElementById('action-btn');
  if (isProcessing || actionBtn.getAttribute('data-state') !== 'send') return;

  const input = document.getElementById('ai-input');
  const chat = document.getElementById('ai-chat');
  const question = inputText || input.value.trim();
  if (!question) return;

  // Update UI state
  isProcessing = true;
  actionBtn.disabled = true;
  actionBtn.setAttribute('aria-disabled', 'true');

  try {
    if (window.FormattingUtils) {
      chat.innerHTML += window.FormattingUtils.createMessageElement(
        question,
        'user',
      );
    } else {
      chat.innerHTML += `<div class="chat-message user"><span class="icon" aria-hidden="true">👤</span>${question}</div>`;
    }
    chat.scrollTop = chat.scrollHeight;

    if (!inputText) input.value = '';
    chatHistory.push({ sender: 'user', text: question });

    // Show typing indicator
    const typingId = `typing-${Date.now()}`;
    chat.innerHTML += `<div id="${typingId}" class="chat-message typing">Assistant is typing...</div>`;
    chat.scrollTop = chat.scrollHeight;

    // Get AI response
    if (
      !window.AIService ||
      typeof window.AIService.getResponse !== 'function'
    ) {
      throw new Error('AIService not properly initialized');
    }

    const aiResponse = await window.AIService.getResponse(
      question,
      chatHistory.slice(0, -1),
    );

    // Remove typing indicator
    const typingElement = document.getElementById(typingId);
    if (typingElement) typingElement.remove();

    // Update chat history
    chatHistory.push({ sender: 'assistant', text: aiResponse.response });
    if (chatHistory.length > 10)
      chatHistory = chatHistory.slice(chatHistory.length - 10);

    await addMessageToChat(
      aiResponse.response,
      aiResponse.quickReplies || [],
      'assistant',
    );
    saveChatHistory();
  } catch (error) {
    console.error('Error sending message:', error);

    const typingElement = document.getElementById(typingId);
    if (typingElement) typingElement.remove();

    const errorMessage =
      "I'm sorry, but I've reached my query limit. Please try again later or check our documentation for information about the API.";
    if (window.FormattingUtils) {
      chat.innerHTML += window.FormattingUtils.createMessageElement(
        errorMessage,
        'assistant',
      );
    } else {
      chat.innerHTML += `<div class="chat-message assistant"><span class="icon" aria-hidden="true">🤖</span>${errorMessage}</div>`;
    }
    chat.scrollTop = chat.scrollHeight;
  } finally {
    isProcessing = false;
    actionBtn.disabled = false;
    actionBtn.setAttribute('aria-disabled', 'false');
    setTimeout(() => input.focus(), 0);
  }
}

// Add a message to the chat with formatting
async function addMessageToChat(
  message,
  quickReplies = [],
  sender = 'assistant',
  isWelcomeMessage = false,
) {
  const chat = document.getElementById('ai-chat');
  if (!chat) return;
  const messageId = `message-${Date.now()}`;
  try {
    let formattedContent = message;
    if (window.FormattingUtils) {
      formattedContent =
        window.FormattingUtils.standardizeApiEndpoints(formattedContent);
      formattedContent =
        window.FormattingUtils.markdownToHtml(formattedContent);
      const classList = sender + (isWelcomeMessage ? ' welcome-message' : '');
      const messageHtml = `<div id="${messageId}" class="chat-message ${classList}"><span class="icon" aria-hidden="true">${
        sender === 'assistant' ? '🤖' : '👤'
      }</span>&nbsp;</div>`;
      chat.insertAdjacentHTML('beforeend', messageHtml);
      const messageElement = document.getElementById(messageId);
      if (messageElement && sender === 'assistant') {
        let i = 0;
        const speed = 2;
        function typeChar() {
          if (i <= formattedContent.length) {
            messageElement.innerHTML =
              `<span class="icon" aria-hidden="true">🤖</span>` +
              formattedContent.slice(0, i);
            i++;
            setTimeout(typeChar, speed);
          } else {
            if (
              quickReplies &&
              quickReplies.length > 0 &&
              window.FormattingUtils
            ) {
              messageElement.innerHTML +=
                window.FormattingUtils.formatQuickReplies(quickReplies);
            }
          }
        }
        typeChar();
      } else if (messageElement) {
        messageElement.innerHTML =
          `<span class="icon" aria-hidden="true">👤</span>` + formattedContent;
        // For user messages, no quick replies
      }
    } else {
      const icon = sender === 'assistant' ? '🤖' : '👤';
      const classList = sender + (isWelcomeMessage ? ' welcome-message' : '');
      chat.insertAdjacentHTML(
        'beforeend',
        `<div id="${messageId}" class="chat-message ${classList}"><span class="icon" aria-hidden="true">${icon}</span>${formattedContent}</div>`,
      );
    }
    chat.scrollTop = chat.scrollHeight;
  } catch (error) {
    console.error('Error adding message to chat:', error);
    if (chat) {
      const icon = sender === 'assistant' ? '🤖' : '👤';
      chat.insertAdjacentHTML(
        'beforeend',
        `<div class="chat-message ${sender}"><span class="icon" aria-hidden="true">${icon}</span><span class="error">Error displaying message.</span></div>`,
      );
      chat.scrollTop = chat.scrollHeight;
    }
  }
}

// Toggle chat visibility
function toggleAssistant() {
  const assistant = document.querySelector('.ai-assistant');
  const toggle = document.querySelector('.ai-toggle');
  const input = document.getElementById('ai-input');

  if (!assistant || !toggle) return;

  const isActive = !assistant.classList.contains('active');

  assistant.setAttribute('aria-hidden', !isActive);
  assistant.classList.toggle('active', isActive);
  toggle.classList.toggle('hidden', isActive);

  if (isActive) {
    if (input) input.focus();

    const chat = document.getElementById('ai-chat');
    if (chat && !chat.querySelector('.chat-message')) {
      typeWelcomeMessage();
    }
  } else {
    toggle.focus();
  }
}

// Clear chat history
function clearChatHistory() {
  const chat = document.getElementById('ai-chat');
  chat.innerHTML = '';
  try {
    localStorage.removeItem('aiChatHistory');
  } catch (e) {
    console.warn('localStorage unavailable:', e);
  }
  chatHistory = [];
  typeWelcomeMessage();
}

// Save chat to localStorage
function saveChatHistory() {
  try {
    const chat = document.getElementById('ai-chat').innerHTML;
    localStorage.setItem('aiChatHistory', chat);
  } catch (e) {
    console.warn('localStorage unavailable:', e);
  }
}

// Load chat from localStorage
function loadChatHistory() {
  try {
    const chat = document.getElementById('ai-chat');
    const history = localStorage.getItem('aiChatHistory');
    if (history) {
      chat.innerHTML = history;
      chat.scrollTop = chat.scrollHeight;
    }
  } catch (e) {
    console.warn('localStorage unavailable:', e);
  }
}

// Setup event listeners
function setupEventListeners() {
  document.querySelector('.ai-toggle')?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      toggleAssistant();
    }
  });

  // Send message with Enter key
  document.getElementById('ai-input')?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !isProcessing) {
      e.preventDefault();
      sendMessage();
    }
  });

  // Send message with button click
  document.getElementById('action-btn')?.addEventListener('click', () => {
    if (!isProcessing) sendMessage();
  });

  // Handle Escape key to close chat
  document.querySelector('.ai-assistant')?.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') toggleAssistant();
  });
}

// Export functions for ES modules
export {
  toggleAssistant,
  clearChatHistory,
  sendMessage,
  addMessageToChat,
  setupEventListeners,
  loadChatHistory,
  saveChatHistory,
};
