Scalar.createApiReference('#app', {
  url: '/swagger/openapi.yaml',
  theme: 'kepler',
  hideDarkModeToggle: false,
  showSidebar: true,
  hideTestRequestButton: false,
  hideClientButton: true,
  withDefaultFonts: false,
  showAuthButtonInHeader: true,
  authPersistence: true,
});

document.addEventListener('DOMContentLoaded', function () {
  const typingElement = document.getElementById('typing-text');
  const text = typingElement.textContent;
  typingElement.textContent = '>';
  let i = 0;
  const typingSpeed = 15;

  function getRandomDelay() {
    return Math.random() < 0.1 ? 300 : Math.random() * 100 + typingSpeed;
  }

  function typeNextCharacter() {
    if (i < text.length) {
      if (['.', '-', ','].includes(text.charAt(i))) {
        setTimeout(() => {
          typingElement.textContent =
            '> ' + typingElement.textContent.substring(2) + text.charAt(i);
          i++;
          setTimeout(typeNextCharacter, getRandomDelay());
        }, 200);
      } else {
        typingElement.textContent =
          '> ' + typingElement.textContent.substring(2) + text.charAt(i);
        i++;
        setTimeout(typeNextCharacter, getRandomDelay());
      }
    } else {
      typingElement.classList.add('typing-complete');
    }
  }
  setTimeout(typeNextCharacter, 500);

  setTimeout(() => {
    if (!document.getElementById('ai-chat').innerHTML.trim()) {
      if (
        window.AIService &&
        typeof window.AIService.getResponse === 'function'
      ) {
        typeWelcomeMessage();
      } else {
        const chat = document.getElementById('ai-chat');
        chat.innerHTML += `<div class="chat-message assistant"><span class="icon" aria-hidden="true">🤖</span>Welcome to the LaunchPad API Assistant. How can I help you today?</div>`;
      }
    }
  }, 1000);
});

let isProcessing = false;
let chatHistory = [];

async function typeWelcomeMessage() {
  try {
    if (
      !window.AIService ||
      typeof window.AIService.getResponse !== 'function'
    ) {
      throw new Error('AIService not properly initialized');
    }

    const welcomeResponse = await window.AIService.getResponse(
      'Provide a very short, friendly welcome greeting for a new user. Just a single sentence. Do not include any quick reply suggestions for this initial greeting.',
      [],
    );

    await typeResponse(
      welcomeResponse.response,
      welcomeResponse.quickReplies || [],
      'assistant',
    );
    chatHistory.push({ sender: 'assistant', text: welcomeResponse.response });
  } catch (error) {
    const simpleGreeting =
      'Welcome to the LaunchPad API Assistant. How can I help you today?';
    await typeResponse(simpleGreeting, [], 'assistant');
    chatHistory.push({ sender: 'assistant', text: simpleGreeting });
  }
}

function formatEndpoints(text) {
  if (window.FormattingUtils) {
    text = window.FormattingUtils.standardizeApiEndpoints(text);
    if (typeof window.FormattingUtils.markdownToHtml === 'function') {
      let formattedText = window.FormattingUtils.markdownToHtml(text);
      if (
        formattedText.includes('numbered-item') &&
        !document.getElementById('numbered-list-style')
      ) {
        const styleEl = document.createElement('style');
        styleEl.id = 'numbered-list-style';
        document.head.appendChild(styleEl);
      }
      return formattedText;
    }
  }

  console.warn('FormattingUtils not available, using legacy formatting');

  let formattedText = text.replace(
    /(\d+)\.\s+([^\n]+)/g,
    (match, number, content) =>
      `<div class="numbered-item"><span class="number">${number}.</span> ${content}</div>`,
  );

  formattedText = formattedText.replace(
    /(?:`?(GET|POST|PUT|DELETE|PATCH)\s+([^`\n]+)`?|(\b(?:GET|POST|PUT|DELETE|PATCH)\b)\s+([^\n]+?)(?:\s|$))/gi,
    (match, verb1, path1, verb2, path2) => {
      const verb = verb1 || verb2;
      const path = path1 || path2;
      const verbLower = verb.toLowerCase();
      let cleanPath = path;
      if (!cleanPath.startsWith('/api/') && !cleanPath.startsWith('http')) {
        cleanPath = `/api/${cleanPath.replace(/^\//, '')}`;
      }
      return `<div class='swagger-ui'><div class='opblock opblock-${verbLower}'><div class='opblock-summary-method ${verbLower}'>${verb}</div><div class='opblock-summary-path'>${cleanPath}</div></div></div>`;
    },
  );

  formattedText = formattedText.replace(
    /```(json|javascript|html|css|yaml|bash|xml)?\n([\s\S]*?)```/gm,
    (match, language, code) => {
      const lang = language || '';
      return `<pre class="code-block ${lang}">${code
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')}</pre>`;
    },
  );

  formattedText = formattedText.replace(
    /`([^`]+)`/g,
    (match, code) => `<code>${code}</code>`,
  );
  formattedText = formattedText
    .replace(/\*\*([^*]+)\*\*/g, (match, text) => `<strong>${text}</strong>`)
    .replace(/\*([^*]+)\*/g, (match, text) => `<em>${text}</em>`);
  formattedText = formattedText
    .replace(
      /^\s*[\-\*]\s+(.+)$/gm,
      (match, item) => `<ul><li>${item}</li></ul>`,
    )
    .replace(/<\/ul>\s*<ul>/g, '');
  formattedText = formattedText.replace(
    /https:\/\/api\.launchpadinventory\.com\/([\w-]+)/g,
    (match, path) => `/api/${path}`,
  );

  if (
    formattedText.includes('numbered-item') &&
    !document.getElementById('numbered-list-style')
  ) {
    const styleEl = document.createElement('style');
    styleEl.id = 'numbered-list-style';
    document.head.appendChild(styleEl);
  }

  return formattedText;
}

async function sendMessage(inputText) {
  const actionBtn = document.getElementById('action-btn');
  if (isProcessing || actionBtn.getAttribute('data-state') !== 'send') return;

  const input = document.getElementById('ai-input');
  const chat = document.getElementById('ai-chat');
  const question = inputText || input.value.trim();
  if (!question) return;

  isProcessing = true;
  actionBtn.disabled = true;
  actionBtn.setAttribute('aria-disabled', 'true');
  try {
    if (
      window.FormattingUtils &&
      typeof window.FormattingUtils.createMessageElement === 'function'
    ) {
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

    const typingId = `typing-${Date.now()}`;
    chat.innerHTML += `<div id="${typingId}" class="chat-message typing">Assistant is typing...</div>`;
    chat.scrollTop = chat.scrollHeight;

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
    const typingElement = document.getElementById(typingId);
    if (typingElement) typingElement.remove();

    chatHistory.push({ sender: 'assistant', text: aiResponse.response });
    if (chatHistory.length > 10)
      chatHistory = chatHistory.slice(chatHistory.length - 10);

    await typeResponse(
      aiResponse.response,
      aiResponse.quickReplies,
      'assistant',
    );
    saveChatHistory();
  } catch (error) {
    console.error('Error in sendMessage:', error);
    const typingElement = document.getElementById(typingId);
    if (typingElement) typingElement.remove();

    const errorMessage = 'Oops, something went wrong. Please try again!';
    if (
      window.FormattingUtils &&
      typeof window.FormattingUtils.createMessageElement === 'function'
    ) {
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

async function typeResponse(response, quickReplies, sender) {
  const chat = document.getElementById('ai-chat');
  const messageId = `message-${Date.now()}`;
  const icon =
    sender === 'assistant'
      ? '<span class="icon" aria-hidden="true">🤖</span>'
      : '<span class="icon" aria-hidden="true">👤</span>';

  chat.innerHTML += `<div id="${messageId}" class="chat-message ${sender}">${icon}</div>`;
  const messageElement = document.getElementById(messageId);

  try {
    const formattedResponse = formatEndpoints(response);
    const parser = new DOMParser();
    const doc = parser.parseFromString(
      `<div>${formattedResponse}</div>`,
      'text/html',
    );
    const nodes = Array.from(doc.body.firstChild.childNodes);
    let currentText = '';
    let i = 0;
    const baseTypingSpeed = 30;

    async function typeNode() {
      if (i < nodes.length) {
        const node = nodes[i];
        if (node.nodeType === Node.TEXT_NODE) {
          const text = node.textContent;
          for (let j = 0; j < text.length; j++) {
            currentText += text[j];
            messageElement.innerHTML = `${icon}${currentText}`;
            chat.scrollTop = chat.scrollHeight;
            const typingSpeed = baseTypingSpeed - 10 + Math.random() * 20;
            await new Promise((resolve) => setTimeout(resolve, typingSpeed));
          }
        } else if (node.nodeType === Node.ELEMENT_NODE) {
          currentText += node.outerHTML;
          messageElement.innerHTML = `${icon}${currentText}`;
          chat.scrollTop = chat.scrollHeight;
          await new Promise((resolve) => setTimeout(resolve, baseTypingSpeed));
        }
        i++;
        await typeNode();
      } else {
        if (quickReplies && quickReplies.length > 0) {
          if (
            window.FormattingUtils &&
            typeof window.FormattingUtils.formatQuickReplies === 'function'
          ) {
            currentText +=
              window.FormattingUtils.formatQuickReplies(quickReplies);
          } else {
            const quickRepliesHtml = quickReplies
              .map(
                (reply) =>
                  `<button class="quick-reply" onclick="sendMessage('${reply.replace(
                    /'/g,
                    "\\'",
                  )}')">${reply}</button>`,
              )
              .join('');
            currentText += `<div class="quick-replies">${quickRepliesHtml}</div>`;
          }
          messageElement.innerHTML = `${icon}${currentText}`;
        }
        chat.scrollTop = chat.scrollHeight;
      }
    }
    await typeNode();
  } catch (error) {
    console.error('Error in typeResponse:', error);
    messageElement.innerHTML = `${icon}<span class="endpoint-error">Error displaying response. Please try again.</span>`;
  }
}

function toggleAssistant() {
  const assistant = document.querySelector('.ai-assistant');
  const toggle = document.querySelector('.ai-toggle');
  const input = document.getElementById('ai-input');
  const isActive = !assistant.classList.contains('active');

  assistant.setAttribute('aria-hidden', !isActive);
  assistant.classList.toggle('active', isActive);
  toggle.classList.toggle('hidden', isActive);
  if (isActive) {
    input.focus();
    trapFocus(assistant);
    if (!document.getElementById('ai-chat').innerHTML.trim())
      typeWelcomeMessage();
  } else {
    toggle.focus();
  }
}

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

function trapFocus(element) {
  const focusable = element.querySelectorAll(
    'button:not(:disabled), input:not(:disabled), [tabindex]:not([tabindex="-1"])',
  );
  const first = focusable[0];
  const last = focusable[focusable.length - 1];

  element.addEventListener('keydown', (e) => {
    if (e.key === 'Tab') {
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    } else if (e.key === 'Escape') toggleAssistant();
  });
}

function saveChatHistory() {
  try {
    const chat = document.getElementById('ai-chat').innerHTML;
    localStorage.setItem('aiChatHistory', chat);
  } catch (e) {
    console.warn('localStorage unavailable:', e);
  }
}

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

function setupEventListeners() {
  document.querySelector('.ai-toggle').addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      toggleAssistant();
    }
  });

  document.getElementById('ai-input').addEventListener('keydown', (e) => {
    const actionBtn = document.getElementById('action-btn');
    if (
      e.key === 'Enter' &&
      actionBtn.getAttribute('data-state') === 'send' &&
      !isProcessing
    ) {
      e.preventDefault();
      sendMessage();
    }
  });

  document.getElementById('action-btn').addEventListener('click', () => {
    const actionBtn = document.getElementById('action-btn');
    if (actionBtn.getAttribute('data-state') === 'send' && !isProcessing)
      sendMessage();
  });
}

setupEventListeners();
