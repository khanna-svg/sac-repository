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

    <div class="md:ml-64 flex h-screen flex-col transition-all">
        <header
            class="flex flex-col sm:flex-row sm:items-center justify-between gap-3
                   border-b border-gray-200 bg-white px-4 md:px-6 py-4 shadow-sm">

            <div>
                <h1 class="text-base md:text-lg font-bold text-[#700000]">
                    AI Assistant
                </h1>

                <p class="mt-0.5 text-xs md:text-sm text-gray-500">
                    Ask questions about uploaded thesis documents.
                </p>
            </div>

            <div
                class="self-start sm:self-auto rounded-lg bg-[#700000]/10
                       px-3 py-1.5 text-xs text-[#700000] font-bold
                       border border-[#700000]/20">
                RAG Thesis Assistant
            </div>

        </header>

        <div
            id="chatMessages"
            class="flex-1 overflow-y-auto px-4 md:px-6 py-6
                   space-y-6 bg-slate-50">

            <!-- Welcome Message -->
            <div class="flex items-start gap-3">

                <div
                    class="w-8 h-8 md:w-9 md:h-9 rounded-lg
                           bg-[#700000] text-[#FFD700] flex-shrink-0
                           flex items-center justify-center
                           text-sm md:text-base font-bold shadow-md">
                    🤖
                </div>

                <div class="max-w-3xl">

                    <div
                        class="bg-white border border-gray-200
                               rounded-2xl rounded-tl-md
                               px-3.5 md:px-4 py-3 shadow-sm">

                        <p class="text-sm text-gray-800 font-medium">
                            👋 Hi! I'm your RAG Thesis AI Assistant.
                        </p>

                        <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                            Ask me anything about the uploaded thesis papers.
                            I'll search the repository and use the relevant
                            documents to answer your question.
                        </p>

                    </div>

                    <div
                        class="text-[10px] md:text-xs text-[#700000]
                               mt-1.5 font-semibold">
                        AI Thesis Assistant
                    </div>

                </div>

            </div>

        </div>

        <div
            class="border-t border-gray-200 bg-white
                   px-4 md:px-6 py-3 md:py-4">

            <form
                id="chatForm"
                class="flex items-end sm:items-center
                       gap-2 md:gap-3 w-full">

                <div class="flex-1 relative">

                    <textarea
                        id="messageInput"
                        rows="1"
                        required
                        placeholder="Ask a question..."
                        class="w-full resize-none bg-slate-50
                               border border-gray-300 rounded-xl
                               px-3.5 md:px-4 py-2.5 md:py-3
                               text-sm text-gray-800
                               placeholder-gray-400
                               focus:outline-none
                               focus:border-[#700000]
                               focus:ring-1
                               focus:ring-[#700000]
                               block leading-normal"></textarea>

                </div>

                <button
                    type="submit"
                    id="sendBtn"
                    class="h-[42px] md:h-[46px]
                           bg-[#700000]
                           hover:bg-[#800000]
                           disabled:bg-gray-200
                           disabled:text-gray-400
                           text-[#FFD700]
                           font-bold
                           px-4 md:px-6
                           rounded-xl
                           text-xs md:text-sm
                           transition
                           flex items-center
                           justify-center
                           gap-1.5 md:gap-2
                           shrink-0 shadow-sm">

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
        document.addEventListener('DOMContentLoaded', function() {

            console.log('======================================');
            console.log('SAC Thesis AI Chat');
            console.log('Initializing...');
            console.log('======================================');

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

            if (!chatForm) {
                console.error(
                    'ERROR: #chatForm was not found.'
                );
                return;
            }

            if (!messageInput) {
                console.error(
                    'ERROR: #messageInput was not found.'
                );
                return;
            }

            if (!sendBtn) {
                console.error(
                    'ERROR: #sendBtn was not found.'
                );
                return;
            }

            if (!sendBtnText) {
                console.error(
                    'ERROR: #sendBtnText was not found.'
                );
                return;
            }

            if (!sendBtnIcon) {
                console.error(
                    'ERROR: #sendBtnIcon was not found.'
                );
                return;
            }

            if (!chatMessages) {
                console.error(
                    'ERROR: #chatMessages was not found.'
                );
                return;
            }


            console.log(
                'All chat elements found successfully.'
            );

            function scrollToBottom() {

                chatMessages.scrollTo({
                    top: chatMessages.scrollHeight,
                    behavior: 'smooth'
                });

            }

            function addUserMessage(message) {

                const wrapper =
                    document.createElement('div');

                wrapper.className =
                    'flex justify-end items-start gap-3';


                wrapper.innerHTML = `

                    <div class="max-w-xl md:max-w-3xl">

                        <div
                            class="bg-[#700000]
                                   rounded-2xl rounded-tr-md
                                   px-3.5 md:px-4 py-3
                                   shadow-sm">

                            <p
                                class="text-sm text-white
                                       whitespace-pre-wrap">
                            </p>

                        </div>

                        <div
                            class="text-[10px] md:text-xs
                                   text-gray-500
                                   mt-1.5 text-right
                                   font-medium">
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

            function escapeHtml(value) {

                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');

            }

            function addAIMessage(
                answer,
                sources = []
            ) {

                const wrapper =
                    document.createElement('div');

                wrapper.className =
                    'flex items-start gap-3';


                /*
                 * Markdown support
                 */

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

                    <div
                        class="w-8 h-8 md:w-9 md:h-9
                               rounded-lg bg-[#700000]
                               text-[#FFD700]
                               flex-shrink-0
                               flex items-center
                               justify-center
                               text-sm md:text-base
                               font-bold shadow-md">
                        🤖
                    </div>


                    <div
                        class="max-w-xl md:max-w-3xl
                               flex-1">

                        <div
                            class="bg-white
                                   border border-gray-200
                                   rounded-2xl
                                   rounded-tl-md
                                   px-4 py-3.5
                                   shadow-sm">

                            <div
                                class="text-sm text-gray-800
                                       leading-relaxed
                                       prose prose-sm
                                       max-w-none
                                       space-y-2">

                                ${formattedAnswer}

                            </div>


                            ${
                                Array.isArray(sources) &&
                                sources.length > 0
                                ? `

                                    <div
                                        class="mt-4 pt-3
                                               border-t
                                               border-gray-100">

                                        <p
                                            class="text-xs
                                                   font-bold
                                                   text-[#700000]
                                                   mb-2">
                                            📚 Referenced Theses
                                        </p>

                                        <div
                                            class="space-y-2
                                                   source-list">
                                        </div>

                                    </div>

                                `
                                : ''
                            }

                        </div>


                        <div
                            class="text-[10px] md:text-xs
                                   text-[#700000]
                                   mt-1.5
                                   font-semibold">
                            AI Thesis Assistant
                        </div>

                    </div>
                `;

                if (
                    Array.isArray(sources) &&
                    sources.length > 0
                ) {

                    const sourceList =
                        wrapper.querySelector(
                            '.source-list'
                        );


                    if (sourceList) {

                        sources.forEach(function(source) {

                            if (!source) {
                                return;
                            }


                            const sourceElement =
                                document.createElement('div');


                            sourceElement.className =
                                'text-xs text-gray-700 ' +
                                'bg-slate-50 ' +
                                'border border-gray-200 ' +
                                'rounded-xl p-2.5 ' +
                                'flex items-center ' +
                                'justify-between ' +
                                'gap-3';


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
                                source.document_author ?
                                'by ' +
                                source.document_author :
                                '';


                            const similarity =
                                source.similarity !== undefined &&
                                source.similarity !== null ?
                                Math.round(
                                    Number(
                                        source.similarity
                                    ) * 100
                                ) :
                                null;


                            const documentId =
                                source.document_id !== undefined &&
                                source.document_id !== null ?
                                encodeURIComponent(
                                    String(
                                        source.document_id
                                    )
                                ) :
                                '';


                            sourceElement.innerHTML = `

                                <div
                                    class="flex-1
                                           min-w-0">

                                    <p
                                        class="font-bold
                                               text-gray-900
                                               truncate">
                                        ${escapeHtml(title)}
                                    </p>

                                    <p
                                        class="text-[11px]
                                               text-gray-500
                                               truncate">
                                        ${escapeHtml(author)}
                                    </p>

                                </div>


                                <div
                                    class="flex items-center
                                           gap-2 shrink-0">

                                    ${
                                        similarity !== null &&
                                        !Number.isNaN(
                                            similarity
                                        )
                                        ? `
                                            <span
                                                class="text-[10px]
                                                       font-bold
                                                       text-[#700000]
                                                       bg-[#700000]/10
                                                       px-2 py-0.5
                                                       rounded-md">

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
                                                class="rounded-lg
                                                       bg-[#700000]
                                                       px-2.5 py-1
                                                       text-[11px]
                                                       font-bold
                                                       text-[#FFD700]
                                                       hover:bg-[#800000]">

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

            function addLoadingMessage() {

                removeLoadingMessage();


                const wrapper =
                    document.createElement('div');


                wrapper.id =
                    'loadingMessage';


                wrapper.className =
                    'flex items-start gap-3';


                wrapper.innerHTML = `

                    <div
                        class="w-8 h-8 md:w-9 md:h-9
                               rounded-lg
                               bg-[#700000]
                               text-[#FFD700]
                               flex-shrink-0
                               flex items-center
                               justify-center
                               font-bold shadow-md">
                        🤖
                    </div>


                    <div>

                        <div
                            class="bg-white
                                   border border-gray-200
                                   rounded-2xl
                                   rounded-tl-md
                                   px-4 py-3
                                   shadow-sm">

                            <div
                                class="flex items-center
                                       gap-1.5">

                                <span
                                    class="w-2 h-2
                                           bg-[#700000]
                                           rounded-full
                                           animate-bounce">
                                </span>

                                <span
                                    class="w-2 h-2
                                           bg-[#700000]
                                           rounded-full
                                           animate-bounce"
                                    style="animation-delay: .15s">
                                </span>

                                <span
                                    class="w-2 h-2
                                           bg-[#700000]
                                           rounded-full
                                           animate-bounce"
                                    style="animation-delay: .3s">
                                </span>

                            </div>

                        </div>


                        <div
                            class="text-[10px] md:text-xs
                                   text-gray-500
                                   mt-1.5
                                   font-medium">

                            AI Thesis Assistant
                            is searching...

                        </div>

                    </div>
                `;


                chatMessages.appendChild(wrapper);

                scrollToBottom();

            }

            function removeLoadingMessage() {

                const loading =
                    document.getElementById(
                        'loadingMessage'
                    );


                if (loading) {
                    loading.remove();
                }

            }

            function addErrorMessage(message) {

                const wrapper =
                    document.createElement('div');


                wrapper.className =
                    'flex items-start gap-3';


                wrapper.innerHTML = `

                    <div
                        class="w-8 h-8 md:w-9 md:h-9
                               rounded-lg
                               bg-red-600
                               text-white
                               flex-shrink-0
                               flex items-center
                               justify-center
                               font-bold shadow-md">
                        ⚠️
                    </div>


                    <div
                        class="max-w-xl
                               md:max-w-3xl">

                        <div
                            class="bg-red-50
                                   border border-red-200
                                   rounded-2xl
                                   rounded-tl-md
                                   px-4 py-3
                                   shadow-sm">

                            <p
                                class="text-sm
                                       text-red-700
                                       whitespace-pre-wrap">
                            </p>

                        </div>


                        <div
                            class="text-[10px] md:text-xs
                                   text-red-600
                                   mt-1.5
                                   font-semibold">

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

            chatForm.addEventListener(
                'submit',
                async function(e) {

                    e.preventDefault();


                    const message =
                        messageInput.value.trim();


                    if (!message) {
                        return;
                    }


                    console.log(
                        'Sending message:',
                        message
                    );


                    /* User message */

                    addUserMessage(message);


                    /* Clear input */

                    messageInput.value = '';

                    messageInput.style.height =
                        'auto';


                    /* Disable send button */

                    sendBtn.disabled =
                        true;

                    sendBtnText.textContent =
                        'Thinking...';

                    sendBtnIcon.textContent =
                        '⏳';


                    /* Loading */

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
                                'Invalid JSON:',
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

                            Array.isArray(
                                data?.sources
                            ) ?
                            data.sources :
                            []
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

                        /* Re-enable */

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

            messageInput.addEventListener(
                'keydown',
                function(e) {

                    if (
                        e.key === 'Enter' &&
                        !e.shiftKey
                    ) {

                        e.preventDefault();


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

            const urlParams =
                new URLSearchParams(
                    window.location.search
                );


            const initialQuery =
                urlParams.get('q');


            console.log(
                'URL query:',
                initialQuery
            );

            if (
                initialQuery &&
                initialQuery.trim()
            ) {

                const question =
                    initialQuery.trim();


                console.log(
                    'Ask AI question detected:',
                    question
                );

                messageInput.value =
                    question;

                messageInput.style.height =
                    'auto';


                messageInput.style.height =
                    Math.min(
                        messageInput.scrollHeight,
                        150
                    ) + 'px';

                setTimeout(
                    function() {

                        console.log(
                            'Automatically submitting Ask AI question.'
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

            scrollToBottom();


            console.log(
                'SAC Thesis AI Chat initialized successfully.'
            );

        });
    </script>

</body>

</html>