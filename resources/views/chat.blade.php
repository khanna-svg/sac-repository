<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>SAC Thesis System - AI Assistant</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="bg-slate-50 text-slate-800 min-h-screen font-sans">

    @include('partials.sidebar')

    <div class="md:ml-64 flex h-screen flex-col transition-all">

        <!-- Top Bar -->
        <header
            class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 bg-white px-4 md:px-6 py-4 shadow-sm">

            <div>
                <h1 class="text-base md:text-lg font-bold text-[#700000]">
                    AI Assistant
                </h1>

                <p class="mt-0.5 text-xs md:text-sm text-gray-500">
                    Ask questions about uploaded thesis documents.
                </p>
            </div>

            <div
                class="self-start sm:self-auto rounded-lg bg-[#700000]/10 px-3 py-1.5 text-xs text-[#700000] font-bold border border-[#700000]/20">
                RAG Thesis Assistant
            </div>

        </header>

        <!-- Chat Area -->
        <div
            id="chatMessages"
            class="flex-1 overflow-y-auto px-4 md:px-6 py-6 space-y-6 bg-slate-50"
        >

            <!-- Welcome Message -->
            <div class="flex items-start gap-3">

                <div
                    class="w-8 h-8 md:w-9 md:h-9 rounded-lg bg-[#700000] text-[#FFD700] flex-shrink-0 flex items-center justify-center text-sm md:text-base font-bold shadow-md">
                    🤖
                </div>

                <div class="max-w-3xl">

                    <div
                        class="bg-white border border-gray-200 rounded-2xl rounded-tl-md px-3.5 md:px-4 py-3 shadow-sm">

                        <p class="text-sm text-gray-800 font-medium">
                            👋 Hi! I'm your RAG Thesis AI Assistant.
                        </p>

                        <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                            Ask me anything about the uploaded thesis papers.
                            I'll search the repository and use the relevant documents
                            to answer your question.
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

            <form
                id="chatForm"
                class="flex items-end sm:items-center gap-2 md:gap-3 w-full"
            >

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

                    <span id="sendBtnText" class="hidden sm:inline">
                        Send
                    </span>

                    <span id="sendBtnIcon">
                        ➤
                    </span>

                </button>

            </form>

        </div>

    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {

            /*
            |--------------------------------------------------------------------------
            | Get DOM Elements
            |--------------------------------------------------------------------------
            */

            const chatForm = document.getElementById('chatForm');
            const messageInput = document.getElementById('messageInput');
            const sendBtn = document.getElementById('sendBtn');
            const sendBtnText = document.getElementById('sendBtnText');
            const sendBtnIcon = document.getElementById('sendBtnIcon');
            const chatMessages = document.getElementById('chatMessages');


            /*
            |--------------------------------------------------------------------------
            | Safety Check
            |--------------------------------------------------------------------------
            |
            | This prevents:
            |
            | Cannot read properties of null
            |
            | when an expected HTML element does not exist.
            |
            */

            if (!chatForm) {
                console.error('Chat error: #chatForm was not found.');
                return;
            }

            if (!messageInput) {
                console.error('Chat error: #messageInput was not found.');
                return;
            }

            if (!sendBtn) {
                console.error('Chat error: #sendBtn was not found.');
                return;
            }

            if (!sendBtnText) {
                console.error('Chat error: #sendBtnText was not found.');
                return;
            }

            if (!sendBtnIcon) {
                console.error('Chat error: #sendBtnIcon was not found.');
                return;
            }

            if (!chatMessages) {
                console.error('Chat error: #chatMessages was not found.');
                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Scroll Chat To Bottom
            |--------------------------------------------------------------------------
            */

            function scrollToBottom() {

                if (!chatMessages) {
                    return;
                }

                chatMessages.scrollTo({
                    top: chatMessages.scrollHeight,
                    behavior: 'smooth'
                });
            }


            /*
            |--------------------------------------------------------------------------
            | Add User Message
            |--------------------------------------------------------------------------
            */

            function addUserMessage(message) {

                if (!chatMessages) {
                    return;
                }

                const messageWrapper = document.createElement('div');

                messageWrapper.className =
                    'flex justify-end items-start gap-3';

                messageWrapper.innerHTML = `
                    <div class="max-w-xl md:max-w-3xl">

                        <div class="bg-[#700000] rounded-2xl rounded-tr-md px-3.5 md:px-4 py-3 shadow-sm">

                            <p class="text-sm text-white whitespace-pre-wrap"></p>

                        </div>

                        <div class="text-[10px] md:text-xs text-gray-500 mt-1.5 text-right font-medium">
                            You
                        </div>

                    </div>
                `;

                const paragraph = messageWrapper.querySelector('p');

                if (paragraph) {
                    paragraph.textContent = message;
                }

                chatMessages.appendChild(messageWrapper);

                scrollToBottom();
            }


            /*
            |--------------------------------------------------------------------------
            | Add AI Message
            |--------------------------------------------------------------------------
            */

            function addAIMessage(answer, sources = []) {

                if (!chatMessages) {
                    return;
                }

                const messageWrapper = document.createElement('div');

                messageWrapper.className =
                    'flex items-start gap-3';


                /*
                |--------------------------------------------------------------------------
                | Parse Markdown
                |--------------------------------------------------------------------------
                */

                let formattedHtml = answer;

                if (
                    typeof marked !== 'undefined' &&
                    marked &&
                    typeof marked.parse === 'function'
                ) {
                    formattedHtml = marked.parse(answer);
                }


                /*
                |--------------------------------------------------------------------------
                | Build AI Message
                |--------------------------------------------------------------------------
                */

                messageWrapper.innerHTML = `
                    <div
                        class="w-8 h-8 md:w-9 md:h-9 rounded-lg bg-[#700000] text-[#FFD700] flex-shrink-0 flex items-center justify-center text-sm md:text-base font-bold shadow-md">
                        🤖
                    </div>

                    <div class="max-w-xl md:max-w-3xl flex-1">

                        <div
                            class="bg-white border border-gray-200 rounded-2xl rounded-tl-md px-4 py-3.5 shadow-sm">

                            <div
                                class="text-sm text-gray-800 ai-answer leading-relaxed prose prose-sm max-w-none space-y-2">
                                ${formattedHtml}
                            </div>

                            ${
                                Array.isArray(sources) && sources.length > 0
                                    ? `
                                        <div class="mt-4 pt-3 border-t border-gray-100">

                                            <p class="text-xs font-bold text-[#700000] mb-2">
                                                📚 Referenced Theses
                                            </p>

                                            <div class="space-y-2 source-list"></div>

                                        </div>
                                    `
                                    : ''
                            }

                        </div>

                        <div class="text-[10px] md:text-xs text-[#700000] mt-1.5 font-semibold">
                            AI Thesis Assistant
                        </div>

                    </div>
                `;


                /*
                |--------------------------------------------------------------------------
                | Add Sources
                |--------------------------------------------------------------------------
                */

                if (Array.isArray(sources) && sources.length > 0) {

                    const sourceList =
                        messageWrapper.querySelector('.source-list');

                    if (sourceList) {

                        sources.forEach(function (source) {

                            if (!source) {
                                return;
                            }

                            const sourceElement =
                                document.createElement('div');

                            sourceElement.className =
                                'text-xs text-gray-700 bg-slate-50 border border-gray-200 rounded-xl p-2.5 flex items-center justify-between gap-3 hover:border-[#700000]/40 transition';


                            /*
                            |--------------------------------------------------------------------------
                            | Source Data
                            |--------------------------------------------------------------------------
                            */

                            const similarity =
                                source.similarity !== undefined &&
                                source.similarity !== null
                                    ? Math.round(
                                        Number(source.similarity) * 100
                                    )
                                    : null;

                            const title =
                                source.document_title ||
                                `Thesis #${source.document_id ?? 'Unknown'}`;

                            const author =
                                source.document_author
                                    ? `by ${source.document_author}`
                                    : '';


                            /*
                            |--------------------------------------------------------------------------
                            | Escape HTML
                            |--------------------------------------------------------------------------
                            |
                            | Prevents document titles/authors from injecting
                            | arbitrary HTML into the chat.
                            |
                            */

                            function escapeHtml(value) {

                                return String(value)
                                    .replace(/&/g, '&amp;')
                                    .replace(/</g, '&lt;')
                                    .replace(/>/g, '&gt;')
                                    .replace(/"/g, '&quot;')
                                    .replace(/'/g, '&#039;');
                            }


                            const safeTitle =
                                escapeHtml(title);

                            const safeAuthor =
                                escapeHtml(author);

                            const documentId =
                                encodeURIComponent(
                                    String(
                                        source.document_id ?? ''
                                    )
                                );


                            sourceElement.innerHTML = `
                                <div class="flex-1 min-w-0">

                                    <p class="font-bold text-gray-900 truncate">
                                        ${safeTitle}
                                    </p>

                                    <p class="text-[11px] text-gray-500 truncate">
                                        ${safeAuthor}
                                    </p>

                                </div>

                                <div class="flex items-center gap-2 shrink-0">

                                    ${
                                        similarity !== null &&
                                        !Number.isNaN(similarity)
                                            ? `
                                                <span
                                                    class="text-[10px] font-bold text-[#700000] bg-[#700000]/10 px-2 py-0.5 rounded-md">
                                                    ${similarity}% match
                                                </span>
                                            `
                                            : ''
                                    }

                                    ${
                                        documentId
                                            ? `
                                                <a
                                                    href="/backend/documents/${documentId}/view"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="rounded-lg bg-[#700000] px-2.5 py-1 text-[11px] font-bold text-[#FFD700] hover:bg-[#800000] transition">
                                                    View PDF
                                                </a>
                                            `
                                            : ''
                                    }

                                </div>
                            `;

                            sourceList.appendChild(sourceElement);

                        });
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Append Message
                |--------------------------------------------------------------------------
                */

                chatMessages.appendChild(messageWrapper);

                scrollToBottom();
            }


            /*
            |--------------------------------------------------------------------------
            | Add Loading Message
            |--------------------------------------------------------------------------
            */

            function addLoadingMessage() {

                if (!chatMessages) {
                    return;
                }

                /*
                | Remove an existing loading message first.
                */

                const existingLoading =
                    document.getElementById('loadingMessage');

                if (existingLoading) {
                    existingLoading.remove();
                }


                const loadingWrapper =
                    document.createElement('div');

                loadingWrapper.id = 'loadingMessage';

                loadingWrapper.className =
                    'flex items-start gap-3';

                loadingWrapper.innerHTML = `
                    <div
                        class="w-8 h-8 md:w-9 md:h-9 rounded-lg bg-[#700000] text-[#FFD700] flex-shrink-0 flex items-center justify-center text-sm md:text-base font-bold shadow-md">
                        🤖
                    </div>

                    <div>

                        <div
                            class="bg-white border border-gray-200 rounded-2xl rounded-tl-md px-4 py-3 shadow-sm">

                            <div class="flex items-center gap-1.5">

                                <span
                                    class="w-2 h-2 bg-[#700000] rounded-full animate-bounce">
                                </span>

                                <span
                                    class="w-2 h-2 bg-[#700000] rounded-full animate-bounce"
                                    style="animation-delay: 0.15s">
                                </span>

                                <span
                                    class="w-2 h-2 bg-[#700000] rounded-full animate-bounce"
                                    style="animation-delay: 0.3s">
                                </span>

                            </div>

                        </div>

                        <div
                            class="text-[10px] md:text-xs text-gray-500 mt-1.5 font-medium">
                            AI Thesis Assistant is searching...
                        </div>

                    </div>
                `;

                chatMessages.appendChild(loadingWrapper);

                scrollToBottom();
            }


            /*
            |--------------------------------------------------------------------------
            | Remove Loading Message
            |--------------------------------------------------------------------------
            */

            function removeLoadingMessage() {

                const loadingMessage =
                    document.getElementById('loadingMessage');

                if (loadingMessage) {
                    loadingMessage.remove();
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Add Error Message
            |--------------------------------------------------------------------------
            */

            function addErrorMessage(message) {

                if (!chatMessages) {
                    return;
                }

                const errorWrapper =
                    document.createElement('div');

                errorWrapper.className =
                    'flex items-start gap-3';

                errorWrapper.innerHTML = `
                    <div
                        class="w-8 h-8 md:w-9 md:h-9 rounded-lg bg-red-600 text-white flex-shrink-0 flex items-center justify-center text-sm md:text-base font-bold shadow-md">
                        ⚠️
                    </div>

                    <div class="max-w-xl md:max-w-3xl">

                        <div
                            class="bg-red-50 border border-red-200 rounded-2xl rounded-tl-md px-4 py-3 shadow-sm">

                            <p class="text-sm text-red-700 whitespace-pre-wrap"></p>

                        </div>

                        <div
                            class="text-[10px] md:text-xs text-red-600 mt-1.5 font-semibold">
                            System Error
                        </div>

                    </div>
                `;

                const paragraph =
                    errorWrapper.querySelector('p');

                if (paragraph) {
                    paragraph.textContent = message;
                }

                chatMessages.appendChild(errorWrapper);

                scrollToBottom();
            }


            /*
            |--------------------------------------------------------------------------
            | Submit Chat Form
            |--------------------------------------------------------------------------
            */

            chatForm.addEventListener('submit', async function (e) {

                e.preventDefault();


                /*
                |--------------------------------------------------------------------------
                | Get Message
                |--------------------------------------------------------------------------
                */

                const message =
                    messageInput.value.trim();

                if (!message) {
                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Disable Button
                |--------------------------------------------------------------------------
                */

                addUserMessage(message);

                messageInput.value = '';
                messageInput.style.height = 'auto';

                sendBtn.disabled = true;

                sendBtnText.textContent = 'Thinking...';

                sendBtnIcon.textContent = '⏳';


                /*
                |--------------------------------------------------------------------------
                | Show Loading
                |--------------------------------------------------------------------------
                */

                addLoadingMessage();


                try {

                    /*
                    |--------------------------------------------------------------------------
                    | CSRF Token
                    |--------------------------------------------------------------------------
                    */

                    const csrfMeta =
                        document.querySelector(
                            'meta[name="csrf-token"]'
                        );

                    const csrfToken =
                        csrfMeta
                            ? csrfMeta.getAttribute('content')
                            : '';


                    /*
                    |--------------------------------------------------------------------------
                    | Send Request
                    |--------------------------------------------------------------------------
                    */

                    const response = await fetch(
                        '/backend/chat',
                        {
                            method: 'POST',

                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },

                            body: JSON.stringify({
                                message: message
                            })
                        }
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Read Response
                    |--------------------------------------------------------------------------
                    */

                    let data = null;

                    try {
                        data = await response.json();
                    } catch (jsonError) {

                        console.error(
                            'Invalid JSON response:',
                            jsonError
                        );

                        throw new Error(
                            'The server returned an invalid response.'
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Remove Loading
                    |--------------------------------------------------------------------------
                    */

                    removeLoadingMessage();


                    /*
                    |--------------------------------------------------------------------------
                    | Handle Backend Error
                    |--------------------------------------------------------------------------
                    */

                    if (!response.ok || (data && data.error)) {

                        addErrorMessage(
                            data?.message ||
                            `Server error (${response.status}). Please try again.`
                        );

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Add AI Response
                    |--------------------------------------------------------------------------
                    */

                    addAIMessage(
                        data?.answer ||
                        'No answer was generated.',
                        Array.isArray(data?.sources)
                            ? data.sources
                            : []
                    );


                } catch (error) {

                    console.error(
                        'Chat request error:',
                        error
                    );

                    removeLoadingMessage();

                    addErrorMessage(
                        error?.message ||
                        'Unable to connect to the chatbot server. Please make sure the backend is running.'
                    );


                } finally {

                    /*
                    |--------------------------------------------------------------------------
                    | Re-enable Button
                    |--------------------------------------------------------------------------
                    */

                    sendBtn.disabled = false;

                    sendBtnText.textContent = 'Send';

                    sendBtnIcon.textContent = '➤';

                    messageInput.focus();
                }

            });


            /*
            |--------------------------------------------------------------------------
            | Enter Key
            |--------------------------------------------------------------------------
            |
            | Enter = Send
            | Shift + Enter = New Line
            |
            */

            messageInput.addEventListener(
                'keydown',
                function (e) {

                    if (
                        e.key === 'Enter' &&
                        !e.shiftKey
                    ) {

                        e.preventDefault();

                        chatForm.dispatchEvent(
                            new Event(
                                'submit',
                                {
                                    cancelable: true,
                                    bubbles: true
                                }
                            )
                        );
                    }

                }
            );


            /*
            |--------------------------------------------------------------------------
            | Auto Resize Textarea
            |--------------------------------------------------------------------------
            */

            messageInput.addEventListener(
                'input',
                function () {

                    this.style.height = 'auto';

                    this.style.height =
                        Math.min(
                            this.scrollHeight,
                            150
                        ) + 'px';
                }
            );


            /*
            |--------------------------------------------------------------------------
            | Automatically Submit ?q= URL Parameter
            |--------------------------------------------------------------------------
            |
            | Example:
            |
            | /chat?q=Tell+me+about+the+thesis
            |
            */

            const urlParams =
                new URLSearchParams(
                    window.location.search
                );

            const initialQuery =
                urlParams.get('q');


            if (
                initialQuery &&
                initialQuery.trim()
            ) {

                messageInput.value =
                    initialQuery.trim();


                /*
                | Resize textarea for the initial question.
                */

                messageInput.style.height = 'auto';

                messageInput.style.height =
                    Math.min(
                        messageInput.scrollHeight,
                        150
                    ) + 'px';


                /*
                | Automatically submit after page loads.
                */

                setTimeout(
                    function () {

                        chatForm.dispatchEvent(
                            new Event(
                                'submit',
                                {
                                    cancelable: true,
                                    bubbles: true
                                }
                            )
                        );

                    },
                    300
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Initial Scroll
            |--------------------------------------------------------------------------
            */

            scrollToBottom();


            /*
            |--------------------------------------------------------------------------
            | Debug Message
            |--------------------------------------------------------------------------
            */

            console.log(
                'Thesis AI Chat initialized successfully.'
            );

        });
    </script>

</body>

</html>