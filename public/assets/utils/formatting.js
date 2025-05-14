class FormattingUtils {
  static standardizeApiEndpoints(text) {
    if (!text) return '';
    let standardized = text.replace(
      /https?:\/\/api\.launchpadinventory\.com\/([\w-\/{}]+)/gi,
      '/api/$1',
    );
    standardized = standardized.replace(
      /\b(GET|POST|PUT|DELETE|PATCH)\s+\/([^\s\n`]+)/gi,
      '`$1 /api/$2`',
    );
    standardized = standardized.replace(
      /`(GET|POST|PUT|DELETE|PATCH)\s+\/(?!api\/)([\w\/-]+)`/gi,
      '`$1 /api/$2`',
    );
    standardized = standardized.replace(
      /(\d+)\.\s+([^\n]+)(\s*)(\d+)\./g,
      '$1. $2\n$3$4.',
    );
    standardized = standardized.replace(
      /(\d+\.\s+[^\n]+)(\n\s*\n+)([^\d])/g,
      '$1\n$3',
    );
    standardized = standardized.replace(/(\d+\.\s+[^\n]+)(\n\s*\n+)/g, '$1\n');
    return standardized;
  }
  static markdownToHtml(text) {
    if (!text) return '';
    let formattedText = text.replace(
      /(\d+)\.\s+([^\n]+)/g,
      (match, number, content) => {
        return `<div class="numbered-item"><span class="number">${number}.</span> ${content}</div>`;
      },
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
    return formattedText;
  }
  static createMessageElement(content, sender, messageId = '') {
    const icon =
      sender === 'assistant'
        ? '<span class="icon" aria-hidden="true">🤖</span>'
        : '<span class="icon" aria-hidden="true">👤</span>';
    const idAttribute = messageId ? ` id="${messageId}"` : '';
    if (sender === 'user') {
      const escapedContent = this.escapeHtml(content);
      return `<div${idAttribute} class="chat-message ${sender}">${icon}${escapedContent}</div>`;
    }
    return `<div${idAttribute} class="chat-message ${sender}">${icon}${content}</div>`;
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
