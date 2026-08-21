<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SAC Thesis System - AI Assistant</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            background: #850000;
            background: linear-gradient(327deg, rgba(133, 0, 0, 1) 0%, rgba(255, 255, 255, 1) 50%, rgba(133, 0, 0, 1) 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }
    </style>
</head>

<body class="text-slate-800 min-h-screen font-sans">

    @include('partials.sidebar')

    <div class="md:ml-64 flex h-screen flex-col">

        <!-- HEADER -->
        <header class="flex items-center justify-between gap-3 border-b border-gray-200 bg-white/95 backdrop-blur-md px-4 md:px-6 py-4 shadow-sm">

            <div>
                <h1 class="text-base md:text-lg font-bold text-[#700000]">
                    AI Assistant
                </h1>

                <p class="mt-0.5 text-xs md:text-sm text-gray-500">
                    Ask questions about uploaded thesis documents.
                </p>
            </div>

            <div class="rounded-lg bg-[#700000]/10 px-3 py-1.5 text-xs text-[#700000] font-bold border border-[#700000]/20">
                RAG Thesis Assistant
            </div>

        </header>


        <!-- CHAT AREA -->
        <div
            id="chatMessages"
            class="flex-1 overflow-y-auto px-4 md:px-6 py-6 space-y-6 bg-transparent"
        >

            <!-- INITIAL AI MESSAGE -->
            <div class="flex items-start gap-3">

                <div class="w-8 h-8 md:w-9 md:h-9 rounded-lg bg-[#700000] text-[#FFD700] flex-shrink-0 flex items-center justify-center text-sm md:text-base font-bold shadow-md">
                    🤖
                </div>

                <div class="max-w-3xl">

                    <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-md px-4 py-3 shadow-sm">

                        <p class="text-sm text-gray-800 font-medium">
                            👋 Hi! I'm your RAG Thesis AI Assistant.
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
                class="flex items-end gap-2 md:gap-3 w-full"
            >

                <div class="flex-1">

                    <textarea
                        id="messageInput"
                        rows="1"
                        required
                        placeholder="Ask a question..."
                        class="w-full resize-none bg-slate-50 border border-gray-300 rounded-xl px-3.5 md:px-4 py-2.5 md:py-3 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000] leading-normal"
                    ></textarea>

                </div>

                <button
                    type="submit"
                    id="sendBtn"
                    class="h-[42px] md:h-[46px] bg-[#700000] hover:bg-[#800000] disabled:bg-gray-200 disabled:text-gray-400 text-[#FFD700] font-bold px-4 md:px-6 rounded-xl text-xs md:text-sm transition flex items-center justify-center gap-2 shrink-0 shadow-sm"
                >

                    <span id="sendBtnText">
                        Send
                    </span>

                    <span id="sendBtnIcon">
                        ➤
                    </span>

                </button>

            </form>

        </div>

    </div>


    <!-- =========================================================
         JAVASCRIPT
    ========================================================== -->

    <script>

    document.addEventListener('DOMContentLoaded', function () {

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

        function addAIMessage(answer, sources) {

            sources =
                Array.isArray(sources)
                    ? sources
                    : [];


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

                <div class="w-8 h-8 md:w-9 md:h-9 rounded-lg bg-[#700000] text-[#FFD700] flex-shrink-0 flex items-center justify-center text-sm md:text-base font-bold shadow-md">
                    🤖
                </div>

                <div class="max-w-xl md:max-w-3xl flex-1">

                    <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-md px-4 py-3.5 shadow-sm">

                        <div class="text-sm text-gray-800 ai-answer leading-relaxed prose prose-sm max-w-none">
                            ${formattedAnswer}
                        </div>

                        ${
                            sources.length > 0
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


            /* =====================================================
               SOURCES
            ====================================================== */

            if (sources.length > 0) {

                const sourceList =
                    wrapper.querySelector('.source-list');


                if (sourceList) {

                    sources.forEach(function (source) {

                        if (!source) {
                            return;
                        }


                        const sourceElement =
                            document.createElement('div');


                        sourceElement.className =
                            'text-xs text-gray-700 bg-slate-50 border border-gray-200 rounded-xl p-2.5 flex items-center justify-between gap-3';


                        const title =
                            source.document_title ||
                            (
                                'Thesis #' +
                                (
                                    source.document_id ??
                                    'Unknown'
                                )
                            );


                        const author =
                            source.document_author
                                ? 'by ' +
                                  source.document_author
                                : '';


                        let similarity = null;


                        if (
                            source.similarity !== undefined &&
                            source.similarity !== null
                        ) {

                            similarity =
                                Math.round(
                                    Number(
                                        source.similarity
                                    ) * 100
                                );

                        }


                        let documentId = '';


                        if (
                            source.document_id !== undefined &&
                            source.document_id !== null
                        ) {

                            documentId =
                                encodeURIComponent(
                                    String(
                                        source.document_id
                                    )
                                );

                        }


                        sourceElement.innerHTML = `

                            <div class="flex-1 min-w-0">

                                <p class="font-bold text-gray-900 truncate">
                                    ${escapeHtml(title)}
                                </p>

                                <p class="text-[11px] text-gray-500 truncate">
                                    ${escapeHtml(author)}
                                </p>

                            </div>

                            <div class="flex items-center gap-2 shrink-0">

                                ${
                                    similarity !== null &&
                                    !Number.isNaN(similarity)
                                    ? `
                                        <span class="text-[10px] font-bold text-[#700000] bg-[#700000]/10 px-2 py-0.5 rounded-md">
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
                                            class="rounded-lg bg-[#700000] px-2.5 py-1 text-[11px] font-bold text-[#FFD700] hover:bg-[#800000]"
                                        >
                                            View PDF
                                        </a>
                                    `
                                    : ''
                                }

                            </div>
                        `;


                        sourceList.appendChild(
                            sourceElement
                        );

                    });

                }

            }


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

                <div class="w-8 h-8 md:w-9 md:h-9 rounded-lg bg-[#700000] text-[#FFD700] flex-shrink-0 flex items-center justify-center font-bold shadow-md">
                    🤖
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

                <div class="w-8 h-8 md:w-9 md:h-9 rounded-lg bg-red-600 text-white flex-shrink-0 flex items-center justify-center font-bold shadow-md">
                    ⚠️
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
            async function (event) {

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

                sendBtnIcon.textContent =
                    '⏳';


                addLoadingMessage();


                try {

                    const csrfMeta =
                        document.querySelector(
                            'meta[name="csrf-token"]'
                        );


                    const csrfToken =
                        csrfMeta
                            ? csrfMeta.getAttribute(
                                'content'
                            )
                            : '';


                    const response =
                        await fetch(
                            '/backend/chat',
                            {
                                method: 'POST',

                                headers: {
                                    'Content-Type':
                                        'application/json',

                                    'Accept':
                                        'application/json',

                                    'X-CSRF-TOKEN':
                                        csrfToken
                                },

                                body: JSON.stringify({
                                    message: message
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
                        'No answer was generated.',
                        data?.sources || []
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

                    sendBtnIcon.textContent =
                        '➤';

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
            function (event) {

                if (
                    event.key === 'Enter' &&
                    !event.shiftKey
                ) {

                    event.preventDefault();


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


        /* =========================================================
           TEXTAREA AUTO RESIZE
        ========================================================== */

        messageInput.addEventListener(
            'input',
            function () {

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
                function () {

                    console.log(
                        'Auto-sending Ask AI question:',
                        question
                    );


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