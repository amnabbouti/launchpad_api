import './assets/env-loader.js';
import './assets/ai-service.js';
import {
  toggleAssistant,
  clearChatHistory,
  sendMessage,
} from './assets/main-ai.js';

window.toggleAssistant = toggleAssistant;
window.clearChatHistory = clearChatHistory;
window.sendMessage = sendMessage;
document.addEventListener('DOMContentLoaded', function () {
  if (window.lucide) {
    lucide.createIcons();
  }
});
