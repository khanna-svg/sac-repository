@if(session('sac_user_role') !== 'admin')
<style>
    html.ai-drawer-open #floatingAiContainer,
    html.ai-drawer-open #floatingAiWindow {
        display: none !important;
    }
</style>

<!-- 1. Floating Action Button (Messenger Chat Head) -->
<div id="floatingAiContainer" class="fixed bottom-5 right-5 sm:bottom-6 sm:right-6 z-40 select-none group">
    
    <!-- Hover Tooltip / Speech Bubble -->
    <div
        id="floatingAiTooltip"
        class=""
        <span class="w-2 h-2 animate-pulse"></span>
        <span class="absolute -bottom-1.5 right-6 w-3 h-3 bg-white border-r border-b border-gray-200/80 rotate-45"></span>
    </div>

    <!-- The Circular Floating Button -->
    <button
        id="floatingAiBtn"
        type="button"
        onclick="toggleFloatingAiChat()"
        title="Open AI Assistant"
        aria-label="Open AI Assistant"
        class="relative w-14 h-14 rounded-full bg-[#700000] text-[#FFD700] shadow-2xl border-2 border-[#FFD700] hover:bg-[#850000] hover:scale-105 active:scale-95 transition-all duration-300 flex items-center justify-center cursor-pointer focus:outline-none">
        
        <!-- Open Icon -->
        <span id="floatingAiIconOpen" class="flex items-center justify-center transition-transform duration-300 group-hover:rotate-12">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
            </svg>
        </span>

        <!-- Close Icon (X) -->
        <span id="floatingAiIconClose" class="hidden items-center justify-center transition-transform duration-300">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </span>
    </button>
</div>

<!-- 2. Floating Messenger Chat Window -->
<div
    id="floatingAiWindow"
    class="fixed bottom-[80px] sm:bottom-[88px] right-4 sm:right-6 z-50 w-[calc(100vw-2rem)] sm:w-[390px] md:w-[420px] h-[550px] max-h-[82vh] bg-white rounded-3xl shadow-2xl border border-gray-200/90 flex flex-col overflow-hidden transition-all duration-300 ease-out origin-bottom-right hidden"
    style="box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.25);">

    <!-- Header (Messenger Style Gradient) -->
    <div class="px-4 py-3 bg-gradient-to-r from-[#690000] via-[#700000] to-[#8a0000] text-white flex items-center justify-between shadow-xs shrink-0">
        <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-9 h-9 rounded-2xl bg-white/15 border border-[#FFD700]/50 text-[#FFD700] flex items-center justify-center shrink-0 shadow-inner">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                </svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-xs sm:text-sm font-bold text-white tracking-wide truncate flex items-center gap-1.5">
                    <span>SAC AI Assistant</span>
                </h3>
            </div>
        </div>

        <!-- Header Actions -->
        <div class="flex items-center gap-1 shrink-0">
            <!-- New Chat / Clear -->
            <button
                type="button"
                onclick="clearFloatingAiChat()"
                title="Start New Conversation"
                class="p-1.5 rounded-xl text-amber-200 hover:text-white hover:bg-white/10 transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
            </button>

            <!-- Close / Minimize -->
            <button
                type="button"
                onclick="toggleFloatingAiChat()"
                title="Minimize"
                class="p-1.5 rounded-xl text-amber-200 hover:text-white hover:bg-white/10 transition cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Chat Messages Feed -->
    <div id="floatingAiMessages" class="flex-1 overflow-y-auto p-4 space-y-3.5 bg-slate-50/70 text-xs sm:text-sm">
        
        <!-- Welcome Card -->
        <div id="floatingAiWelcomeCard" class="space-y-3">
            <div class="flex items-start gap-2.5">
                <div class="w-7 h-7 rounded-xl bg-[#700000] text-[#FFD700] flex items-center justify-center shrink-0 shadow-xs mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                    </svg>
                </div>
                <div class="flex-1 bg-white border border-gray-200/90 rounded-2xl rounded-tl-xs p-3.5 shadow-xs text-gray-800 leading-relaxed">
                    <p class="font-bold text-[#700000]">Hello! I'm your SAC Thesis AI.</p>
                    <p class="mt-1 text-gray-600 text-xs">
                        Ask me any research questions about thesis topics, methodologies, or findings in St. Anthony's College.
                    </p>
                </div>
            </div>

            <!-- Quick Suggestions Chips -->
            <div class="pl-9 space-y-1.5">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Suggested Questions</p>
                <button
                    type="button"
                    onclick="sendFloatingSuggested('What IoT and computer vision capstone theses are available?')"
                    class="w-full text-left px-3 py-2 rounded-xl bg-white border border-gray-200 text-xs font-semibold text-gray-700 hover:border-[#700000] hover:text-[#700000] hover:shadow-2xs transition cursor-pointer">
                    💡 What IoT capstone theses are available?
                </button>
                <button
                    type="button"
                    onclick="sendFloatingSuggested('Explain the common methodologies used in recent IT theses.')"
                    class="w-full text-left px-3 py-2 rounded-xl bg-white border border-gray-200 text-xs font-semibold text-gray-700 hover:border-[#700000] hover:text-[#700000] hover:shadow-2xs transition cursor-pointer">
                    📊 Explain common IT research methodologies
                </button>
                <button
                    type="button"
                    onclick="sendFloatingSuggested('Recommend healthcare or nursing capstone research topics.')"
                    class="w-full text-left px-3 py-2 rounded-xl bg-white border border-gray-200 text-xs font-semibold text-gray-700 hover:border-[#700000] hover:text-[#700000] hover:shadow-2xs transition cursor-pointer">
                    🏥 Recommend healthcare research topics
                </button>
            </div>
        </div>

        <!-- Dynamic Chat Thread -->
        <div id="floatingAiChatThread" class="space-y-3.5"></div>

        <!-- Typing Indicator -->
        <div id="floatingAiTyping" class="hidden items-center gap-2 text-xs text-gray-400 pl-2">
            <span class="w-2 h-2 rounded-full bg-[#700000] animate-pulse"></span>
            <span class="w-2 h-2 rounded-full bg-[#700000] animate-pulse delay-150"></span>
            <span class="w-2 h-2 rounded-full bg-[#700000] animate-pulse delay-300"></span>
            <span class="text-[11px] text-gray-500 font-medium ml-1">Searching repository...</span>
        </div>
    </div>

    <!-- Input Footer (Messenger Style) -->
    <div class="p-3 bg-white border-t border-gray-100 shrink-0">
        <form id="floatingAiForm" onsubmit="handleFloatingAiSubmit(event)" class="relative flex items-center gap-2">
            <input
                id="floatingAiInput"
                type="text"
                autocomplete="off"
                placeholder="Ask a research question..."
                class="w-full rounded-2xl border border-gray-300 bg-slate-50 pl-4 pr-11 py-2.5 text-xs sm:text-sm text-gray-800 placeholder-gray-400 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000] shadow-2xs transition">

            <button
                id="floatingAiSendBtn"
                type="submit"
                title="Send Message"
                class="absolute right-1.5 p-2 rounded-xl bg-[#700000] text-[#FFD700] hover:bg-[#850000] transition disabled:opacity-40 cursor-pointer shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                </svg>
            </button>
        </form>
        <p class="text-[9px] text-gray-400 text-center mt-1.5">
            St. Anthony's College Repository • Powered by Gemini AI
        </p>
    </div>

</div>

<!-- =========================================================
     FLOATING AI JAVASCRIPT LOGIC
========================================================== -->
<script>
    let floatingAiHistory = [];
    let floatingAiIsOpen = false;

    // Load marked.js dynamically if not already available on the page
    (function ensureMarkedLoaded() {
        if (typeof marked === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/marked/marked.min.js';
            document.head.appendChild(script);
        }
    })();

    function toggleFloatingAiChat() {
        if (floatingAiIsOpen) {
            closeFloatingAiChat();
        } else {
            openFloatingAiChat();
        }
    }

    function openFloatingAiChat() {
        const windowElem = document.getElementById('floatingAiWindow');
        const iconOpen = document.getElementById('floatingAiIconOpen');
        const iconClose = document.getElementById('floatingAiIconClose');
        const tooltip = document.getElementById('floatingAiTooltip');
        const input = document.getElementById('floatingAiInput');

        if (!windowElem) return;

        windowElem.classList.remove('hidden');
        windowElem.classList.add('flex');
        if (iconOpen) iconOpen.classList.add('hidden');
        if (iconClose) iconClose.classList.remove('hidden');
        if (iconClose) iconClose.classList.add('flex');
        if (tooltip) tooltip.classList.add('hidden');

        floatingAiIsOpen = true;

        setTimeout(() => {
            if (input) input.focus();
        }, 150);
    }

    function closeFloatingAiChat() {
        const windowElem = document.getElementById('floatingAiWindow');
        const iconOpen = document.getElementById('floatingAiIconOpen');
        const iconClose = document.getElementById('floatingAiIconClose');
        const tooltip = document.getElementById('floatingAiTooltip');

        if (!windowElem) return;

        windowElem.classList.add('hidden');
        windowElem.classList.remove('flex');
        if (iconOpen) iconOpen.classList.remove('hidden');
        if (iconClose) iconClose.classList.add('hidden');
        if (iconClose) iconClose.classList.remove('flex');
        if (tooltip) tooltip.classList.remove('hidden');

        floatingAiIsOpen = false;
    }

    function clearFloatingAiChat() {
        floatingAiHistory = [];
        const thread = document.getElementById('floatingAiChatThread');
        const welcome = document.getElementById('floatingAiWelcomeCard');
        const input = document.getElementById('floatingAiInput');

        if (thread) thread.innerHTML = '';
        if (welcome) welcome.classList.remove('hidden');
        if (input) {
            input.value = '';
            input.focus();
        }
    }

    function sendFloatingSuggested(question) {
        const input = document.getElementById('floatingAiInput');
        if (input) input.value = question;
        handleFloatingAiSubmit(new Event('submit'));
    }

    async function handleFloatingAiSubmit(event) {
        if (event && event.preventDefault) event.preventDefault();

        const input = document.getElementById('floatingAiInput');
        if (!input) return;

        const question = input.value.trim();
        if (!question) return;

        input.value = '';
        await executeFloatingAiQuery(question);
    }

    async function executeFloatingAiQuery(question) {
        const thread = document.getElementById('floatingAiChatThread');
        const typing = document.getElementById('floatingAiTyping');
        const sendBtn = document.getElementById('floatingAiSendBtn');
        const messages = document.getElementById('floatingAiMessages');
        const welcome = document.getElementById('floatingAiWelcomeCard');

        if (!thread || !typing || !sendBtn || !messages) return;

        // Hide welcome card once conversation starts
        if (welcome) welcome.classList.add('hidden');

        // 1. Append user message bubble
        const userBubble = document.createElement('div');
        userBubble.className = 'flex justify-end';
        userBubble.innerHTML = `
            <div class="max-w-[85%] rounded-2xl rounded-tr-xs bg-[#700000] text-white px-3.5 py-2 text-xs sm:text-sm font-medium shadow-xs leading-relaxed">
                ${escapeHtml(question)}
            </div>
        `;
        thread.appendChild(userBubble);
        messages.scrollTop = messages.scrollHeight;

        // Append to history
        floatingAiHistory.push({ role: 'user', content: question });

        // 2. Show typing
        typing.classList.remove('hidden');
        typing.classList.add('flex');
        sendBtn.disabled = true;
        messages.scrollTop = messages.scrollHeight;

        try {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const token = csrfMeta ? csrfMeta.getAttribute('content') : '';

            const res = await fetch('/backend/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    message: question,
                    history: floatingAiHistory
                })
            });

            const data = await res.json();
            typing.classList.add('hidden');
            typing.classList.remove('flex');
            sendBtn.disabled = false;

            if (!res.ok || data?.error) {
                const errMsg = data?.message || 'Unable to get answer from AI.';
                appendFloatingAiError(errMsg);
                return;
            }

            const rawAnswer = data.answer || 'No answer could be found.';
            floatingAiHistory.push({ role: 'assistant', content: rawAnswer });

            let formatted = rawAnswer;
            if (typeof marked !== 'undefined' && marked && marked.parse) {
                formatted = marked.parse(rawAnswer);
            } else {
                formatted = escapeHtml(rawAnswer).replace(/\n/g, '<br>');
            }

            const aiBubble = document.createElement('div');
            aiBubble.className = 'flex items-start gap-2';
            aiBubble.innerHTML = `
                <div class="w-6 h-6 rounded-xl bg-[#700000] text-[#FFD700] flex items-center justify-center shrink-0 shadow-2xs mt-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                    </svg>
                </div>
                <div class="flex-1 max-w-[90%] bg-white border border-gray-200/90 rounded-2xl rounded-tl-xs p-3 shadow-xs text-xs sm:text-sm text-gray-800 leading-relaxed">
                    <div class="prose prose-xs sm:prose-sm max-w-none text-gray-800">${formatted}</div>
                </div>
            `;
            thread.appendChild(aiBubble);
            messages.scrollTop = messages.scrollHeight;

        } catch (err) {
            console.error('Floating AI Error:', err);
            typing.classList.add('hidden');
            typing.classList.remove('flex');
            sendBtn.disabled = false;
            appendFloatingAiError('Unable to connect to AI server. Please try again.');
        }
    }

    function appendFloatingAiError(msg) {
        const thread = document.getElementById('floatingAiChatThread');
        const messages = document.getElementById('floatingAiMessages');
        if (!thread) return;

        const errBubble = document.createElement('div');
        errBubble.className = 'flex justify-center';
        errBubble.innerHTML = `
            <div class="rounded-xl bg-red-50 border border-red-200 px-3 py-1.5 text-[11px] text-red-700 font-medium text-center">
                ⚠️ ${escapeHtml(msg)}
            </div>
        `;
        thread.appendChild(errBubble);
        if (messages) messages.scrollTop = messages.scrollHeight;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Close floating chat on outside click
    document.addEventListener('click', function(e) {
        if (!floatingAiIsOpen) return;
        const windowElem = document.getElementById('floatingAiWindow');
        const containerElem = document.getElementById('floatingAiContainer');
        if (windowElem && !windowElem.contains(e.target) && containerElem && !containerElem.contains(e.target)) {
            closeFloatingAiChat();
        }
    });

    // Close floating chat on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && floatingAiIsOpen) {
            closeFloatingAiChat();
        }
    });
</script>
@endif
