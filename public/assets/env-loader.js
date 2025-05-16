async function loadEnvironmentVariables() {
  try {
    if (document.readyState !== 'loading') {
      initWithEnvVars();
    } else {
      document.addEventListener('DOMContentLoaded', initWithEnvVars);
    }
  } catch (error) {
    console.error('Error loading environment variables:', error);
  }
}

function initWithEnvVars() {
  const aiChatScript = document.querySelector('script[src="assets/main-ai.js"]');
  if (aiChatScript) {
    aiChatScript.addEventListener('load', () => {
      console.log('AI chat initialized');
    });
  }
}
loadEnvironmentVariables(); 