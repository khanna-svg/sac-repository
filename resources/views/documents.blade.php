<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAC Thesis Repository - Documents</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="min-h-screen bg-slate-50 text-slate-800 font-sans">

    @include('partials.sidebar')

    <main class="md:ml-64 min-h-screen p-4 sm:p-6 md:p-10 transition-all pt-16 md:pt-10">
        <div class="mx-auto max-w-5xl">

            <!-- HEADER -->
            <section class="mb-6 md:mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-[#700000]">
                            Thesis Repository
                        </h1>
                        <p class="mt-1 text-xs md:text-sm text-gray-500">
                            Search, cite, and view approved St. Anthony's College thesis.
                        </p>
                    </div>
                </div>
            </section>

            <!-- SEARCH BAR -->
            <!-- SEARCH BAR WITH SEMANTIC TOGGLE -->
            <section class="mb-8">
                <form id="searchForm" class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Search Mode:</span>
                        <div class="inline-flex rounded-xl bg-slate-200/70 p-1 border border-gray-300">
                            <button
                                type="button"
                                id="modeKeywordBtn"
                                onclick="setSearchMode('keyword')"
                                class="rounded-lg px-3 py-1 text-xs font-bold bg-white text-[#700000] shadow-sm transition">
                                🔍 Keyword
                            </button>
                            <button
                                type="button"
                                id="modeSemanticBtn"
                                onclick="setSearchMode('semantic')"
                                class="rounded-lg px-3 py-1 text-xs font-bold text-gray-600 hover:text-gray-900 transition">
                                ✨ Semantic AI
                            </button>
                        </div>
                        <span id="semanticExplainer" class="text-[11px] text-gray-400 hidden sm:inline">
                            (Searches concepts & meanings using AI embeddings)
                        </span>
                    </div>

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
                                placeholder="Search by topic, author, keywords, or department..."
                                class="w-full rounded-2xl border border-gray-300 bg-white pl-10 pr-4 py-3 text-xs md:text-sm text-gray-800 placeholder-gray-400 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000] shadow-sm transition">
                        </div>
                        <button
                            type="submit"
                            class="rounded-2xl bg-[#700000] px-7 py-3 text-xs md:text-sm font-bold text-[#FFD700] hover:bg-[#800000] transition shadow-md shrink-0 flex items-center justify-center gap-2">
                            <span>Search</span>
                        </button>
                    </div>
                </form>
            </section>

            <!-- DOCUMENT LIST CONTAINER -->
            <section id="documentsList" class="space-y-4">
                <p class="text-center text-sm text-gray-500 py-10">
                    Loading thesis repository...
                </p>
            </section>

        </div>
    </main>

    <!-- FLOATING TOAST NOTIFICATION POP-UP -->
    <div id="toastNotification" class="fixed bottom-6 right-6 z-50 transform transition-all duration-300 translate-y-20 opacity-0 pointer-events-none">
        <div class="flex items-center gap-3 rounded-2xl bg-gray-900 text-white px-5 py-3.5 shadow-2xl backdrop-blur-md border border-white/10">
            <span id="toastIconContainer" class="shrink-0">
                <svg id="toastBookmarkIcon" class="w-5 h-5 text-amber-400 fill-current" viewBox="0 0 24 24">
                    <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
            </span>
            <p id="toastMessage" class="text-xs md:text-sm font-semibold tracking-wide">Added to bookmark</p>
        </div>
    </div>

    <!-- CITATION MODAL (IEEE ONLY) -->
    <div id="citationModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl transition-all">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-[#700000]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                    </svg>
                    <h3 class="text-base md:text-lg font-bold text-gray-900">IEEE Citation</h3>
                </div>
                <button onclick="closeCitationModal()" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mt-4">
                <p id="modalDocTitle" class="text-xs font-bold text-[#700000] truncate"></p>
                <div class="mt-4 flex rounded-xl bg-slate-100 p-1 border border-gray-200">
                    <div class="w-full py-1.5 text-xs font-bold rounded-lg bg-white text-[#700000] shadow-sm text-center">
                        IEEE Style Format
                    </div>
                </div>
                <div class="mt-4 rounded-2xl border border-gray-200 bg-slate-50 p-4">
                    <p id="citationText" class="text-xs md:text-sm text-gray-800 leading-relaxed font-mono select-all break-words"></p>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <button onclick="closeCitationModal()" class="rounded-xl px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-100 transition">
                    Close
                </button>
                <button id="copyCitationBtn" onclick="copyCitationToClipboard()" class="rounded-xl bg-[#700000] px-5 py-2.5 text-xs font-bold text-[#FFD700] hover:bg-[#800000] transition shadow-md flex items-center gap-1.5">
                    <svg id="copyBtnIcon" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span id="copyBtnText">Copy Citation</span>
                </button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const COVERS_BASE_URL = "{{ asset('images/covers') }}";

        let allDocuments = [];
        let currentCitationDoc = null;
        let savedBookmarkIds = new Set();
        let toastTimeout = null;
        let currentSearchMode = 'keyword';

        const documentsList = document.getElementById('documentsList');
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');
        const docCountBadge = document.getElementById('docCountBadge');

        function setSearchMode(mode) {
            currentSearchMode = mode;
            const kwBtn = document.getElementById('modeKeywordBtn');
            const semBtn = document.getElementById('modeSemanticBtn');
            const input = document.getElementById('searchInput');
            if (mode === 'semantic') {
                semBtn.className = 'rounded-lg px-3 py-1 text-xs font-bold bg-[#700000] text-[#FFD700] shadow-sm transition';
                kwBtn.className = 'rounded-lg px-3 py-1 text-xs font-bold text-gray-600 hover:text-gray-900 transition';
                input.placeholder = 'Ask or describe concepts (e.g., "how to reduce maritime accidents" or "hospital patient care")...';
            } else {
                kwBtn.className = 'rounded-lg px-3 py-1 text-xs font-bold bg-white text-[#700000] shadow-sm transition';
                semBtn.className = 'rounded-lg px-3 py-1 text-xs font-bold text-gray-600 hover:text-gray-900 transition';
                input.placeholder = 'Search by topic, author, keywords, or department...';
            }
            if (input.value.trim()) {
                fetchDocuments(input.value);
            }
        }

        function showToast(message, isSaved = true) {
            const toast = document.getElementById('toastNotification');
            const toastMsg = document.getElementById('toastMessage');
            const toastIconContainer = document.getElementById('toastIconContainer');

            if (!toast || !toastMsg) return;

            toastMsg.textContent = message;
            if (isSaved) {
                toastIconContainer.innerHTML = `
                    <svg class="w-5 h-5 text-amber-400 fill-current" viewBox="0 0 24 24">
                        <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                    </svg>
                `;
            } else {
                toastIconContainer.innerHTML = `
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
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

        function handleImageError(imageElement) {
            imageElement.onerror = null;
            imageElement.src = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='100' height='140' viewBox='0 0 100 140'><rect width='100%' height='100%' fill='%23700000'/><text x='50%' y='50%' font-size='12' font-weight='bold' fill='%23FFD700' text-anchor='middle' dominant-baseline='middle'>SAC THESIS</text></svg>";
        }

        function getDepartmentDetails(deptVal, courseVal, titleVal) {
            const dept = (deptVal || '').toLowerCase();
            const course = (courseVal || '').toLowerCase();
            const title = (titleVal || '').toLowerCase();

            if (dept === 'nursing' || course === 'bsn' || title.includes('patient') || title.includes('nursing')) {
                return {
                    cover: 'NURSING.webp',
                    name: 'Nursing Department',
                    badgeBg: 'bg-emerald-50 text-emerald-700 border-emerald-200'
                };
            } else if (dept === 'marine' || course === 'bsmare' || title.includes('marine') || title.includes('vessel')) {
                return {
                    cover: 'MARINE.webp',
                    name: 'Marine Engineering Department',
                    badgeBg: 'bg-sky-50 text-sky-700 border-sky-200'
                };
            } else if (dept === 'it' || course === 'bsit' || title.includes('system') || title.includes('app') || title.includes('web')) {
                return {
                    cover: 'IT.webp',
                    name: 'Information Technology Department',
                    badgeBg: 'bg-blue-50 text-blue-700 border-blue-200'
                };
            } else if (dept === 'hospitality' || course === 'bshm' || title.includes('hotel') || title.includes('hospitality')) {
                return {
                    cover: 'HM.webp',
                    name: 'Hospitality Management',
                    badgeBg: 'bg-amber-50 text-amber-800 border-amber-200'
                };
            } else if (dept === 'education' || course === 'bsed' || title.includes('teaching') || title.includes('education')) {
                return {
                    cover: 'EDUC.webp',
                    name: 'Education Department',
                    badgeBg: 'bg-purple-50 text-purple-700 border-purple-200'
                };
            } else if (dept === 'criminology' || course === 'bsc' || title.includes('crime') || title.includes('criminology')) {
                return {
                    cover: 'CRIM.webp',
                    name: 'Criminology Department',
                    badgeBg: 'bg-red-50 text-red-700 border-red-200'
                };
            }

            return {
                cover: 'IT.webp',
                name: 'Academic Research',
                badgeBg: 'bg-[#700000]/10 text-[#700000] border-[#700000]/20'
            };
        }

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
                        <p class="mt-1 text-xs text-gray-500">Try searching for different keywords or authors.</p>
                    </div>
                `;
                docCountBadge.textContent = '0 Theses Found';
                return;
            }

            docCountBadge.textContent = `${documents.length} Theses Available`;

            documentsList.innerHTML = documents.map((doc, idx) => {
                const details = getDepartmentDetails(doc.department, doc.course_code, doc.title);
                const isLongAbstract = (doc.abstract || '').length > 200;
                const truncatedAbstract = isLongAbstract ? doc.abstract.substring(0, 200) + '...' : doc.abstract;
                const isSaved = savedBookmarkIds.has(doc.id);

                return `
                    <article class="relative flex flex-col md:flex-row gap-5 rounded-3xl border border-gray-200 bg-white p-5 md:p-6 shadow-sm hover:shadow-md hover:border-[#700000]/30 transition">
                        
                        <!-- Top-Right Bookmark Icon Button -->
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

                        <!-- Book Cover Image -->
                        <div class="w-full md:w-28 h-36 md:h-36 rounded-2xl border border-gray-200 bg-slate-100 overflow-hidden shadow-sm shrink-0">
                            <img
                                src="${COVERS_BASE_URL}/${details.cover}"
                                alt="${escapeHtml(doc.title)} Cover"
                                class="w-full h-full object-cover"
                                onerror="handleImageError(this)">
                        </div>

                        <div class="flex-1 min-w-0 pr-8">
                            <div class="flex flex-wrap items-center gap-2">
                            ${doc.similarity_score ? `
                                <span class="rounded-lg bg-amber-100 text-amber-900 border border-amber-300 px-2.5 py-0.5 text-[10px] font-extrabold flex items-center gap-1">
                                    ✨ ${doc.similarity_score}% Match
                                </span>
                            ` : ''}
                                <span class="rounded-lg border px-2.5 py-0.5 text-[10px] font-bold ${details.badgeBg}">
                                    ${details.name}
                                </span>
                                <span class="rounded-lg bg-green-50 text-green-700 border border-green-200 px-2.5 py-0.5 text-[10px] font-bold flex items-center gap-1">
                                    <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Full Text Available
                                </span>
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
                                <span id="abstract-short-${doc.id}">${escapeHtml(truncatedAbstract)}</span>
                                ${isLongAbstract ? `
                                    <span id="abstract-full-${doc.id}" class="hidden">${escapeHtml(doc.abstract)}</span>
                                    <button type="button" onclick="toggleAbstract(${doc.id})" id="abstract-btn-${doc.id}" class="ml-1 text-xs font-bold text-[#700000] hover:underline">
                                        Read More
                                    </button>
                                ` : ''}
                            </div>

                            <!-- Bottom Action Buttons (Cite & Ask AI) -->
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
                                        <span>Cite (IEEE)</span>
                                    </button>

                                    <a
                                        href="/chat?q=${encodeURIComponent('Tell me about the thesis: ' + doc.title)}"
                                        class="rounded-xl border border-gray-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-gray-700 hover:bg-[#700000] hover:text-[#FFD700] hover:border-[#700000] transition flex items-center gap-1.5"
                                    >
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                                        </svg>
                                        <span>Ask AI</span>
                                    </a>
                                </div>
                            </div>

                        </div>
                    </article>
                `;
            }).join('');
        }

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

        async function fetchBookmarkIds() {
            try {
                const res = await fetch('/backend/bookmarks/ids');
                if (res.ok) {
                    const ids = await res.json();
                    savedBookmarkIds = new Set(ids);
                }
            } catch (e) {}
        }

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

        async function fetchDocuments(search = '') {
            documentsList.innerHTML = `<p class="text-center text-sm text-gray-500 py-10">Searching documents...</p>`;
            try {
                const url = new URL('/backend/documents', window.location.origin);
                if (search && search.trim()) {
                    url.searchParams.set('search', search.trim());
                    url.searchParams.set('search_type', currentSearchMode);
                }
                const res = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (!res.ok) throw new Error('Failed to load');
                allDocuments = await res.json();
                renderDocuments(allDocuments);
            } catch (err) {
                documentsList.innerHTML = `
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-6 text-center text-sm text-red-700">
                        Could not load documents from server.
                    </div>
                `;
            }
        }

        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            fetchDocuments(searchInput.value);
        });

        function openCitationModal(index) {
            currentCitationDoc = allDocuments[index];
            if (!currentCitationDoc) return;
            document.getElementById('modalDocTitle').textContent = currentCitationDoc.title;
            generateCitationText();
            document.getElementById('citationModal').classList.remove('hidden');
            document.getElementById('citationModal').classList.add('flex');
        }

        function closeCitationModal() {
            document.getElementById('citationModal').classList.add('hidden');
            document.getElementById('citationModal').classList.remove('flex');
        }

        function generateCitationText() {
            if (!currentCitationDoc) return;
            const author = currentCitationDoc.author || 'Author, A.';
            const title = currentCitationDoc.title || 'Untitled Thesis';
            const year = currentCitationDoc.created_at ? new Date(currentCitationDoc.created_at).getFullYear() : new Date().getFullYear();
            const citation = `${author}, "${title}," Undergraduate thesis, Dept. of Research, St. Anthony's College, San Jose, Antique, ${year}.`;
            document.getElementById('citationText').textContent = citation;
            resetCopyButton();
        }

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

        async function init() {
            await fetchBookmarkIds();
            await fetchDocuments();
        }

        init();
    </script>
</body>

</html>