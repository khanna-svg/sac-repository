<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Saved / Bookmarks - SAC Thesis Repository</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    </script>

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

    @include('partials.sidebar')

    <main id="mainContent" class="md:ml-64 min-h-screen p-4 sm:p-6 md:p-10 transition-all duration-300 ease-in-out pt-16 md:pt-10">
        <div class="mx-auto max-w-5xl">

            <!-- HEADER -->
            <section class="mb-6 md:mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-[#700000] flex items-center gap-2.5">
                            <span>Saved / Bookmarks</span>
                        </h1>
                        <p class="mt-1 text-xs md:text-sm text-gray-500">
                            Your saved thesis and capstone projects for quick reading and citation.
                        </p>
                    </div>
                </div>
            </section>

            <!-- DOCUMENT LIST CONTAINER -->
            <section id="documentsList" class="space-y-4">
                <p class="text-center text-sm text-gray-500 py-10">
                    Loading your saved theses...
                </p>
            </section>

        </div>
    </main>

    <!-- FLOATING TOAST NOTIFICATION POP-UP -->
    <div id="toastNotification" class="fixed bottom-6 right-6 z-50 transform transition-all duration-300 translate-y-20 opacity-0 pointer-events-none">
        <div class="flex items-center gap-3 rounded-2xl bg-white text-gray-900 px-5 py-3.5 shadow-xl border border-gray-200">
            <span id="toastIconContainer" class="shrink-0">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </span>
            <p id="toastMessage" class="text-xs md:text-sm font-semibold text-gray-800 tracking-wide">Removed from bookmark</p>
        </div>
    </div>

    <!-- ACADEMIC CITATION MODAL (IEEE, APA 7th, MLA 9th) -->
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
                        IEEE (Standard)
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

    <!-- =========================================================
         PROTECTED SECURE PDF READER MODAL (CONTINUOUS SCROLLABLE CANVAS)
    ========================================================== -->
    <div
        id="securePdfModal"
        class="fixed inset-0 z-50 hidden bg-slate-950/95 backdrop-blur-md flex-col select-none"
        oncontextmenu="return false;">

        <!-- Top Reader Header -->
        <div class="flex items-center justify-between px-4 md:px-6 py-3 bg-[#500000] text-white border-b border-[#700000] shadow-md shrink-0">
            <div class="flex items-center gap-3 min-w-0 pr-4">
                <h3 id="securePdfDocTitle" class="text-xs md:text-sm font-bold text-white truncate">
                    Protected Thesis Manuscript
                </h3>
            </div>

            <!-- Reader Controls (Total Pages, Zoom, Close) -->
            <div class="flex items-center gap-2 shrink-0">
                <!-- Total Pages Badge -->
                <div class="flex items-center bg-black/40 rounded-xl px-3 py-1 border border-white/10 text-xs">
                    <span id="pageCount" class="text-amber-200 font-mono text-[11px]">Loading...</span>
                </div>

                <!-- Zoom Controls -->
                <div class="hidden sm:flex items-center gap-1 bg-black/40 rounded-xl px-2 py-1 border border-white/10 text-xs">
                    <button
                        type="button"
                        onclick="onZoomOut()"
                        class="p-1 rounded hover:bg-white/20 text-white cursor-pointer"
                        title="Zoom Out">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                        </svg>
                    </button>
                    <span id="zoomPercent" class="px-1 text-[11px] font-mono text-gray-300">100%</span>
                    <button
                        type="button"
                        onclick="onZoomIn()"
                        class="p-1 rounded hover:bg-white/20 text-white cursor-pointer"
                        title="Zoom In">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </button>
                </div>

                <!-- Close Reader Button -->
                <button
                    type="button"
                    onclick="closeSecurePdfReader()"
                    class="rounded-xl p-1.5 bg-white/10 hover:bg-white/20 text-white transition ml-2 cursor-pointer"
                    title="Close (Esc)">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Security Policy Sub-header -->
        <div class="bg-black/60 text-amber-200/90 text-[10px] sm:text-xs py-1 px-4 text-center border-b border-white/5 flex items-center justify-center gap-2 shrink-0">
            <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
            <span>St. Anthony's College Protected Document • Copying, printing, and downloading are prohibited by institutional policy.</span>
        </div>

        <!-- Continuous Vertical Scrollable Canvas Container -->
        <div id="pdfScrollContainer" class="flex-1 overflow-y-auto p-4 md:p-8 flex flex-col items-center relative bg-slate-900 scroll-smooth">
            <!-- Loading Spinner -->
            <div id="pdfLoader" class="sticky top-20 flex flex-col items-center justify-center gap-3 bg-slate-950/90 p-6 rounded-2xl border border-white/10 z-20 shadow-2xl">
                <svg class="w-8 h-8 animate-spin text-[#FFD700]" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-xs font-semibold text-amber-200">Loading protected manuscript...</p>
            </div>

            <!-- Pages Canvas List -->
            <div id="pdfPagesWrapper" class="flex flex-col items-center gap-6 w-full max-w-3xl"></div>
        </div>
    </div>

    {{-- =========================================================
         RIGHT-SIDE AI RESEARCH ASSISTANT DRAWER (YOUTUBE / GEMINI STYLE)
    ========================================================== --}}
    <!-- Mobile Backdrop for AI Drawer (Only on mobile where screen cannot squeeze) -->
    <div
        id="aiDrawerBackdrop"
        onclick="closeBookmarkAiDrawer()"
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
                onclick="closeBookmarkAiDrawer()"
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
                    <div class="w-6 h-6 rounded-lg bg-[#700000] text-[#FFD700] flex items-center justify-center shrink-0 shadow-2xs mt-0.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
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
                        onclick="sendBookmarkQuickQuestion('Summarize this thesis in 3 concise bullet points.')"
                        class="text-left px-3.5 py-2.5 rounded-xl bg-white border border-gray-200 text-xs font-semibold text-gray-700 hover:border-[#700000] hover:text-[#700000] hover:shadow-xs transition cursor-pointer">
                        📑 Summarize this thesis
                    </button>

                    <button
                        type="button"
                        onclick="sendBookmarkQuickQuestion('What is the main problem and objective of this research?')"
                        class="text-left px-3.5 py-2.5 rounded-xl bg-white border border-gray-200 text-xs font-semibold text-gray-700 hover:border-[#700000] hover:text-[#700000] hover:shadow-xs transition cursor-pointer">
                        🎯 What problem does this study solve?
                    </button>

                    <button
                        type="button"
                        onclick="sendBookmarkQuickQuestion('What methodology, tools, and technologies were used in this system?')"
                        class="text-left px-3.5 py-2.5 rounded-xl bg-white border border-gray-200 text-xs font-semibold text-gray-700 hover:border-[#700000] hover:text-[#700000] hover:shadow-xs transition cursor-pointer">
                        💻 What methodology and tech stack was used?
                    </button>

                    <button
                        type="button"
                        onclick="sendBookmarkQuickQuestion('What are the key conclusions, findings, and recommendations of this study?')"
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
            <form id="aiDrawerForm" onsubmit="handleBookmarkAiSubmit(event)" class="relative flex items-center">
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
                Grounded in St. Anthony's College research • Powered by Gemini
            </p>
        </div>

    </aside>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const COVERS_BASE_URL = "{{ asset('images/covers') }}";

        let bookmarkedDocuments = [];
        let currentCitationDoc = null;
        let toastTimeout = null;

        const documentsList = document.getElementById('documentsList');
        const bookmarkCountBadge = document.getElementById('bookmarkCountBadge');

        function showToast(message) {
            const toast = document.getElementById('toastNotification');
            const toastMsg = document.getElementById('toastMessage');

            if (!toast || !toastMsg) return;

            toastMsg.textContent = message;
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

        function handleImageError(imageElement) {
            imageElement.onerror = null;
            imageElement.src = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='100' height='140' viewBox='0 0 100 140'><rect width='100%' height='100%' fill='%23700000'/><text x='50%' y='50%' font-size='12' font-weight='bold' fill='%23FFD700' text-anchor='middle' dominant-baseline='middle'>SAC THESIS</text></svg>";
        }

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

        async function toggleBookmark(docId) {
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

                if (res.ok) {
                    bookmarkedDocuments = bookmarkedDocuments.filter(d => d.id !== docId);
                    renderDocuments(bookmarkedDocuments);
                    showToast('Removed from bookmark');
                }
            } catch (err) {
                console.error('Failed to toggle bookmark:', err);
            }
        }

        function renderDocuments(documents) {
            if (!Array.isArray(documents) || documents.length === 0) {
                documentsList.innerHTML = `
                    <div class="rounded-3xl border border-dashed border-gray-300 bg-white p-12 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-500 mb-3 border border-amber-200">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-gray-800">No saved thesis</h3>
                        <p class="mt-1 text-xs text-gray-500">When you bookmark a thesis in the repository, it will appear here for easy access.</p>
                        <a href="/documents" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-[#700000] px-4 py-2.5 text-xs font-bold text-[#FFD700] hover:bg-[#800000] shadow-sm transition">
                            <span>Explore Thesis Repository</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                    </div>
                `;
                if (bookmarkCountBadge) bookmarkCountBadge.textContent = '0 Saved Theses';
                return;
            }

            if (bookmarkCountBadge) bookmarkCountBadge.textContent = `${documents.length} Saved Theses`;

            documentsList.innerHTML = documents.map((doc, idx) => {
                const details = getDepartmentDetails(doc.department, doc.course_code, doc.title);
                const isLongAbstract = (doc.abstract || '').length > 200;
                const truncatedAbstract = isLongAbstract ? doc.abstract.substring(0, 200) + '...' : doc.abstract;
                const rawDate = doc.publication_date || doc.created_at;
                const pubDateStr = rawDate ? new Date(rawDate).toLocaleDateString('en-US', {
                    month: 'short',
                    year: 'numeric'
                }) : '';

                return `
                    <article class="relative flex flex-col md:flex-row gap-5 rounded-3xl border border-gray-200 bg-white p-5 md:p-6 shadow-sm hover:shadow-md hover:border-[#700000]/30 transition">
                        
                        <!-- Top-Right Active Yellow Bookmark Icon Button -->
                        <button
                            type="button"
                            onclick="toggleBookmark(${doc.id})"
                            title="Remove from saved"
                            class="absolute top-4 right-4 md:top-6 md:right-6 p-2 rounded-xl border bg-amber-50 border-amber-300 text-amber-500 shadow-sm transition hover:bg-red-50 hover:text-red-600 hover:border-red-300">
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                            </svg>
                        </button>

                        <!-- Book Cover Image -->
                        <div class="w-full md:w-28 h-36 md:h-36 rounded-2xl border border-gray-200 bg-slate-100 overflow-hidden shadow-sm shrink-0">
                            <img
                                src="${COVERS_BASE_URL}/${details.cover}"
                                alt="${escapeHtml(doc.title)} Cover"
                                class="w-full h-full object-cover"
                                onerror="handleImageError(this)">
                        </div>

                        <div class="flex-1 min-w-0 pr-8">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-500 font-semibold">
                                <span class="font-bold text-[#700000]">St. Anthony's College</span>
                                <span class="text-gray-300">•</span>
                                <span class="text-gray-700">${escapeHtml(details.name)}</span>
                                ${pubDateStr ? `<span class="text-gray-300">•</span><span class="text-gray-500 font-medium">${pubDateStr}</span>` : ''}
                            </div>

                            <h3 class="mt-2.5 text-base md:text-lg font-bold text-gray-900 transition">
                                <a href="/documents/${doc.id}" class="hover:text-[#700000] hover:underline cursor-pointer">
                                    ${escapeHtml(doc.title)}
                                </a>
                            </h3>

                            <p class="mt-1 text-xs md:text-sm font-semibold text-[#700000]">
                                by ${escapeHtml(doc.author || 'Unknown Author')}
                            </p>

                            <div class="mt-3 text-xs md:text-sm text-gray-600 leading-relaxed">
                                <p>${escapeHtml(truncatedAbstract)}</p>
                            </div>

                            <div class="mt-5 pt-4 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        onclick="openCitationModal(${idx})"
                                        class="rounded-xl border border-gray-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-gray-700 hover:bg-[#700000] hover:text-[#FFD700] hover:border-[#700000] transition flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                                        </svg>
                                        <span>Citation</span>
                                    </button>

                                    <button
                                        type="button"
                                        onclick="openBookmarkAiDrawer(${idx})"
                                        class="group rounded-xl border border-gray-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-gray-700 hover:bg-[#700000] hover:text-[#FFD700] hover:border-[#700000] transition flex items-center gap-1.5 cursor-pointer">
                                        <svg class="w-3.5 h-3.5 shrink-0 text-[#700000] group-hover:text-[#FFD700] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                                        </svg>
                                        <span>Ask AI</span>
                                    </button>

                                    <button
                                        type="button"
                                        onclick="openSecurePdfReader(${idx})"
                                        class="rounded-xl bg-[#700000] px-4 py-2 text-xs font-bold text-[#FFD700] hover:bg-[#850000] transition flex items-center gap-1.5 shadow-sm">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                        </svg>
                                        <span>View PDF (Protected)</span>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </article>
                `;
            }).join('');
        }

        async function fetchBookmarks() {
            try {
                const res = await fetch('/backend/bookmarks', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (res.status === 401) {
                    window.location.href = '/login';
                    return;
                }

                if (!res.ok) throw new Error('Failed to load');
                bookmarkedDocuments = await res.json();
                renderDocuments(bookmarkedDocuments);
            } catch (err) {
                console.error('fetchBookmarks error:', err);
                renderDocuments([]);
            }
        }

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
                let first = '',
                    last = '';
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
                let first = '',
                    last = '';
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

        function openCitationModal(index) {
            currentCitationDoc = bookmarkedDocuments[index];
            if (!currentCitationDoc) return;
            document.getElementById('modalDocTitle').textContent = currentCitationDoc.title;
            const preferredStyle = localStorage.getItem('sac_preferred_citation') || 'ieee';
            switchCitationStyle(preferredStyle);
            document.getElementById('citationModal').classList.remove('hidden');
            document.getElementById('citationModal').classList.add('flex');
        }

        function closeCitationModal() {
            document.getElementById('citationModal').classList.add('hidden');
            document.getElementById('citationModal').classList.remove('flex');
        }

        function copyCitationToClipboard() {
            const text = document.getElementById('citationText').textContent;
            navigator.clipboard.writeText(text).then(() => {
                const btnText = document.getElementById('copyBtnText');
                const btnIcon = document.getElementById('copyBtnIcon');
                btnText.textContent = 'Copied!';
                btnIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />`;
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

        function openBookmarkAiDrawer(idx) {
            const doc = bookmarkedDocuments[idx];
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

        function closeBookmarkAiDrawer() {
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

        async function sendBookmarkQuickQuestion(questionText) {
            await processBookmarkAiQuestion(questionText);
        }

        async function handleBookmarkAiSubmit(e) {
            e.preventDefault();
            const input = document.getElementById('aiDrawerInput');
            if (!input) return;
            const question = input.value.trim();
            if (!question) return;
            input.value = '';
            await processBookmarkAiQuestion(question);
        }

        async function processBookmarkAiQuestion(question) {
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

        // =========================================================
        // PROTECTED SECURE PDF VIEWER (CONTINUOUS SCROLLABLE CANVAS)
        // =========================================================
        let pdfDoc = null;
        let currentScale = 1.3;
        let pageObserver = null;
        let renderedPages = new Set();
        let renderingPages = new Set();
        let pageDimensions = {
            width: 600,
            height: 800
        };

        async function openSecurePdfReader(idx) {
            const doc = bookmarkedDocuments[idx];
            if (!doc) return;

            const modal = document.getElementById('securePdfModal');
            const loader = document.getElementById('pdfLoader');
            const pagesWrapper = document.getElementById('pdfPagesWrapper');
            const titleElem = document.getElementById('securePdfDocTitle');

            if (titleElem) titleElem.textContent = doc.title || 'Protected Thesis Manuscript';

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            loader.classList.remove('hidden');
            pagesWrapper.innerHTML = '';
            renderedPages.clear();
            renderingPages.clear();

            if (pageObserver) {
                pageObserver.disconnect();
                pageObserver = null;
            }

            try {
                const res = await fetch(`/backend/documents/${doc.id}/signed-url`);
                if (!res.ok) throw new Error('Could not obtain secure PDF link');
                const data = await res.json();
                if (!data.url) throw new Error('Invalid PDF URL');

                const loadingTask = pdfjsLib.getDocument({
                    url: data.url,
                    cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
                    cMapPacked: true
                });
                pdfDoc = await loadingTask.promise;
                document.getElementById('pageCount').textContent = `${pdfDoc.numPages} Pages`;

                const firstPage = await pdfDoc.getPage(1);
                const firstViewport = firstPage.getViewport({
                    scale: currentScale
                });
                pageDimensions.width = firstViewport.width;
                pageDimensions.height = firstViewport.height;

                createPagePlaceholders();
                setupPageObserver();

                renderPage(1);
                if (pdfDoc.numPages >= 2) {
                    renderPage(2);
                }

                loader.classList.add('hidden');
            } catch (err) {
                console.error('Error loading secure PDF:', err);
                loader.innerHTML = `
                    <div class="p-6 text-center text-red-400 bg-slate-900 rounded-2xl border border-red-500/30 max-w-sm mx-auto">
                        <p class="font-bold text-sm">Unable to render protected PDF</p>
                        <p class="text-xs text-gray-400 mt-1">Please try again later or check your network connection.</p>
                        <button onclick="closeSecurePdfReader()" class="mt-4 px-4 py-2 rounded-xl bg-[#700000] text-[#FFD700] font-bold text-xs cursor-pointer">Close Reader</button>
                    </div>
                `;
            }
        }

        function createPagePlaceholders() {
            if (!pdfDoc) return;
            const pagesWrapper = document.getElementById('pdfPagesWrapper');
            pagesWrapper.innerHTML = '';

            const fragment = document.createDocumentFragment();

            for (let num = 1; num <= pdfDoc.numPages; num++) {
                const card = document.createElement('div');
                card.id = `pdf-page-${num}`;
                card.dataset.pageNumber = num;
                card.className = 'pdf-page-card flex flex-col items-center bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-300 w-full max-w-full relative transition-all';
                card.style.minHeight = `${pageDimensions.height}px`;

                card.innerHTML = `
                    <div class="page-placeholder flex-1 flex flex-col items-center justify-center w-full bg-slate-50 text-gray-400 py-16" style="min-height: ${pageDimensions.height - 35}px;">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="w-6 h-6 text-gray-300 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-[11px] font-mono text-gray-400 font-medium">Page ${num}</span>
                        </div>
                    </div>
                    <div class="page-footer w-full py-1.5 bg-slate-100 border-t border-gray-200 text-center text-[10px] sm:text-xs font-semibold text-gray-500 tracking-wider uppercase font-mono">
                        Page ${num} of ${pdfDoc.numPages}
                    </div>
                `;

                fragment.appendChild(card);
            }

            pagesWrapper.appendChild(fragment);
            document.getElementById('zoomPercent').textContent = Math.round((currentScale / 1.3) * 100) + '%';
        }

        function setupPageObserver() {
            if (pageObserver) {
                pageObserver.disconnect();
            }

            const scrollContainer = document.getElementById('pdfScrollContainer');

            pageObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const pageNum = parseInt(entry.target.dataset.pageNumber, 10);
                        if (pageNum && !renderedPages.has(pageNum) && !renderingPages.has(pageNum)) {
                            renderPage(pageNum);
                        }
                    }
                });
            }, {
                root: scrollContainer,
                rootMargin: '450px 0px',
                threshold: 0.01
            });

            document.querySelectorAll('.pdf-page-card').forEach((card) => {
                pageObserver.observe(card);
            });
        }

        async function renderPage(num) {
            if (!pdfDoc || renderedPages.has(num) || renderingPages.has(num)) return;
            renderingPages.add(num);

            const card = document.getElementById(`pdf-page-${num}`);
            if (!card) {
                renderingPages.delete(num);
                return;
            }

            try {
                const page = await pdfDoc.getPage(num);
                const viewport = page.getViewport({
                    scale: currentScale
                });

                const canvas = document.createElement('canvas');
                canvas.className = 'block max-w-full h-auto';
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                const ctx = canvas.getContext('2d');
                const renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };

                await page.render(renderContext).promise;

                const placeholder = card.querySelector('.page-placeholder');
                const existingCanvas = card.querySelector('canvas');
                const footer = card.querySelector('.page-footer');

                if (existingCanvas) existingCanvas.remove();
                if (placeholder) placeholder.remove();

                card.insertBefore(canvas, footer);
                card.style.minHeight = `${viewport.height}px`;

                renderedPages.add(num);
            } catch (err) {
                console.error(`Error rendering page ${num}:`, err);
            } finally {
                renderingPages.delete(num);
            }
        }

        async function onZoomIn() {
            if (currentScale >= 2.5) return;
            currentScale += 0.2;
            await reScalePages();
        }

        async function onZoomOut() {
            if (currentScale <= 0.7) return;
            currentScale -= 0.2;
            await reScalePages();
        }

        async function reScalePages() {
            if (!pdfDoc) return;
            document.getElementById('zoomPercent').textContent = Math.round((currentScale / 1.3) * 100) + '%';

            const firstPage = await pdfDoc.getPage(1);
            const firstViewport = firstPage.getViewport({
                scale: currentScale
            });
            pageDimensions.width = firstViewport.width;
            pageDimensions.height = firstViewport.height;

            renderedPages.clear();
            renderingPages.clear();

            document.querySelectorAll('.pdf-page-card').forEach((card) => {
                card.style.minHeight = `${pageDimensions.height}px`;
                const canvas = card.querySelector('canvas');
                if (canvas) canvas.remove();
                if (!card.querySelector('.page-placeholder')) {
                    const num = card.dataset.pageNumber;
                    const placeholder = document.createElement('div');
                    placeholder.className = 'page-placeholder flex-1 flex flex-col items-center justify-center w-full bg-slate-50 text-gray-400 py-16';
                    placeholder.style.minHeight = `${pageDimensions.height - 35}px`;
                    placeholder.innerHTML = `
                        <div class="flex flex-col items-center gap-2">
                            <svg class="w-6 h-6 text-gray-300 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-[11px] font-mono text-gray-400 font-medium">Page ${num}</span>
                        </div>
                    `;
                    const footer = card.querySelector('.page-footer');
                    card.insertBefore(placeholder, footer);
                }
            });

            setupPageObserver();
        }

        function closeSecurePdfReader() {
            const modal = document.getElementById('securePdfModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
            if (pageObserver) {
                pageObserver.disconnect();
                pageObserver = null;
            }
            pdfDoc = null;
        }

        // Global Escape Listener
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeCitationModal();
                closeBookmarkAiDrawer();
                closeSecurePdfReader();
            }
        });

        fetchBookmarks();
    </script>
</body>

</html>