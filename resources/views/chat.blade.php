<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SAC Thesis System - AI Assistant</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen font-sans">

@include('partials.sidebar')

<div class="md:ml-64 flex h-screen flex-col transition-all">
    <!-- Top Bar -->
    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 bg-white px-4 md:px-6 py-4 shadow-sm">
        <div>
            <h1 class="text-base md:text-lg font-bold text-[#700000]">AI Assistant</h1>
            <p class="mt-0.5 text-xs md:text-sm text-gray-500">
                Ask questions about uploaded thesis documents.
            </p>
        </div>

        <div class="self-start sm:self-auto rounded-lg bg-[#700000]/10 px-3 py-1.5 text-xs text-[#700000] font-bold border border-[#700000]/20">
            RAG Thesis Assistant
        </div>
    </header>

    <!-- Chat Area -->
    <div id="chatMessages" class="flex-1 overflow-y-auto px-4 md:px-6 py-6 space-y-6 bg-slate-50">
        <!-- Welcome Message -->
        <div class="flex items-start gap-3">
            <div class="w-8 h-8 md:w-9 md:h-9 rounded-lg bg-[#700000] text-[#FFD700] flex-shrink-0 flex items-center justify-center text-sm md:text-base font-bold shadow-md">
                🤖
            </div>

            <div class="max-w-3xl">
                <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-md px-3.5 md:px-4 py-3 shadow-sm">
                    <p class="text-sm text-gray-800 font-medium">
                        👋 Hi! I'm your RAG Thesis AI Assistant.
                    </p>
                    <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                        Ask me anything about the uploaded thesis papers. I'll search the repository and use the relevant documents to answer your question.
                    </p>
                </div>
                <div class="text-[10px] md:text-xs text-[#700000] mt-1.5 font-semibold">
                    AI Thesis Assistant
                </div>
            </div>
        </div>
    </div>

    <!-- Message Input Bar -->
    <div class="border-t border-gray-200 bg-white px-4 md:px-6 py-3 md:py-4">
        <form id="chatForm" class="flex items-end sm:items-center gap-2 md:gap-3 w-full">
            <div class="flex-1 relative">
                <textarea
                    id="messageInput"
                    rows="1"
                    required
                    placeholder="Ask a question..."
                    class="w-full resize-none bg-slate-50 border border-gray-300 rounded-xl px-3.5 md:px-4 py-2.5 md:py-3 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000] block leading-normal"
                ></textarea>
            </div>

            <button
                type="submit"
                id="sendBtn"
                class="h-[42px] md:h-[46px] bg-[#700000] hover:bg-[#800000] disabled:bg-gray-200 disabled:text-gray-400 text-[#FFD700] font-bold px-4 md:px-6 rounded-xl text-xs md:text-sm transition flex items-center justify-center gap-1.5 md:gap-2 shrink-0 shadow-sm"
            >
                <span id="sendBtnText" class="hidden sm:inline">Send</span>
                <span id="sendBtnIcon">➤</span>
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');
    const sendBtnText = document.getElementById('sendBtnText');
    const sendBtnIcon = document.getElementById('sendBtnIcon');
    const chatMessages = document.getElementById('chatMessages');

    function scrollToBottom() {
        chatMessages.scrollTo({
            top: chatMessages.scrollHeight,
            behavior: 'smooth'
        });
    }

    function addUserMessage(message) {
        const messageWrapper = document.createElement('div');
        messageWrapper.className = 'flex justify-end items-start gap-3';
        messageWrapper.innerHTML = `
            <div class="max-w-xl md:max-w-3xl">
                <div class="bg-[#700000] rounded-2xl rounded-tr-md px-3.5 md:px-4 py-3 shadow-sm">
                    <p class="text-sm text-white whitespace-pre-wrap"></p>
                </div>
                <div class="text-[10px] md:text-xs text-gray-500 mt-1.5 text-right font-medium">You</div>
            </div>
        `;
        messageWrapper.querySelector('p').textContent = message;
        chatMessages.appendChild(messageWrapper);
        scrollToBottom();
    }

    function addAIMessage(answer, sources = []) {
        const messageWrapper = document.createElement('div');
        messageWrapper.className = 'flex items-start gap-3';
        messageWrapper.innerHTML = `
            <div class="w-8 h-8 md:w-9 md:h-9 rounded-lg bg-[#700000] text-[#FFD700] flex-shrink-0 flex items-center justify-center text-sm md:text-base font-bold shadow-md">🤖</div>
            <div class="max-w-xl md:max-w-3xl">
                <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-md px-3.5 md:px-4 py-3 shadow-sm">
                    <div class="text-sm text-gray-800 whitespace-pre-wrap ai-answer leading-relaxed"></div>
                    ${sources.length > 0 ? `
                        <div class="mt-4 pt-3 border-t border-gray-100">
                            <p class="text-xs font-bold text-[#700000] mb-2">📚 Sources</p>
                            <div class="space-y-2 source-list"></div>
                        </div>
                    ` : ''}
                </div>
                <div class="text-[10px] md:text-xs text-[#700000] mt-1.5 font-semibold">AI Thesis Assistant</div>
            </div>
        `;

        messageWrapper.querySelector('.ai-answer').textContent = answer;

        if (sources.length > 0) {
            const sourceList = messageWrapper.querySelector('.source-list');
            sources.forEach((source, index) => {
                const sourceElement = document.createElement('div');
                sourceElement.className = 'text-xs text-gray-600 bg-slate-50 border border-gray-200 rounded-lg px-3 py-2';
                const similarity = source.similarity ? Math.round(source.similarity * 100) : null;
                sourceElement.innerHTML = `
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-medium">Source ${index + 1}</span>
                        ${similarity !== null ? `<span class="text-[#700000] font-bold">${similarity}% similarity</span>` : ''}
                    </div>
                `;
                sourceList.appendChild(sourceElement);
            });
        }

        chatMessages.appendChild(messageWrapper);
        scrollToBottom();
    }

    function addLoadingMessage() {
        const loadingWrapper = document.createElement('div');
        loadingWrapper.id = 'loadingMessage';
        loadingWrapper.className = 'flex items-start gap-3';
        loadingWrapper.innerHTML = `
            <div class="w-8 h-8 md:w-9 md:h-9 rounded-lg bg-[#700000] text-[#FFD700] flex-shrink-0 flex items-center justify-center text-sm md:text-base font-bold shadow-md">🤖</div>
            <div>
                <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-md px-4 py-3 shadow-sm">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 bg-[#700000] rounded-full animate-bounce"></span>
                        <span class="w-2 h-2 bg-[#700000] rounded-full animate-bounce" style="animation-delay: 0.15s"></span>
                        <span class="w-2 h-2 bg-[#700000] rounded-full animate-bounce" style="animation-delay: 0.3s"></span>
                    </div>
                </div>
                <div class="text-[10px] md:text-xs text-gray-500 mt-1.5 font-medium">AI Thesis Assistant is searching...</div>
            </div>
        `;
        chatMessages.appendChild(loadingWrapper);
        scrollToBottom();
    }

    function removeLoadingMessage() {
        const loadingMessage = document.getElementById('loadingMessage');
        if (loadingMessage) loadingMessage.remove();
    }

    function addErrorMessage(message) {
        const errorWrapper = document.createElement('div');
        errorWrapper.className = 'flex items-start gap-3';
        errorWrapper.innerHTML = `
            <div class="w-8 h-8 md:w-9 md:h-9 rounded-lg bg-red-600 text-white flex-shrink-0 flex items-center justify-center text-sm md:text-base font-bold shadow-md">⚠️</div>
            <div class="max-w-xl md:max-w-3xl">
                <div class="bg-red-50 border border-red-200 rounded-2xl rounded-tl-md px-4 py-3 shadow-sm">
                    <p class="text-sm text-red-700 whitespace-pre-wrap"></p>
                </div>
                <div class="text-[10px] md:text-xs text-red-600 mt-1.5 font-semibold">System Error</div>
            </div>
        `;
        errorWrapper.querySelector('p').textContent = message;
        chatMessages.appendChild(errorWrapper);
        scrollToBottom();
    }

    chatForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const message = messageInput.value.trim();
        if (!message) return;

        addUserMessage(message);
        messageInput.value = '';
        messageInput.style.height = 'auto';

        sendBtn.disabled = true;
        sendBtnText.textContent = 'Thinking...';
        sendBtnIcon.textContent = '⏳';

        addLoadingMessage();

        try {
            const response = await fetch('/backend/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ message: message })
            });

            const data = await response.json();
            removeLoadingMessage();

            if (!response.ok || data.error) {
                addErrorMessage(data.message || 'Sorry, something went wrong while processing your question.');
                return;
            }

            addAIMessage(data.answer || 'No answer was generated.', data.sources || []);
        } catch (error) {
            removeLoadingMessage();
            addErrorMessage('Unable to connect to the chatbot server. Please make sure the backend is running.');
        } finally {
            sendBtn.disabled = false;
            sendBtnText.textContent = 'Send';
            sendBtnIcon.textContent = '➤';
            messageInput.focus();
        }
    });

    messageInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            chatForm.requestSubmit();
        }
    });

    messageInput.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 150) + 'px';
    });
});
</script>
</body>
</html>