<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAC Thesis Repository - Documents</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        /* Antigravity-Style Right AI Sidebar Squeeze Layout */
        @media (min-width: 1024px) {
            html.ai-drawer-open main,
            html.ai-drawer-open #mainContent {
                margin-right: 440px !important;
            }
            html.ai-drawer-open #aiDrawerBackdrop {
                display: none !important;
                pointer-events: none !important;
            }
            #aiDrawer {
                width: 440px !important;
            }
        }
        @media (min-width: 1440px) {
            html.ai-drawer-open main,
            html.ai-drawer-open #mainContent {
                margin-right: 480px !important;
            }
            #aiDrawer {
                width: 480px !important;
            }
        }
    </style>
</head>

<body class="min-h-screen bg-slate-50 text-slate-800 font-sans">

    {{-- SIDEBAR NAVIGATION --}}
    @include('partials.sidebar')

    {{-- MAIN PAGE CONTENT --}}
    <main id="mainContent" class="md:ml-64 min-h-screen p-4 sm:p-6 md:p-10 transition-all duration-300 ease-in-out pt-16 md:pt-10">
        <div class="mx-auto max-w-5xl">

            {{-- 1. PAGE HEADER --}}
            <section class="mb-6 md:mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-[#700000]">
                            Thesis Repository
                        </h1>
                        <p class="mt-1 text-xs md:text-sm text-gray-500">
                            Search, cite, and view published St. Anthony's College thesis documents.
                        </p>
                    </div>

                    {{-- Student Notifications Bell & Dropdown --}}
                    <div class="relative shrink-0">
                        <button
                            type="button"
                            id="notifBellBtn"
                            onclick="toggleNotificationDropdown()"
                            class="px-3.5 py-2 rounded-2xl border border-gray-200 bg-white hover:bg-slate-50 text-gray-700 text-xs font-bold transition flex items-center gap-2 shadow-2xs relative cursor-pointer">
                            <svg class="w-4 h-4 text-[#700000]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                            <span class="hidden sm:inline">Notifications</span>
                            <span id="notifBadge" class="hidden w-2 h-2 rounded-full bg-rose-600 shrink-0"></span>
                        </button>

                        <div
                            id="notifDropdown"
                            class="hidden absolute right-0 mt-2 w-80 sm:w-96 rounded-3xl bg-white p-4 shadow-2xl border border-gray-200 z-50 transition-all">
                            <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-2">
                                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Submission Updates</h3>
                                <button onclick="markAllNotificationsAsRead()" class="text-[10px] font-semibold text-[#700000] hover:underline cursor-pointer">Mark all as read</button>
                            </div>
                            <div id="notifList" class="max-h-72 overflow-y-auto divide-y divide-gray-100 text-xs">
                                <p class="py-4 text-center text-gray-400">Loading notifications...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- 2. SEARCH & FILTER SECTION --}}
            <section class="mb-8 space-y-4">
                <form id="searchForm" class="space-y-3">
                    {{-- Search Input Bar (Google Scholar Style Hybrid Search) --}}
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input
                                id="searchInput"
                                type="search"
                                placeholder="Search by thesis title, author, keywords, topics, or concepts..."
                                class="w-full rounded-2xl border border-gray-300 bg-white pl-10 pr-4 py-3 text-xs md:text-sm text-gray-800 placeholder-gray-400 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000] shadow-sm transition">
                        </div>
                        <button
                            type="submit"
                            class="rounded-2xl bg-[#700000] px-7 py-3 text-xs md:text-sm font-bold text-[#FFD700] hover:bg-[#800000] transition shadow-md shrink-0 flex items-center justify-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <span>Search</span>
                        </button>
                    </div>
        </div>

        {{-- 3. QUICK FILTER & SORT TOOLBAR --}}
        <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
            <div class="flex items-center gap-3 flex-wrap">
                {{-- Department Filter Dropdown --}}
                <div class="flex items-center gap-1.5">
                    <label for="deptFilter" class="text-xs font-bold text-gray-500">Department:</label>
                    <select
                        id="deptFilter"
                        onchange="onFilterChange()"
                        class="rounded-xl border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000] shadow-2xs">
                        <option value="all">All Departments</option>
                        <option value="it">Information Technology (BSIT)</option>
                        <option value="marine">Marine Engineering (BSMarE)</option>
                        <option value="nursing">Nursing & Healthcare (BSN)</option>
                        <option value="hospitality">Hospitality Management (BSHM)</option>
                        <option value="business">Business & Accountancy (CBA)</option>
                        <option value="education">Teacher Education (CTE)</option>
                        <option value="criminology">Criminology / Arts & Sciences</option>
                    </select>
                </div>

                {{-- Sort By Dropdown --}}
                <div class="flex items-center gap-1.5">
                    <label for="sortFilter" class="text-xs font-bold text-gray-500">Sort By:</label>
                    <select
                        id="sortFilter"
                        onchange="onFilterChange()"
                        class="rounded-xl border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000] shadow-2xs">
                        <option value="relevance" id="sortOptionRelevance" class="hidden">Best Match (Relevance)</option>
                        <option value="latest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="title_asc">Title (A – Z)</option>
                        <option value="title_desc">Title (Z – A)</option>
                    </select>
                </div>
            </div>
        </div>
        </form>
        </section>

        {{-- 4. THESIS CARDS LIST CONTAINER --}}
        <section id="documentsList" class="space-y-4">
            <p class="text-center text-sm text-gray-500 py-10">
                Loading thesis repository...
            </p>
        </section>

        </div>
    </main>

    {{-- FLOATING TOAST NOTIFICATION (WHITE POPUP) --}}
    <div id="toastNotification" class="fixed bottom-6 right-6 z-50 transform transition-all duration-300 translate-y-20 opacity-0 pointer-events-none">
        <div class="flex items-center gap-3 rounded-2xl bg-white text-gray-900 px-5 py-3.5 shadow-xl border border-gray-200">
            <span id="toastIconContainer" class="shrink-0">
                <svg id="toastBookmarkIcon" class="w-5 h-5 text-amber-500 fill-current" viewBox="0 0 24 24">
                    <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
            </span>
            <p id="toastMessage" class="text-xs md:text-sm font-semibold text-gray-800 tracking-wide">Added to bookmark</p>
        </div>
    </div>

    {{-- ACADEMIC CITATION MODAL (IEEE, APA 7th, MLA 9th) --}}
    <div id="citationModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl transition-all">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-[#700000] text-[#FFD700] flex items-center justify-center shrink-0 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Academic Citation</h3>
                        <p class="text-[11px] text-gray-500">Official reference format for research papers</p>
                    </div>
                </div>
                <button onclick="closeCitationModal()" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mt-4 space-y-3">
                <p id="modalDocTitle" class="text-xs font-bold text-[#700000] truncate"></p>
                <!-- Style Tabs -->
                <div class="flex rounded-2xl bg-slate-100 p-1 border border-gray-200 gap-1">
                    <button
                        id="citeTabIeee"
                        type="button"
                        onclick="switchCitationStyle('ieee')"
                        class="flex-1 py-2 text-xs font-bold rounded-xl transition shadow-xs bg-white text-[#700000]">
                        IEEE
                    </button>
                    <button
                        id="citeTabApa"
                        type="button"
                        onclick="switchCitationStyle('apa')"
                        class="flex-1 py-2 text-xs font-bold rounded-xl transition text-gray-600 hover:text-gray-900">
                        APA 7th
                    </button>
                    <button
                        id="citeTabMla"
                        type="button"
                        onclick="switchCitationStyle('mla')"
                        class="flex-1 py-2 text-xs font-bold rounded-xl transition text-gray-600 hover:text-gray-900">
                        MLA 9th
                    </button>
                </div>

                <!-- Formatted Citation Output Box -->
                <div class="rounded-2xl border border-gray-200 bg-slate-50/80 p-4 relative">
                    <p id="citationText" class="text-xs md:text-sm text-gray-800 leading-relaxed font-mono select-all break-words" title="Click to select all"></p>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <button onclick="closeCitationModal()" class="rounded-xl px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-100 transition cursor-pointer">
                    Close
                </button>
                <button id="copyCitationBtn" onclick="copyCitationToClipboard()" class="rounded-xl bg-[#700000] px-5 py-2.5 text-xs font-bold text-[#FFD700] hover:bg-[#800000] transition shadow-md flex items-center gap-1.5 cursor-pointer">
                    <svg id="copyBtnIcon" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span id="copyBtnText">Copy Citation</span>
                </button>
            </div>
        </div>
    </div>

    {{-- =========================================================
         RIGHT-SIDE AI RESEARCH ASSISTANT DRAWER (YOUTUBE / GEMINI STYLE)
    ========================================================== --}}
    <!-- Mobile Backdrop for AI Drawer (Only on mobile where screen cannot squeeze) -->
    <div
        id="aiDrawerBackdrop"
        onclick="closeDocAiDrawer()"
        class="fixed inset-0 z-40 bg-black/40 opacity-0 pointer-events-none transition-opacity duration-300 lg:hidden">
    </div>

    <!-- Right-Side AI Drawer (Antigravity-Style Squeezable Side Panel) -->
    <aside
        id="aiDrawer"
        class="fixed inset-y-0 right-0 z-40 w-full sm:w-[420px] lg:w-[440px] xl:w-[480px] bg-white border-l border-gray-200 shadow-xl flex flex-col transition-transform duration-300 ease-in-out translate-x-full">

        <!-- Drawer Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-white">
            <div class="flex items-center gap-2.5 min-w-0 pr-2">
                <div class="w-8 h-8 rounded-xl bg-[#700000] text-[#FFD700] flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h2 class="text-sm font-bold text-gray-900 truncate">Ask about this thesis</h2>
                    <p id="aiDrawerDocTitle" class="text-[10px] text-gray-500 truncate">Select a thesis...</p>
                </div>
            </div>
            <button
                type="button"
                onclick="closeDocAiDrawer()"
                aria-label="Close AI Drawer"
                class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Chat Conversation Feed -->
        <div id="aiDrawerMessages" class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4 bg-slate-50/60">

            <!-- Initial Greeting & Quick Question Chips (YouTube Style) -->
            <div id="aiInitialCard" class="space-y-3.5">
                <div class="flex items-start gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-[#700000] text-[#FFD700] flex items-center justify-center shrink-0 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs sm:text-sm text-gray-800 leading-relaxed font-medium">
                            Hello! Curious about what you're reading? I'm here to help analyze this thesis.
                        </p>
                        <p class="text-[11px] text-gray-500 mt-2 font-medium">
                            Not sure what to ask? Choose something:
                        </p>
                    </div>
                </div>

                <!-- Quick Prompt Chips -->
                <div class="flex flex-col gap-2 pl-8">
                    <button
                        type="button"
                        onclick="sendDocQuickQuestion('Summarize this thesis in 3 concise bullet points.')"
                        class="text-left px-3.5 py-2.5 rounded-xl bg-white border border-gray-200 text-xs font-semibold text-gray-700 hover:border-[#700000] hover:text-[#700000] hover:shadow-xs transition cursor-pointer">
                        📑 Summarize this thesis
                    </button>

                    <button
                        type="button"
                        onclick="sendDocQuickQuestion('What is the main problem and objective of this research?')"
                        class="text-left px-3.5 py-2.5 rounded-xl bg-white border border-gray-200 text-xs font-semibold text-gray-700 hover:border-[#700000] hover:text-[#700000] hover:shadow-xs transition cursor-pointer">
                        🎯 What problem does this study solve?
                    </button>

                    <button
                        type="button"
                        onclick="sendDocQuickQuestion('What methodology, tools, and technologies were used in this system?')"
                        class="text-left px-3.5 py-2.5 rounded-xl bg-white border border-gray-200 text-xs font-semibold text-gray-700 hover:border-[#700000] hover:text-[#700000] hover:shadow-xs transition cursor-pointer">
                        💻 What methodology and tech stack was used?
                    </button>

                    <button
                        type="button"
                        onclick="sendDocQuickQuestion('What are the key conclusions, findings, and recommendations of this study?')"
                        class="text-left px-3.5 py-2.5 rounded-xl bg-white border border-gray-200 text-xs font-semibold text-gray-700 hover:border-[#700000] hover:text-[#700000] hover:shadow-xs transition cursor-pointer">
                        📊 What are the conclusions & findings?
                    </button>
                </div>
            </div>

            <!-- Dynamic Messages Thread -->
            <div id="aiDrawerChatThread" class="space-y-4"></div>

            <!-- Typing Indicator -->
            <div id="aiDrawerTyping" class="hidden items-center gap-2 text-xs text-gray-400 pl-2">
                <span class="w-2 h-2 rounded-full bg-[#700000] animate-pulse"></span>
                <span class="w-2 h-2 rounded-full bg-[#700000] animate-pulse delay-150"></span>
                <span class="w-2 h-2 rounded-full bg-[#700000] animate-pulse delay-300"></span>
                <span class="text-[11px] text-gray-500 font-medium ml-1">Gemini is analyzing thesis...</span>
            </div>
        </div>

        <!-- Drawer Input Footer -->
        <div class="p-3 border-t border-gray-200 bg-white">
            <form id="aiDrawerForm" onsubmit="handleDocAiSubmit(event)" class="relative flex items-center">
                <input
                    id="aiDrawerInput"
                    type="text"
                    placeholder="Ask a question..."
                    autocomplete="off"
                    class="w-full rounded-2xl border border-gray-300 bg-slate-50 pl-4 pr-12 py-3 text-xs sm:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000] shadow-2xs">

                <button
                    id="aiDrawerSendBtn"
                    type="submit"
                    title="Send question"
                    class="absolute right-2 p-2 rounded-xl bg-[#700000] text-[#FFD700] hover:bg-[#850000] transition disabled:opacity-50 cursor-pointer shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                </button>
            </form>
            <p class="text-[9px] text-gray-400 text-center mt-1.5">
                St. Anthony's College research • Powered by Gemini
            </p>
        </div>

    </aside>

    {{-- JAVASCRIPT LOGIC --}}
    <script>
        // Global variables and elements
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const COVERS_BASE_URL = "{{ asset('images/covers') }}";

        let allDocuments = [];
        let currentSearchQuery = '';
        let currentCitationDoc = null;
        let savedBookmarkIds = new Set();
        let toastTimeout = null;

        const documentsList = document.getElementById('documentsList');
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');
        const deptFilter = document.getElementById('deptFilter');
        const sortFilter = document.getElementById('sortFilter');
        const docCountBadge = document.getElementById('docCountBadge');

        // Trigger search when Department or Sort dropdown changes
        function onFilterChange() {
            fetchDocuments(searchInput.value);
        }

        // Display floating toast message when bookmarking/unbookmarking
        function showToast(message, isSaved = true) {
            const toast = document.getElementById('toastNotification');
            const toastMsg = document.getElementById('toastMessage');
            const toastIconContainer = document.getElementById('toastIconContainer');

            if (!toast || !toastMsg) return;

            toastMsg.textContent = message;
            toastMsg.className = "text-xs md:text-sm font-semibold text-gray-800 tracking-wide";

            if (isSaved) {
                toastIconContainer.innerHTML = `
                    <svg class="w-5 h-5 text-amber-500 fill-current" viewBox="0 0 24 24">
                        <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                    </svg>
                `;
            } else {
                toastIconContainer.innerHTML = `
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                `;
            }

            toast.classList.remove('translate-y-20', 'opacity-0', 'pointer-events-none');
            toast.classList.add('translate-y-0', 'opacity-100');

            if (toastTimeout) clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('translate-y-20', 'opacity-0', 'pointer-events-none');
            }, 2500);
        }

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        }

        // Highlight matching search keywords in title and abstract
        function highlightKeywords(text, query) {
            if (!text) return '';
            if (!query || !query.trim()) return escapeHtml(text);

            const stopWords = new Set([
                'a', 'an', 'the', 'in', 'on', 'at', 'to', 'for', 'of', 'and', 'or', 
                'is', 'are', 'was', 'were', 'by', 'with', 'from', 'as', 'it', 'its', 
                'be', 'this', 'that', 'into', 'about', 'than', 'then', 'so', 'such'
            ]);

            const rawTerms = query
                .toLowerCase()
                .replace(/[^\w\s-]/g, ' ')
                .trim()
                .split(/\s+/)
                .filter(t => t.length >= 2 && !stopWords.has(t));

            const terms = rawTerms.length > 0 
                ? rawTerms 
                : query.toLowerCase().replace(/[^\w\s-]/g, ' ').trim().split(/\s+/).filter(t => t.length >= 2);

            if (terms.length === 0) return escapeHtml(text);

            const termSet = new Set();
            terms.forEach(t => {
                termSet.add(t);
                if (t.endsWith('s') && t.length > 3) {
                    termSet.add(t.slice(0, -1));
                }
            });

            const sortedTerms = [...termSet].sort((a, b) => b.length - a.length);
            const pattern = sortedTerms.map(t => t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|');
            const regex = new RegExp(`(\\b(?:${pattern})\\w*)`, 'gi');

            const parts = text.split(regex);
            return parts.map(part => {
                if (!part) return '';
                const isMatch = sortedTerms.some(t => new RegExp(`^${t}`, 'i').test(part));
                if (isMatch) {
                    return `<mark class="bg-amber-100 text-gray-900 font-semibold px-0.5 rounded">${escapeHtml(part)}</mark>`;
                }
                return escapeHtml(part);
            }).join('');
        }

        function handleImageError(imageElement) {
            imageElement.onerror = null;
            imageElement.src = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='100' height='140' viewBox='0 0 100 140'><rect width='100%' height='100%' fill='%23700000'/><text x='50%' y='50%' font-size='12' font-weight='bold' fill='%23FFD700' text-anchor='middle' dominant-baseline='middle'>SAC THESIS</text></svg>";
        }

        // Return cover art and styling based on department
        function getDepartmentDetails(deptVal, courseVal, titleVal) {
            const dept = (deptVal || '').toLowerCase().trim();
            const course = (courseVal || '').toLowerCase().trim();
            const title = (titleVal || '').toLowerCase().trim();

            // 1. Direct database department check (Highest Priority)
            if (dept === 'it' || course === 'bsit') {
                return {
                    cover: 'IT.webp',
                    name: 'Information Technology Department',
                    badgeBg: 'bg-blue-50 text-blue-700 border-blue-200'
                };
            } else if (dept === 'marine' || course === 'bsmare') {
                return {
                    cover: 'MARINE.webp',
                    name: 'Marine Engineering Department',
                    badgeBg: 'bg-sky-50 text-sky-700 border-sky-200'
                };
            } else if (dept === 'nursing' || course === 'bsn') {
                return {
                    cover: 'NURSING.webp',
                    name: 'Nursing Department',
                    badgeBg: 'bg-emerald-50 text-emerald-700 border-emerald-200'
                };
            } else if (dept === 'hospitality' || course === 'bshm') {
                return {
                    cover: 'HM.webp',
                    name: 'Hospitality Management',
                    badgeBg: 'bg-amber-50 text-amber-800 border-amber-200'
                };
            } else if (dept === 'education' || course === 'bsed') {
                return {
                    cover: 'EDUC.webp',
                    name: 'Education Department',
                    badgeBg: 'bg-purple-50 text-purple-700 border-purple-200'
                };
            } else if (dept === 'criminology' || course === 'bsc') {
                return {
                    cover: 'CRIM.webp',
                    name: 'Criminology Department',
                    badgeBg: 'bg-red-50 text-red-700 border-red-200'
                };
            }

            // 2. Keyword heuristic fallback if department is unspecified
            if (title.includes('patient') || title.includes('nursing')) {
                return {
                    cover: 'NURSING.webp',
                    name: 'Nursing Department',
                    badgeBg: 'bg-emerald-50 text-emerald-700 border-emerald-200'
                };
            }
            if (title.includes('marine') || title.includes('vessel')) {
                return {
                    cover: 'MARINE.webp',
                    name: 'Marine Engineering Department',
                    badgeBg: 'bg-sky-50 text-sky-700 border-sky-200'
                };
            }
            if (title.includes('system') || title.includes('app') || title.includes('web') || title.includes('software')) {
                return {
                    cover: 'IT.webp',
                    name: 'Information Technology Department',
                    badgeBg: 'bg-blue-50 text-blue-700 border-blue-200'
                };
            }

            return {
                cover: 'IT.webp',
                name: 'Academic Research',
                badgeBg: 'bg-[#700000]/10 text-[#700000] border-[#700000]/20'
            };
        }

        // Render thesis cards to the page
        function renderDocuments(documents) {
            if (!Array.isArray(documents) || documents.length === 0) {
                documentsList.innerHTML = `
                    <div class="rounded-3xl border border-dashed border-gray-300 bg-white p-12 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-50 border border-gray-200 text-gray-400 mb-3">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 18m-2-5a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-gray-800">No theses found</h3>
                        <p class="mt-1 text-xs text-gray-500">Try adjusting your search terms or department filters.</p>
                    </div>
                `;
            }

            documentsList.innerHTML = documents.map((doc, idx) => {
                const details = getDepartmentDetails(doc.department, doc.course_code, doc.title);
                const isLongAbstract = (doc.abstract || '').length > 200;
                const truncatedAbstract = isLongAbstract ? doc.abstract.substring(0, 200) + '...' : doc.abstract;
                const isSaved = savedBookmarkIds.has(doc.id);
                const rawDate = doc.publication_date || doc.created_at;
                const pubDateStr = rawDate ? new Date(rawDate).toLocaleDateString('en-US', { month: 'short', year: 'numeric' }) : '';

                return `
                    <article class="relative flex flex-col md:flex-row gap-5 rounded-3xl border border-gray-200 bg-white p-5 md:p-6 shadow-sm hover:shadow-md hover:border-[#700000]/30 transition">
                        
                        {{-- Top-Right Bookmark Button --}}
                        <button
                            type="button"
                            onclick="toggleBookmark(${doc.id}, this)"
                            title="${isSaved ? 'Remove from bookmark' : 'Add to bookmark'}"
                            class="absolute top-4 right-4 md:top-6 md:right-6 p-2 rounded-xl border transition ${isSaved ? 'bg-amber-50 border-amber-300 text-amber-500 shadow-sm' : 'bg-white border-gray-200 text-gray-400 hover:text-[#700000] hover:border-gray-300 hover:bg-slate-50'}"
                        >
                            <svg class="w-5 h-5 ${isSaved ? 'fill-current' : 'fill-none'}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                            </svg>
                        </button>

                        {{-- Thesis Book Cover --}}
                        <div class="w-full md:w-28 h-36 md:h-36 rounded-2xl border border-gray-200 bg-slate-100 overflow-hidden shadow-sm shrink-0">
                            <img
                                src="${COVERS_BASE_URL}/${details.cover}"
                                alt="${escapeHtml(doc.title)} Cover"
                                class="w-full h-full object-cover"
                                onerror="handleImageError(this)">
                        </div>

                        <div class="flex-1 min-w-0 pr-8">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-500 font-semibold">
                                ${doc.similarity_score ? `<span class="font-bold text-[#700000]">${doc.similarity_score}% Similarity</span><span class="text-gray-300">•</span>` : ''}
                                <span class="font-bold text-[#700000]">St. Anthony's College</span>
                                <span class="text-gray-300">•</span>
                                <span class="text-gray-700">${escapeHtml(details.name)}</span>
                                ${pubDateStr ? `<span class="text-gray-300">•</span><span class="text-gray-500 font-medium">${pubDateStr}</span>` : ''}
                            </div>

                            <h3 class="mt-2.5 text-base md:text-lg font-bold text-gray-900 transition">
                                <a href="/documents/${doc.id}" class="hover:text-[#700000] hover:underline cursor-pointer">
                                    ${highlightKeywords(doc.title, currentSearchQuery)}
                                </a>
                            </h3>

                            <p class="mt-1 text-xs md:text-sm font-semibold text-[#700000]">
                                by ${escapeHtml(doc.author || 'Unknown Author')}
                            </p>

                            <div class="mt-3 text-xs md:text-sm text-gray-600 leading-relaxed">
                                <span id="abstract-short-${doc.id}">${highlightKeywords(truncatedAbstract, currentSearchQuery)}</span>
                                ${isLongAbstract ? `
                                    <span id="abstract-full-${doc.id}" class="hidden">${highlightKeywords(doc.abstract, currentSearchQuery)}</span>
                                    <button type="button" onclick="toggleAbstract(${doc.id})" id="abstract-btn-${doc.id}" class="ml-1 text-xs font-bold text-[#700000] hover:underline">
                                        Read More
                                    </button>
                                ` : ''}
                            </div>

                            {{-- Bottom Action Buttons (Cite IEEE & Ask AI) --}}
                            <div class="mt-5 pt-4 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        onclick="openCitationModal(${idx})"
                                        class="rounded-xl border border-gray-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-gray-700 hover:bg-[#700000] hover:text-[#FFD700] hover:border-[#700000] transition flex items-center gap-1.5"
                                    >
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                                        </svg>
                                        <span>Citation</span>
                                    </button>

                                    <button
                                        type="button"
                                        onclick="openDocAiDrawer(${idx})"
                                        class="rounded-xl border border-gray-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-gray-700 hover:bg-[#700000] hover:text-[#FFD700] hover:border-[#700000] transition flex items-center gap-1.5 cursor-pointer"
                                    >
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                                        </svg>
                                        <span>Ask AI</span>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </article>
                `;
            }).join('');
        }

        // Toggle Expand/Collapse Abstract
        function toggleAbstract(id) {
            const shortSpan = document.getElementById(`abstract-short-${id}`);
            const fullSpan = document.getElementById(`abstract-full-${id}`);
            const btn = document.getElementById(`abstract-btn-${id}`);

            if (fullSpan.classList.contains('hidden')) {
                shortSpan.classList.add('hidden');
                fullSpan.classList.remove('hidden');
                btn.textContent = 'Show Less';
            } else {
                shortSpan.classList.remove('hidden');
                fullSpan.classList.add('hidden');
                btn.textContent = 'Read More';
            }
        }

        // Fetch user's bookmarked thesis IDs from database
        async function fetchBookmarkIds() {
            try {
                const res = await fetch('/backend/bookmarks/ids');
                if (res.ok) {
                    const ids = await res.json();
                    savedBookmarkIds = new Set(ids);
                }
            } catch (e) {}
        }

        // Toggle bookmark status for a thesis
        async function toggleBookmark(docId, btnElement) {
            try {
                const res = await fetch('/backend/bookmarks/toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        document_id: docId
                    })
                });
                const data = await res.json();
                if (data.bookmarked) {
                    savedBookmarkIds.add(docId);
                    btnElement.title = 'Remove from bookmark';
                    btnElement.className = 'absolute top-4 right-4 md:top-6 md:right-6 p-2 rounded-xl border bg-amber-50 border-amber-300 text-amber-500 shadow-sm transition';
                    btnElement.innerHTML = `
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                    `;
                    showToast('Added to bookmark', true);
                } else {
                    savedBookmarkIds.delete(docId);
                    btnElement.title = 'Add to bookmark';
                    btnElement.className = 'absolute top-4 right-4 md:top-6 md:right-6 p-2 rounded-xl border bg-white border-gray-200 text-gray-400 hover:text-[#700000] hover:border-gray-300 hover:bg-slate-50 transition';
                    btnElement.innerHTML = `
                        <svg class="w-5 h-5 fill-none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                    `;
                    showToast('Removed from bookmark', false);
                }
            } catch (err) {
                console.error(err);
            }
        }

        // Fetch theses from backend with Search, Department filter, and Sort order
        async function fetchDocuments(search = '') {
            currentSearchQuery = (search || '').trim();
            documentsList.innerHTML = `<p class="text-center text-sm text-gray-500 py-10">Searching documents...</p>`;
            try {
                const url = new URL('/backend/documents', window.location.origin);

                // Add search query if provided
                if (search && search.trim()) {
                    url.searchParams.set('search', search.trim());
                }

                // Add department filter
                const selectedDept = deptFilter ? deptFilter.value : 'all';
                if (selectedDept && selectedDept !== 'all') {
                    url.searchParams.set('department', selectedDept);
                }

                // Add sort order
                const selectedSort = sortFilter ? sortFilter.value : 'latest';
                if (selectedSort) {
                    url.searchParams.set('sort', selectedSort);
                }

                const res = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (res.status === 401) {
                    window.location.href = '/login';
                    return;
                }

                if (!res.ok) {
                    throw new Error('HTTP ' + res.status);
                }

                allDocuments = await res.json();
                renderDocuments(allDocuments);
            } catch (err) {
                console.error('fetchDocuments error:', err);
                documentsList.innerHTML = `
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-6 text-center text-sm text-red-700">
                        Could not load documents from server.
                    </div>
                `;
            }
        }

        function updateSortOptionsForSearch(hasSearch) {
            const relOption = document.getElementById('sortOptionRelevance');
            if (!relOption || !sortFilter) return;

            if (hasSearch) {
                relOption.classList.remove('hidden');
                sortFilter.value = 'relevance';
            } else {
                relOption.classList.add('hidden');
                if (sortFilter.value === 'relevance') {
                    sortFilter.value = 'latest';
                }
            }
        }

        // Search Form Submit Listener
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const query = searchInput.value.trim();
            updateSortOptionsForSearch(Boolean(query));
            fetchDocuments(query);
        });

        // Auto reset sort when search input is cleared
        searchInput.addEventListener('input', () => {
            if (!searchInput.value.trim()) {
                currentSearchQuery = '';
                updateSortOptionsForSearch(false);
                fetchDocuments('');
            }
        });

        const deptNamesMap = {
            'it': 'Information Technology',
            'computer': 'Computer Studies',
            'marine': 'Marine Engineering',
            'nursing': 'Nursing',
            'hospitality': 'Hospitality Management',
            'education': 'Teacher Education',
            'criminology': 'Criminal Justice Education',
            'business': 'Business Administration',
            'arts': 'Arts and Sciences'
        };

        function getFullDeptName(deptCode, courseCode, titleText) {
            const cleanDept = (deptCode || '').toLowerCase().trim();
            const cleanCourse = (courseCode || '').toLowerCase().trim();
            const cleanTitle = (titleText || '').toLowerCase().trim();

            if (cleanDept === 'it' || cleanCourse === 'bsit' || cleanTitle.includes('system') || cleanTitle.includes('app') || cleanTitle.includes('web') || cleanTitle.includes('software')) {
                return 'Information Technology';
            }
            if (cleanDept === 'marine' || cleanCourse === 'bsmare' || cleanTitle.includes('marine') || cleanTitle.includes('vessel')) {
                return 'Marine Engineering';
            }
            if (cleanDept === 'nursing' || cleanCourse === 'bsn' || cleanTitle.includes('patient') || cleanTitle.includes('nursing')) {
                return 'Nursing';
            }
            if (cleanDept === 'hospitality' || cleanCourse === 'bshm') {
                return 'Hospitality Management';
            }
            if (cleanDept === 'education' || cleanCourse === 'bsed') {
                return 'Teacher Education';
            }
            if (cleanDept === 'criminology' || cleanCourse === 'bsc') {
                return 'Criminal Justice Education';
            }
            for (const [k, v] of Object.entries(deptNamesMap)) {
                if (cleanDept.includes(k)) return v;
            }
            if (deptCode && deptCode.trim()) {
                return deptCode.charAt(0).toUpperCase() + deptCode.slice(1);
            }
            return 'Information Technology';
        }

        function formatIeeeAuthors(authorStr) {
            if (!authorStr || !authorStr.trim()) return 'Anonymous';
            let rawList = [];
            if (authorStr.includes(';') || authorStr.toLowerCase().includes(' and ') || authorStr.includes('&')) {
                rawList = authorStr.split(/;| and | & /i).map(s => s.trim()).filter(Boolean);
            } else {
                const parts = authorStr.split(',').map(s => s.trim()).filter(Boolean);
                if (parts.length === 2 && !parts[0].includes(' ') && !parts[1].includes(' ')) {
                    rawList = [authorStr];
                } else {
                    rawList = parts;
                }
            }

            const compoundPrefixes = ['dela', 'delos', 'de', 'del', 'san', 'santa', 'von', 'van', 'al', 'da'];

            const formatted = rawList.map(name => {
                let trimmed = name.trim();
                if (!trimmed) return '';
                let first = '', last = '';
                if (trimmed.includes(',')) {
                    const split = trimmed.split(',').map(s => s.trim());
                    last = split[0];
                    first = split.slice(1).join(' ');
                } else {
                    const tokens = trimmed.split(/\s+/);
                    if (tokens.length === 1) return tokens[0];
                    if (tokens.length >= 3 && compoundPrefixes.includes(tokens[tokens.length - 2].toLowerCase())) {
                        last = tokens.slice(tokens.length - 2).join(' ');
                        first = tokens.slice(0, -2).join(' ');
                    } else {
                        last = tokens[tokens.length - 1];
                        first = tokens.slice(0, -1).join(' ');
                    }
                }
                const initials = first.split(/\s+/).map(t => {
                    const clean = t.replace(/[^A-Za-z]/g, '');
                    return clean ? clean[0].toUpperCase() + '.' : '';
                }).filter(Boolean).join(' ');

                return initials ? `${initials} ${last}` : last;
            }).filter(Boolean);

            if (formatted.length === 0) return 'Anonymous';
            if (formatted.length === 1) return formatted[0];
            if (formatted.length === 2) return `${formatted[0]} and ${formatted[1]}`;
            if (formatted.length <= 6) {
                return `${formatted.slice(0, -1).join(', ')}, and ${formatted[formatted.length - 1]}`;
            }
            return `${formatted[0]} et al.`;
        }

        function formatApaAuthors(authorStr) {
            if (!authorStr || !authorStr.trim()) return 'Anonymous';
            let rawList = [];
            if (authorStr.includes(';') || authorStr.toLowerCase().includes(' and ') || authorStr.includes('&')) {
                rawList = authorStr.split(/;| and | & /i).map(s => s.trim()).filter(Boolean);
            } else {
                const parts = authorStr.split(',').map(s => s.trim()).filter(Boolean);
                if (parts.length === 2 && !parts[0].includes(' ') && !parts[1].includes(' ')) {
                    rawList = [authorStr];
                } else {
                    rawList = parts;
                }
            }

            const compoundPrefixes = ['dela', 'delos', 'de', 'del', 'san', 'santa', 'von', 'van', 'al', 'da'];

            const formatted = rawList.map(name => {
                let trimmed = name.trim();
                if (!trimmed) return '';
                let first = '', last = '';
                if (trimmed.includes(',')) {
                    const split = trimmed.split(',').map(s => s.trim());
                    last = split[0];
                    first = split.slice(1).join(' ');
                } else {
                    const tokens = trimmed.split(/\s+/);
                    if (tokens.length === 1) return tokens[0];
                    if (tokens.length >= 3 && compoundPrefixes.includes(tokens[tokens.length - 2].toLowerCase())) {
                        last = tokens.slice(tokens.length - 2).join(' ');
                        first = tokens.slice(0, -2).join(' ');
                    } else {
                        last = tokens[tokens.length - 1];
                        first = tokens.slice(0, -1).join(' ');
                    }
                }
                const initials = first.split(/\s+/).map(t => {
                    const clean = t.replace(/[^A-Za-z]/g, '');
                    return clean ? clean[0].toUpperCase() + '.' : '';
                }).filter(Boolean).join(' ');

                return initials ? `${last}, ${initials}` : last;
            }).filter(Boolean);

            if (formatted.length === 0) return 'Anonymous';
            if (formatted.length === 1) return formatted[0];
            if (formatted.length === 2) return `${formatted[0]}, & ${formatted[1]}`;
            if (formatted.length <= 20) {
                return `${formatted.slice(0, -1).join(', ')}, & ${formatted[formatted.length - 1]}`;
            }
            return `${formatted.slice(0, 19).join(', ')}, ... ${formatted[formatted.length - 1]}`;
        }

        function formatMlaAuthors(authorStr) {
            if (!authorStr || !authorStr.trim()) return 'Anonymous';
            let rawList = [];
            if (authorStr.includes(';') || authorStr.toLowerCase().includes(' and ') || authorStr.includes('&')) {
                rawList = authorStr.split(/;| and | & /i).map(s => s.trim()).filter(Boolean);
            } else {
                const parts = authorStr.split(',').map(s => s.trim()).filter(Boolean);
                if (parts.length === 2 && !parts[0].includes(' ') && !parts[1].includes(' ')) {
                    rawList = [authorStr];
                } else {
                    rawList = parts;
                }
            }

            if (rawList.length === 0) return 'Anonymous';
            function toLastFirst(trimmed) {
                if (trimmed.includes(',')) return trimmed;
                const tokens = trimmed.split(/\s+/);
                if (tokens.length === 1) return tokens[0];
                return `${tokens[tokens.length - 1]}, ${tokens.slice(0, -1).join(' ')}`;
            }

            if (rawList.length === 1) return toLastFirst(rawList[0]);
            if (rawList.length === 2) return `${toLastFirst(rawList[0])}, and ${rawList[1]}`;
            return `${toLastFirst(rawList[0])}, et al.`;
        }

        let currentCitationStyle = 'ieee';

        function switchCitationStyle(style) {
            currentCitationStyle = style;
            localStorage.setItem('sac_preferred_citation', style);

            const tabIeee = document.getElementById('citeTabIeee');
            const tabApa = document.getElementById('citeTabApa');
            const tabMla = document.getElementById('citeTabMla');
            const citationP = document.getElementById('citationText');

            const activeClass = 'flex-1 py-2 text-xs font-bold rounded-xl transition shadow-xs bg-white text-[#700000]';
            const inactiveClass = 'flex-1 py-2 text-xs font-bold rounded-xl transition text-gray-600 hover:text-gray-900';

            if (tabIeee) tabIeee.className = style === 'ieee' ? activeClass : inactiveClass;
            if (tabApa) tabApa.className = style === 'apa' ? activeClass : inactiveClass;
            if (tabMla) tabMla.className = style === 'mla' ? activeClass : inactiveClass;

            if (!currentCitationDoc) return;
            const author = currentCitationDoc.author || 'Author, A.';
            const cleanTitle = (currentCitationDoc.title || 'Untitled Thesis').trim().replace(/\.$/, '');
            const dateVal = currentCitationDoc.publication_date || currentCitationDoc.created_at;
            const year = dateVal ? new Date(dateVal).getFullYear() : new Date().getFullYear();
            const deptName = getFullDeptName(currentCitationDoc.department, currentCitationDoc.course, currentCitationDoc.title);

            let citation = '';
            if (style === 'apa') {
                const apaAuthors = formatApaAuthors(author);
                citation = `${apaAuthors} (${year}). ${cleanTitle} [Undergraduate thesis, St. Anthony's College]. SAC Institutional Research Repository.`;
            } else if (style === 'mla') {
                const mlaAuthors = formatMlaAuthors(author);
                citation = `${mlaAuthors}. "${cleanTitle}." Undergraduate thesis, St. Anthony's College, ${year}.`;
            } else {
                const ieeeAuthors = formatIeeeAuthors(author);
                citation = `[1] ${ieeeAuthors}, "${cleanTitle}," B.S. thesis, Dept. of ${deptName}, St. Anthony's College, San Jose, Antique, Philippines, ${year}.`;
            }

            if (citationP) citationP.textContent = citation;
            resetCopyButton();
        }

        // Open Academic Citation Modal
        function openCitationModal(index) {
            currentCitationDoc = allDocuments[index];
            if (!currentCitationDoc) return;
            document.getElementById('modalDocTitle').textContent = currentCitationDoc.title;
            const preferredStyle = localStorage.getItem('sac_preferred_citation') || 'ieee';
            switchCitationStyle(preferredStyle);
            document.getElementById('citationModal').classList.remove('hidden');
            document.getElementById('citationModal').classList.add('flex');
        }

        // Close Academic Citation Modal
        function closeCitationModal() {
            document.getElementById('citationModal').classList.add('hidden');
            document.getElementById('citationModal').classList.remove('flex');
        }

        // Copy citation text to clipboard
        function copyCitationToClipboard() {
            const text = document.getElementById('citationText').textContent;
            navigator.clipboard.writeText(text).then(() => {
                const btnText = document.getElementById('copyBtnText');
                const btnIcon = document.getElementById('copyBtnIcon');
                btnText.textContent = 'Copied!';
                btnIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                `;
                setTimeout(resetCopyButton, 2000);
            });
        }

        function resetCopyButton() {
            document.getElementById('copyBtnText').textContent = 'Copy Citation';
            document.getElementById('copyBtnIcon').innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            `;
        }

        // =========================================================
        // RIGHT-SIDE AI RESEARCH DRAWER (YOUTUBE / GEMINI STYLE)
        // =========================================================
        let activeDrawerDocId = null;

        function openDocAiDrawer(idx) {
            const doc = allDocuments[idx];
            if (!doc) return;

            activeDrawerDocId = doc.id;
            const docTitleElem = document.getElementById('aiDrawerDocTitle');
            if (docTitleElem) docTitleElem.textContent = doc.title;

            // Reset chat thread and input
            const thread = document.getElementById('aiDrawerChatThread');
            if (thread) thread.innerHTML = '';
            const input = document.getElementById('aiDrawerInput');
            if (input) input.value = '';

            const drawer = document.getElementById('aiDrawer');
            const backdrop = document.getElementById('aiDrawerBackdrop');
            if (!drawer) return;

            document.documentElement.classList.add('ai-drawer-open');
            drawer.classList.remove('translate-x-full');
            if (backdrop) {
                backdrop.classList.remove('opacity-0', 'pointer-events-none');
                backdrop.classList.add('opacity-100');
            }

            setTimeout(() => {
                if (input) input.focus();
            }, 250);
        }

        function closeDocAiDrawer() {
            const drawer = document.getElementById('aiDrawer');
            const backdrop = document.getElementById('aiDrawerBackdrop');
            if (!drawer) return;

            document.documentElement.classList.remove('ai-drawer-open');
            drawer.classList.add('translate-x-full');
            if (backdrop) {
                backdrop.classList.remove('opacity-100');
                backdrop.classList.add('opacity-0', 'pointer-events-none');
            }
        }

        async function sendDocQuickQuestion(questionText) {
            await processDocAiQuestion(questionText);
        }

        async function handleDocAiSubmit(e) {
            e.preventDefault();
            const input = document.getElementById('aiDrawerInput');
            if (!input) return;
            const question = input.value.trim();
            if (!question) return;
            input.value = '';
            await processDocAiQuestion(question);
        }

        async function processDocAiQuestion(question) {
            const thread = document.getElementById('aiDrawerChatThread');
            const typing = document.getElementById('aiDrawerTyping');
            const sendBtn = document.getElementById('aiDrawerSendBtn');
            const messagesContainer = document.getElementById('aiDrawerMessages');

            if (!thread || !typing || !sendBtn || !messagesContainer) return;

            // 1. Append user message bubble
            const userBubble = document.createElement('div');
            userBubble.className = 'flex justify-end';
            userBubble.innerHTML = `
                <div class="max-w-[85%] rounded-2xl rounded-tr-xs bg-[#700000] text-white px-4 py-2.5 text-xs sm:text-sm font-medium shadow-sm leading-relaxed">
                    ${escapeHtml(question)}
                </div>
            `;
            thread.appendChild(userBubble);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;

            // 2. Show typing indicator
            typing.classList.remove('hidden');
            typing.classList.add('flex');
            sendBtn.disabled = true;
            messagesContainer.scrollTop = messagesContainer.scrollHeight;

            try {
                const res = await fetch('/backend/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        message: question,
                        document_id: activeDrawerDocId
                    })
                });

                const data = await res.json();
                typing.classList.add('hidden');
                typing.classList.remove('flex');
                sendBtn.disabled = false;

                const aiBubble = document.createElement('div');
                aiBubble.className = 'flex items-start gap-2.5';

                let rawAnswer = data.answer || 'I could not generate an answer for this thesis.';
                let formattedAnswer = rawAnswer;

                if (typeof marked !== 'undefined' && marked.parse) {
                    formattedAnswer = marked.parse(rawAnswer);
                } else {
                    formattedAnswer = escapeHtml(rawAnswer).replace(/\n/g, '<br>');
                }

                aiBubble.innerHTML = `
                    <div class="w-6 h-6 rounded-lg bg-[#700000] text-[#FFD700] flex items-center justify-center shrink-0 shadow-2xs mt-0.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                        </svg>
                    </div>
                    <div class="flex-1 max-w-[90%] bg-white border border-gray-200 rounded-2xl rounded-tl-xs p-3.5 shadow-sm text-xs sm:text-sm text-gray-800 leading-relaxed space-y-2">
                        ${formattedAnswer}
                    </div>
                `;
                thread.appendChild(aiBubble);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;

            } catch (err) {
                console.error('AI Drawer error:', err);
                typing.classList.add('hidden');
                typing.classList.remove('flex');
                sendBtn.disabled = false;

                const errorBubble = document.createElement('div');
                errorBubble.className = 'flex items-start gap-2.5';
                errorBubble.innerHTML = `
                    <div class="w-6 h-6 rounded-lg bg-rose-600 text-white flex items-center justify-center shrink-0 text-xs font-bold mt-0.5">
                        ✕
                    </div>
                    <div class="flex-1 max-w-[90%] bg-rose-50 border border-rose-200 rounded-2xl rounded-tl-xs p-3.5 text-xs text-rose-800 font-medium">
                        Failed to connect to AI Assistant. Please try again.
                    </div>
                `;
                thread.appendChild(errorBubble);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }

        // Global Escape Listener
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeCitationModal();
                closeDocAiDrawer();
            }
        });

        // Notifications Dropdown and Fetch
        async function fetchNotifications() {
            try {
                const res = await fetch('/backend/notifications');
                if (!res.ok) return;
                const data = await res.json();
                const badge = document.getElementById('notifBadge');
                const list = document.getElementById('notifList');
                if (!badge || !list) return;

                if (data.unread_count > 0) {
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }

                if (data.notifications.length === 0) {
                    list.innerHTML = '<p class="py-4 text-center text-gray-400">No notifications yet.</p>';
                    return;
                }

                list.innerHTML = data.notifications.map(n => {
                    const iconColor = n.type === 'approved' ? 'text-emerald-500' : (n.type === 'resubmit' ? 'text-rose-500' : 'text-amber-500');
                    const timeAgo = n.created_at ? new Date(n.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';
                    return `
                        <div class="py-2.5 space-y-1 ${!n.is_read ? 'bg-amber-50/40 p-2 rounded-xl' : ''}">
                            <div class="flex items-center justify-between gap-1">
                                <h4 class="font-bold text-gray-900 ${iconColor} flex items-center gap-1.5">
                                    <span>${n.title}</span>
                                </h4>
                                <span class="text-[9px] text-gray-400 shrink-0 font-mono">${timeAgo}</span>
                            </div>
                            <p class="text-[11px] text-gray-700 leading-snug whitespace-pre-line">${n.message}</p>
                        </div>
                    `;
                }).join('');
            } catch (e) {
                console.error('Failed to fetch notifications:', e);
            }
        }

        function toggleNotificationDropdown() {
            const dd = document.getElementById('notifDropdown');
            if (!dd) return;
            dd.classList.toggle('hidden');
            if (!dd.classList.contains('hidden')) {
                fetchNotifications();
            }
        }

        async function markAllNotificationsAsRead() {
            try {
                await fetch('/backend/notifications/read-all', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                });
                fetchNotifications();
            } catch (e) {}
        }

        document.addEventListener('click', function(e) {
            const dd = document.getElementById('notifDropdown');
            const btn = document.getElementById('notifBellBtn');
            if (dd && btn && !dd.contains(e.target) && !btn.contains(e.target)) {
                dd.classList.add('hidden');
            }
        });

        // Initial load on page ready
        async function init() {
            await fetchBookmarkIds();
            await fetchDocuments();
            fetchNotifications();
        }

        init();
    </script>
</body>

</html>