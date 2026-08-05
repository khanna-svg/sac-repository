<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SAC Thesis System - AI Assistant</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100 h-screen flex flex-col font-sans">

  <!-- Navigation Bar -->
  <header class="border-b border-gray-800 bg-gray-950 px-6 py-4 flex items-center justify-between shrink-0">
    <div class="flex items-center gap-3">
      <div class="h-8 w-8 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-white">S</div>
      <h1 class="text-xl font-bold tracking-tight">SAC Thesis System</h1>
    </div>
    <nav class="flex gap-4 items-center">
      <a href="/documents" class="text-sm font-medium text-gray-400 hover:text-gray-200 transition">Documents & Search</a>
      <a href="/chat" class="text-sm font-medium text-indigo-400 border-b-2 border-indigo-500 pb-1">AI Assistant</a>
      <button id="logoutBtn" class="text-xs bg-gray-800 hover:bg-gray-700 border border-gray-700 px-3 py-1.5 rounded-lg ml-4 text-gray-300">Sign Out</button>
    </nav>
  </header>

  <!-- Chat Area Container -->
  <main class="flex-1 max-w-4xl w-full mx-auto flex flex-col p-4 overflow-hidden">
    
    <!-- Messages Thread -->
    <div id="chatThread" class="flex-1 overflow-y-auto space-y-4 p-2">
      
      <!-- Bot Message -->
      <div class="flex items-start gap-3">
        <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-xs font-bold shrink-0">AI</div>
        <div class="bg-gray-800 border border-gray-700 rounded-2xl rounded-tl-none p-4 max-w-xl text-sm text-gray-200">
          Hello! I can answer questions directly based on the uploaded research papers in your repository. What would you like to know?
        </div>
      </div>

    </div>

    <!-- Chat Input Form -->
    <form id="chatForm" class="shrink-0 pt-4 flex gap-3">
      @csrf
      <input 
        type="text" 
        id="chatInput" 
        placeholder="Ask a question about the documents..." 
        required
        class="flex-1 bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-sm text-gray-100 focus:outline-none focus:border-indigo-500"
      />
      <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white font-medium px-6 py-3 rounded-xl text-sm transition">
        Send
      </button>
    </form>

  </main>

  <script>
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');
    const chatThread = document.getElementById('chatThread');

    chatForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const userMessage = chatInput.value.trim();
      if (!userMessage) return;

      // Render User Message
      const userBubble = document.createElement('div');
      userBubble.className = 'flex items-start justify-end gap-3';
      userBubble.innerHTML = `
        <div class="bg-indigo-600 rounded-2xl rounded-tr-none p-4 max-w-xl text-sm text-white">
          ${escapeHtml(userMessage)}
        </div>
        <div class="h-8 w-8 rounded-full bg-gray-700 flex items-center justify-center text-xs font-bold shrink-0">ME</div>
      `;
      chatThread.appendChild(userBubble);
      chatInput.value = '';
      chatThread.scrollTop = chatThread.scrollHeight;

      // Mock Bot Response Placeholder
      setTimeout(() => {
        const botBubble = document.createElement('div');
        botBubble.className = 'flex items-start gap-3';
        botBubble.innerHTML = `
          <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-xs font-bold shrink-0">AI</div>
          <div class="bg-gray-800 border border-gray-700 rounded-2xl rounded-tl-none p-4 max-w-xl text-sm text-gray-200">
            I am currently waiting for the backend RAG endpoint to connect!
          </div>
        `;
        chatThread.appendChild(botBubble);
        chatThread.scrollTop = chatThread.scrollHeight;
      }, 500);
    });

    function escapeHtml(text) {
      return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    }

    document.getElementById('logoutBtn').addEventListener('click', () => {
      localStorage.removeItem('auth_token');
      window.location.href = '/login';
    });
  </script>
</body>
</html>