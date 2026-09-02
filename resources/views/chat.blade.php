<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SAC Thesis System - AI Assistant</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="bg-slate-50 text-slate-800 min-h-screen font-sans">

    @include('partials.sidebar')

    <div id="mainContent" class="md:ml-64 flex h-screen flex-col transition-all duration-300">

        <!-- HEADER -->
        <header class="flex items-center justify-between gap-3 border-b border-gray-200 bg-white px-4 md:px-6 py-4 shadow-sm">
            <div>
                <h1 class="text-base md:text-lg font-bold text-[#700000]">
                    AI Assistant
                </h1>
                <p class="mt-0.5 text-xs md:text-sm text-gray-500">
                    Ask questions with multi-turn conversation memory.
                </p>
            </div>

            <button
                type="button"
                onclick="clearChatHistory()"
                class="rounded-xl border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 px-3.5 py-2 text-xs font-semibold shadow-xs flex items-center gap-1.5 transition cursor-pointer">
                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <span>New Conversation</span>
            </button>
        </header>


        <!-- CHAT AREA -->
        <div
            id="chatMessages"
            class="flex-1 overflow-y-auto px-4 md:px-6 py-6 space-y-6 bg-transparent">

            <!-- INITIAL AI MESSAGE -->
            <div class="flex items-start gap-3">

                <div class="w-8 h-8 md:w-9 md:h-9 rounded-lg bg-[#700000] text-[#FFD700] flex-shrink-0 flex items-center justify-center text-sm md:text-base font-bold shadow-md">
                    AI
                </div>

                <div class="max-w-3xl">

                    <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-md px-4 py-3 shadow-sm">

                        <p class="text-sm text-gray-800 font-medium">
                            Hi! I'm your RAG Thesis AI Assistant.
                        </p>

                        <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                            Ask me anything about the uploaded thesis papers.
                            I'll search the repository and use the relevant
                            documents to answer your question.
                        </p>

                    </div>

                    <div class="text-[10px] md:text-xs text-[#700000] mt-1.5 font-semibold">
                        AI Thesis Assistant
                    </div>

                </div>

            </div>

        </div>


        <!-- INPUT -->
        <div class="border-t border-gray-200 bg-white px-4 md:px-6 py-3 md:py-4">

            <form
                id="chatForm"
                class="flex items-center gap-2 md:gap-3 w-full">

                <div class="flex-1">

                    <textarea
                        id="messageInput"
                        rows="1"
                        required
                        placeholder="Ask a question..."
                        class="w-full resize-none bg-slate-50 border border-gray-300 rounded-2xl px-4 py-3 text-xs md:text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000] leading-normal block shadow-sm"></textarea>

                </div>

                <button
                    type="submit"
                    id="sendBtn"
                    class="h-[46px] md:h-[48px] bg-[#700000] hover:bg-[#800000] disabled:bg-gray-200 disabled:text-gray-400 text-[#FFD700] font-bold px-5 md:px-6 rounded-2xl text-xs md:text-sm transition flex items-center justify-center gap-2 shrink-0 shadow-sm">

                    <span id="sendBtnText">
                        Send
                    </span>

                    <span id="sendBtnIcon" class="flex items-center justify-center">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                        </svg>
                    </span>

                </button>

            </form>

        </div>

    </div>


    <!-- =========================================================
         JAVASCRIPT
    ========================================================== -->

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            console.log('SAC Thesis AI Chat loading...');


            /* =========================================================
               GET ELEMENTS
            ========================================================== */

            const chatForm =
                document.getElementById('chatForm');

            const messageInput =
                document.getElementById('messageInput');

            const sendBtn =
                document.getElementById('sendBtn');

            const sendBtnText =
                document.getElementById('sendBtnText');

            const sendBtnIcon =
                document.getElementById('sendBtnIcon');

            const chatMessages =
                document.getElementById('chatMessages');

            let conversationHistory = [];

            /* =========================================================
               CLEAR CONVERSATION / NEW CHAT
            ========================================================== */
            window.clearChatHistory = function() {
                conversationHistory = [];
                chatMessages.innerHTML = `
                    <!-- INITIAL AI MESSAGE -->
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 md:w-9 md:h-9 rounded-lg bg-[#700000] text-[#FFD700] shrink-0 flex items-center justify-center text-sm md:text-base font-bold shadow-md">
                            AI
                        </div>
                        <div class="max-w-3xl">
                            <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-md px-4 py-3 shadow-sm">
                                <p class="text-sm text-gray-800 font-medium">
                                    Hi! I'm your RAG Thesis AI Assistant.
                                </p>
                                <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                                    Ask me anything about the uploaded thesis papers.
                                    I'll search the repository and remember our conversation context to answer your follow-up questions!
                                </p>
                            </div>
                            <div class="text-[10px] md:text-xs text-[#700000] mt-1.5 font-semibold">
                                AI Thesis Assistant
                            </div>
                        </div>
                    </div>
                `;
                messageInput.value = '';
                messageInput.focus();
            };

            /* =========================================================
               IMPORTANT:
               MAKE SURE NONE OF THE ELEMENTS ARE NULL
            ========================================================== */

            if (!chatForm) {
                console.error('ERROR: #chatForm does not exist.');
                return;
            }

            if (!messageInput) {
                console.error('ERROR: #messageInput does not exist.');
                return;
            }

            if (!sendBtn) {
                console.error('ERROR: #sendBtn does not exist.');
                return;
            }

            if (!sendBtnText) {
                console.error('ERROR: #sendBtnText does not exist.');
                return;
            }

            if (!sendBtnIcon) {
                console.error('ERROR: #sendBtnIcon does not exist.');
                return;
            }

            if (!chatMessages) {
                console.error('ERROR: #chatMessages does not exist.');
                return;
            }


            console.log('All chat elements found.');


            /* =========================================================
               SCROLL
            ========================================================== */

            function scrollToBottom() {

                chatMessages.scrollTop =
                    chatMessages.scrollHeight;

            }


            /* =========================================================
               ESCAPE HTML
            ========================================================== */

            function escapeHtml(value) {

                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');

            }


            /* =========================================================
               ADD USER MESSAGE
            ========================================================== */

            function addUserMessage(message) {
                conversationHistory.push({ role: 'user', content: message });

                const wrapper =
                    document.createElement('div');

                wrapper.className =
                    'flex justify-end items-start gap-3';

                wrapper.innerHTML = `
                <div class="max-w-xl md:max-w-3xl">
                    <div class="bg-[#700000] rounded-2xl rounded-tr-md px-4 py-3 shadow-sm">
                        <p class="text-sm text-white whitespace-pre-wrap"></p>
                    </div>
                    <div class="text-[10px] md:text-xs text-gray-500 mt-1.5 text-right font-medium">
                        You
                    </div>
                </div>
            `;

                const paragraph =
                    wrapper.querySelector('p');

                if (paragraph) {
                    paragraph.textContent = message;
                }

                chatMessages.appendChild(wrapper);
                scrollToBottom();
            }

            /* =========================================================
               ADD AI MESSAGE
            ========================================================== */
            function addAIMessage(answer) {
                conversationHistory.push({ role: 'assistant', content: answer });

                const wrapper =
                    document.createElement('div');

                wrapper.className =
                    'flex items-start gap-3';

                let formattedAnswer =
                    escapeHtml(answer);

                if (
                    typeof marked !== 'undefined' &&
                    marked &&
                    typeof marked.parse === 'function'
                ) {
                    formattedAnswer =
                        marked.parse(answer);
                }

                wrapper.innerHTML = `
                <div class="w-8 h-8 md:w-9 md:h-9 rounded-lg bg-[#700000] text-[#FFD700] shrink-0 flex items-center justify-center shadow-md">
                    <svg class="w-5 h-5 text-[#FFD700]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                    </svg>
                </div>

                <div class="max-w-xl md:max-w-3xl flex-1">
                    <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-md px-4 py-3.5 shadow-sm">
                        <div class="text-sm text-gray-800 ai-answer leading-relaxed prose prose-sm max-w-none">
                            ${formattedAnswer}
                        </div>
                    </div>

                    <div class="text-[10px] md:text-xs text-[#700000] mt-1.5 font-semibold">
                        AI Thesis Assistant
                    </div>
                </div>
            `;

                chatMessages.appendChild(wrapper);
                scrollToBottom();
            }


            /* =========================================================
               LOADING MESSAGE
            ========================================================== */

            function addLoadingMessage() {

                removeLoadingMessage();


                const wrapper =
                    document.createElement('div');

                wrapper.id =
                    'loadingMessage';

                wrapper.className =
                    'flex items-start gap-3';


                wrapper.innerHTML = `

                <div class="w-8 h-8 md:w-9 md:h-9 rounded-lg bg-[#700000] text-[#FFD700] flex-shrink-0 flex items-center justify-center shadow-md">
                    <svg class="w-5 h-5 text-[#FFD700]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                    </svg>
                </div>

                <div>

                    <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-md px-4 py-3 shadow-sm">

                        <div class="flex items-center gap-1.5">

                            <span class="w-2 h-2 bg-[#700000] rounded-full animate-bounce"></span>

                            <span class="w-2 h-2 bg-[#700000] rounded-full animate-bounce" style="animation-delay:.15s"></span>

                            <span class="w-2 h-2 bg-[#700000] rounded-full animate-bounce" style="animation-delay:.3s"></span>

                        </div>

                    </div>

                    <div class="text-[10px] md:text-xs text-gray-500 mt-1.5 font-medium">
                        AI Thesis Assistant is searching...
                    </div>

                </div>
            `;


                chatMessages.appendChild(wrapper);

                scrollToBottom();

            }


            /* =========================================================
               REMOVE LOADING
            ========================================================== */

            function removeLoadingMessage() {

                const loading =
                    document.getElementById(
                        'loadingMessage'
                    );

                if (loading) {
                    loading.remove();
                }

            }


            /* =========================================================
               ERROR MESSAGE
            ========================================================== */

            function addErrorMessage(message) {

                const wrapper =
                    document.createElement('div');

                wrapper.className =
                    'flex items-start gap-3';


                wrapper.innerHTML = `

                <div class="w-8 h-8 md:w-9 md:h-9 rounded-lg bg-red-600 text-white flex-shrink-0 flex items-center justify-center shadow-md">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>

                <div class="max-w-xl md:max-w-3xl">

                    <div class="bg-red-50 border border-red-200 rounded-2xl rounded-tl-md px-4 py-3 shadow-sm">

                        <p class="text-sm text-red-700 whitespace-pre-wrap"></p>

                    </div>

                    <div class="text-[10px] md:text-xs text-red-600 mt-1.5 font-semibold">
                        System Error
                    </div>

                </div>
            `;


                const paragraph =
                    wrapper.querySelector('p');


                if (paragraph) {
                    paragraph.textContent = message;
                }


                chatMessages.appendChild(wrapper);

                scrollToBottom();

            }


            /* =========================================================
               SUBMIT CHAT
            ========================================================== */

            chatForm.addEventListener(
                'submit',
                async function(event) {

                    event.preventDefault();


                    const message =
                        messageInput.value.trim();


                    if (!message) {
                        return;
                    }


                    console.log(
                        'Sending:',
                        message
                    );


                    addUserMessage(message);


                    messageInput.value =
                        '';

                    messageInput.style.height =
                        'auto';


                    sendBtn.disabled =
                        true;

                    sendBtnText.textContent =
                        'Thinking...';

                    sendBtnIcon.innerHTML = `
                        <svg class="w-4 h-4 animate-spin text-[#FFD700]" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    `;


                    addLoadingMessage();


                    try {

                        const csrfMeta =
                            document.querySelector(
                                'meta[name="csrf-token"]'
                            );


                        const csrfToken =
                            csrfMeta ?
                            csrfMeta.getAttribute(
                                'content'
                            ) :
                            '';


                        const response =
                            await fetch(
                                '/backend/chat', {
                                    method: 'POST',

                                    headers: {
                                        'Content-Type': 'application/json',

                                        'Accept': 'application/json',

                                        'X-CSRF-TOKEN': csrfToken
                                    },

                                    body: JSON.stringify({
                                        message: message,
                                        history: conversationHistory
                                    })
                                }
                            );


                        let data;


                        try {

                            data =
                                await response.json();

                        } catch (jsonError) {

                            console.error(
                                'Invalid JSON response:',
                                jsonError
                            );

                            throw new Error(
                                'The server returned an invalid response.'
                            );

                        }


                        removeLoadingMessage();


                        if (
                            !response.ok ||
                            data?.error
                        ) {

                            addErrorMessage(
                                data?.message ||
                                `Server error: HTTP ${response.status}`
                            );

                            return;
                        }


                        addAIMessage(
                            data?.answer ||
                            'No answer was generated.'
                        );


                    } catch (error) {

                        console.error(
                            'Chat request failed:',
                            error
                        );


                        removeLoadingMessage();


                        addErrorMessage(
                            error?.message ||
                            'Unable to connect to the chatbot server.'
                        );


                    } finally {

                        sendBtn.disabled =
                            false;

                        sendBtnText.textContent =
                            'Send';

                        sendBtnIcon.innerHTML = `
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                            </svg>
                        `;

                        messageInput.focus();

                    }

                }
            );


            /* =========================================================
               ENTER TO SEND
               
               Enter = Send
               Shift + Enter = New Line
            ========================================================== */

            messageInput.addEventListener(
                'keydown',
                function(event) {

                    if (
                        event.key === 'Enter' &&
                        !event.shiftKey
                    ) {

                        event.preventDefault();


                        chatForm.dispatchEvent(
                            new Event(
                                'submit', {
                                    cancelable: true,
                                    bubbles: true
                                }
                            )
                        );

                    }

                }
            );


            /* =========================================================
               TEXTAREA AUTO RESIZE
            ========================================================== */

            messageInput.addEventListener(
                'input',
                function() {

                    this.style.height =
                        'auto';


                    this.style.height =
                        Math.min(
                            this.scrollHeight,
                            150
                        ) + 'px';

                }
            );


            /* =========================================================
               ASK AI REDIRECT HANDLING
               
               Example:
               
               /chat?q=Tell%20me%20about%20the%20thesis
            ========================================================== */

            const urlParams =
                new URLSearchParams(
                    window.location.search
                );


            const initialQuery =
                urlParams.get('q');


            console.log(
                'Initial query:',
                initialQuery
            );


            if (
                initialQuery &&
                initialQuery.trim()
            ) {

                const question =
                    initialQuery.trim();


                messageInput.value =
                    question;


                messageInput.style.height =
                    'auto';


                messageInput.style.height =
                    Math.min(
                        messageInput.scrollHeight,
                        150
                    ) + 'px';


                /*
                 * IMPORTANT:
                 * The submit listener has already been
                 * registered above.
                 */

                setTimeout(
                    function() {

                        console.log(
                            'Auto-sending Ask AI question:',
                            question
                        );


                        chatForm.dispatchEvent(
                            new Event(
                                'submit', {
                                    cancelable: true,
                                    bubbles: true
                                }
                            )
                        );

                    },
                    300
                );

            }


            /* =========================================================
               INITIAL SCROLL
            ========================================================== */

            scrollToBottom();


            console.log(
                'SAC Thesis AI Chat initialized successfully.'
            );

        });
    </script>

</body>

</html>