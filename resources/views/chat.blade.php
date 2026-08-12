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
  <header class="border-b border-gray-800 bg-gray-950 px-6 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <div class="h-8 w-8 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-white">S</div>
      <h1 class="text-xl font-bold tracking-tight">SAC Thesis System</h1>
    </div>
    <nav class="flex gap-4 items-center">
      <a href="/documents" class="text-sm font-medium text-gray-400 hover:text-gray-200 transition">Documents & Search</a>
      <a href="/chat" class="text-sm font-medium text-indigo-400 border-b-2 border-indigo-500 pb-1">AI Assistant</a>
      <button type="submit" class="text-xs bg-gray-800 hover:bg-gray-700 border border-gray-700 px-3 py-1.5 rounded-lg ml-4 text-gray-300">
        Sign Out
      </button>
    </nav>
  </header>

  <!-- Chat Area -->
  <div
        id="chatMessages"
        class="flex-1 overflow-y-auto px-6 py-6 space-y-6"
    >

        <!-- Welcome Message -->

        <div class="flex items-start gap-3">

            <div class="w-9 h-9 rounded-lg bg-indigo-600 flex-shrink-0 flex items-center justify-center">
                🤖
            </div>

            <div class="max-w-3xl">

                <div class="bg-gray-800 border border-gray-700 rounded-2xl rounded-tl-md px-4 py-3">

                    <p class="text-sm text-gray-200">
                        👋 Hi! I'm your RAG Thesis AI Assistant.
                    </p>

                    <p class="text-sm text-gray-300 mt-2">
                        Ask me anything about the uploaded thesis papers.
                        I'll search the repository and use the relevant
                        documents to answer your question.
                    </p>

                </div>

                <div class="text-xs text-gray-500 mt-2">
                    AI Thesis Assistant
                </div>

            </div>

        </div>

    </div>


    <!-- ============================= -->
    <!-- MESSAGE INPUT -->
    <!-- ============================= -->

    <div class="border-t border-gray-800 bg-gray-900 px-6 py-4">

        <form
            id="chatForm"
            class="flex items-end gap-3"
        >

            <div class="flex-1 relative">

                <textarea
                    id="messageInput"
                    rows="1"
                    required
                    placeholder="Ask a question about the research papers..."
                    class="w-full resize-none bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 pr-12 text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                ></textarea>

            </div>


            <button
                type="submit"
                id="sendBtn"
                class="bg-indigo-600 hover:bg-indigo-500 disabled:bg-gray-700 disabled:text-gray-500 text-white font-medium px-6 py-3 rounded-xl text-sm transition flex items-center gap-2"
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


<!-- ============================= -->
<!-- CHAT JAVASCRIPT -->
<!-- ============================= -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');
    const sendBtnText = document.getElementById('sendBtnText');
    const sendBtnIcon = document.getElementById('sendBtnIcon');
    const chatMessages = document.getElementById('chatMessages');


    // ==========================================
    // AUTO SCROLL
    // ==========================================

    function scrollToBottom() {

        chatMessages.scrollTo({
            top: chatMessages.scrollHeight,
            behavior: 'smooth'
        });

    }


    // ==========================================
    // ADD USER MESSAGE
    // ==========================================

    function addUserMessage(message) {

        const messageWrapper = document.createElement('div');

        messageWrapper.className =
            'flex justify-end items-start gap-3';

        messageWrapper.innerHTML = `

            <div class="max-w-3xl">

                <div class="bg-indigo-600 rounded-2xl rounded-tr-md px-4 py-3">

                    <p class="text-sm text-white whitespace-pre-wrap"></p>

                </div>

                <div class="text-xs text-gray-500 mt-2 text-right">
                    You
                </div>

            </div>

        `;

        messageWrapper
            .querySelector('p')
            .textContent = message;

        chatMessages.appendChild(messageWrapper);

        scrollToBottom();

    }


    // ==========================================
    // ADD AI MESSAGE
    // ==========================================

    function addAIMessage(answer, sources = []) {

        const messageWrapper = document.createElement('div');

        messageWrapper.className =
            'flex items-start gap-3';

        messageWrapper.innerHTML = `

            <div class="w-9 h-9 rounded-lg bg-indigo-600 flex-shrink-0 flex items-center justify-center">
                🤖
            </div>

            <div class="max-w-3xl">

                <div class="bg-gray-800 border border-gray-700 rounded-2xl rounded-tl-md px-4 py-3">

                    <div class="text-sm text-gray-200 whitespace-pre-wrap ai-answer"></div>

                    ${
                        sources.length > 0
                        ?
                        `
                        <div class="mt-4 pt-3 border-t border-gray-700">

                            <p class="text-xs font-semibold text-gray-400 mb-2">
                                📚 Sources
                            </p>

                            <div class="space-y-2 source-list"></div>

                        </div>
                        `
                        :
                        ''
                    }

                </div>

                <div class="text-xs text-gray-500 mt-2">
                    AI Thesis Assistant
                </div>

            </div>

        `;


        // Safely insert AI answer
        messageWrapper
            .querySelector('.ai-answer')
            .textContent = answer;


        // Add sources
        if (sources.length > 0) {

            const sourceList =
                messageWrapper.querySelector('.source-list');


            sources.forEach((source, index) => {

                const sourceElement =
                    document.createElement('div');

                sourceElement.className =
                    'text-xs text-gray-400 bg-gray-900 rounded-lg px-3 py-2';


                const similarity =
                    source.similarity
                    ? Math.round(source.similarity * 100)
                    : null;


                sourceElement.innerHTML = `

                    <div class="flex items-center justify-between gap-3">

                        <span>
                            Source ${index + 1}
                        </span>

                        ${
                            similarity !== null
                            ?
                            `<span class="text-indigo-400">
                                ${similarity}% similarity
                            </span>`
                            :
                            ''
                        }

                    </div>

                `;

                sourceList.appendChild(sourceElement);

            });

        }


        chatMessages.appendChild(messageWrapper);

        scrollToBottom();

    }


    // ==========================================
    // ADD LOADING MESSAGE
    // ==========================================

    function addLoadingMessage() {

        const loadingWrapper =
            document.createElement('div');

        loadingWrapper.id =
            'loadingMessage';

        loadingWrapper.className =
            'flex items-start gap-3';


        loadingWrapper.innerHTML = `

            <div class="w-9 h-9 rounded-lg bg-indigo-600 flex-shrink-0 flex items-center justify-center">
                🤖
            </div>

            <div>

                <div class="bg-gray-800 border border-gray-700 rounded-2xl rounded-tl-md px-4 py-3">

                    <div class="flex items-center gap-1">

                        <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></span>

                        <span
                            class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
                            style="animation-delay: 0.15s"
                        ></span>

                        <span
                            class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
                            style="animation-delay: 0.3s"
                        ></span>

                    </div>

                </div>

                <div class="text-xs text-gray-500 mt-2">
                    AI Thesis Assistant is searching the thesis repository...
                </div>

            </div>

        `;


        chatMessages.appendChild(loadingWrapper);

        scrollToBottom();

    }


    // ==========================================
    // REMOVE LOADING MESSAGE
    // ==========================================

    function removeLoadingMessage() {

        const loadingMessage =
            document.getElementById('loadingMessage');

        if (loadingMessage) {

            loadingMessage.remove();

        }

    }


    // ==========================================
    // ADD ERROR MESSAGE
    // ==========================================

    function addErrorMessage(message) {

        const errorWrapper =
            document.createElement('div');

        errorWrapper.className =
            'flex items-start gap-3';


        errorWrapper.innerHTML = `

            <div class="w-9 h-9 rounded-lg bg-red-600 flex-shrink-0 flex items-center justify-center">
                ⚠️
            </div>

            <div class="max-w-3xl">

                <div class="bg-red-950/40 border border-red-800 rounded-2xl rounded-tl-md px-4 py-3">

                    <p class="text-sm text-red-300 whitespace-pre-wrap"></p>

                </div>

                <div class="text-xs text-gray-500 mt-2">
                    System Error
                </div>

            </div>

        `;


        errorWrapper
            .querySelector('p')
            .textContent = message;


        chatMessages.appendChild(errorWrapper);

        scrollToBottom();

    }


    // ==========================================
    // SEND MESSAGE
    // ==========================================

    chatForm.addEventListener('submit', async function (e) {

        e.preventDefault();


        const message =
            messageInput.value.trim();


        if (!message) {
            return;
        }


        // Show user's message
        addUserMessage(message);


        // Clear input
        messageInput.value = '';

        messageInput.style.height = 'auto';


        // Disable button
        sendBtn.disabled = true;

        sendBtnText.textContent =
            'Thinking...';

        sendBtnIcon.textContent =
            '⏳';


        // Show loading animation
        addLoadingMessage();


        try {

            const response = await fetch(
                '/backend/chat',
                {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },

                    body: JSON.stringify({
                        message: message
                    })
                }
            );


            const data =
                await response.json();


            console.log(
                'RAG chatbot response:',
                data
            );


            removeLoadingMessage();


            // Backend error
            if (!response.ok || data.error) {

                addErrorMessage(
                    data.message ||
                    'Sorry, something went wrong while processing your question.'
                );

                return;
            }


            // Add AI answer
            addAIMessage(
                data.answer ||
                'No answer was generated.',
                data.sources || []
            );


        } catch (error) {

            console.error(
                'RAG chatbot error:',
                error
            );


            removeLoadingMessage();


            addErrorMessage(
                'Unable to connect to the chatbot server. Please make sure the Laravel backend is running.'
            );

        } finally {

            sendBtn.disabled = false;

            sendBtnText.textContent =
                'Send';

            sendBtnIcon.textContent =
                '➤';

            messageInput.focus();

        }

    });


    // ==========================================
    // ENTER TO SEND
    // SHIFT + ENTER = NEW LINE
    // ==========================================

    messageInput.addEventListener(
        'keydown',
        function (e) {

            if (
                e.key === 'Enter' &&
                !e.shiftKey
            ) {

                e.preventDefault();

                chatForm.requestSubmit();

            }

        }
    );


    // ==========================================
    // AUTO RESIZE TEXTAREA
    // ==========================================

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


});

</script>
</body>
</html>