<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAC Thesis Repository - Documents</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                            Search, cite, and view approved St. Anthony's College thesis documents.
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span id="docCountBadge" class="rounded-xl bg-[#700000]/10 px-3.5 py-1.5 text-xs font-bold text-[#700000] border border-[#700000]/20">
                            Loading repository...
                        </span>
                    </div>
                </div>
            </section>

            <!-- SEARCH BAR -->
            <section class="mb-8">
                <form id="searchForm" class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-400">
                            🔍
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
                        Search
                    </button>
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

    <!-- ======================================================== -->
    <!-- CITATION MODAL (ProQuest Style)                          -->
    <!-- ======================================================== -->
    <div id="citationModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl transition-all">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <div class="flex items-center gap-2.5">
                    <span class="text-xl">📝</span>
                    <h3 class="text-base md:text-lg font-bold text-gray-900">Generate Citation</h3>
                </div>
                <button onclick="closeCitationModal()" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition">
                    ✕
                </button>
            </div>

            <div class="mt-4">
                <p id="modalDocTitle" class="text-xs font-bold text-[#700000] truncate"></p>

                <!-- Format Selection Tabs -->
                <div class="mt-4 flex rounded-xl bg-slate-100 p-1 border border-gray-200">
                    <button id="tabAPA" onclick="selectCitationFormat('APA')" class="flex-1 py-1.5 text-xs font-bold rounded-lg bg-white text-[#700000] shadow-sm transition">
                        APA 7th
                    </button>
                    <button id="tabMLA" onclick="selectCitationFormat('MLA')" class="flex-1 py-1.5 text-xs font-bold rounded-lg text-gray-500 hover:text-gray-900 transition">
                        MLA 9th
                    </button>
                    <button id="tabChicago" onclick="selectCitationFormat('Chicago')" class="flex-1 py-1.5 text-xs font-bold rounded-lg text-gray-500 hover:text-gray-900 transition">
                        Chicago
                    </button>
                    <button id="tabIEEE" onclick="selectCitationFormat('IEEE')" class="flex-1 py-1.5 text-xs font-bold rounded-lg text-gray-500 hover:text-gray-900 transition">
                        IEEE
                    </button>
                </div>

                <!-- Citation Text Box -->
                <div class="mt-4 rounded-2xl border border-gray-200 bg-slate-50 p-4">
                    <p id="citationText" class="text-xs md:text-sm text-gray-800 leading-relaxed font-mono select-all break-words"></p>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <button onclick="closeCitationModal()" class="rounded-xl px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-100 transition">
                    Close
                </button>
                <button id="copyCitationBtn" onclick="copyCitationToClipboard()" class="rounded-xl bg-[#700000] px-5 py-2.5 text-xs font-bold text-[#FFD700] hover:bg-[#800000] transition shadow-md flex items-center gap-1.5">
                    <span id="copyBtnIcon">📋</span>
                    <span id="copyBtnText">Copy Citation</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- JAVASCRIPT LOGIC                                         -->
    <!-- ======================================================== -->
    <script>
        let allDocuments = [];
        let currentCitationDoc = null;
        let currentFormat = 'APA';

        const documentsList = document.getElementById('documentsList');
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');
        const docCountBadge = document.getElementById('docCountBadge');

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        }

        function formatAddedDate(dateString) {
            if (!dateString) return 'Recent';
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return 'Recent';
            return new Intl.DateTimeFormat('en-US', {
                month: 'short',
                year: 'numeric'
            }).format(date);
        }

        // Determine Department Colors & Badges Dynamically
        function getDepartmentTheme(title, author) {
            const lower = (title + ' ' + author).toLowerCase();
            if (lower.includes('detection') || lower.includes('web') || lower.includes('system') || lower.includes('platform') || lower.includes('app') || lower.includes('tracking')) {
                return {
                    name: 'Information Technology',
                    tag: 'BSIT',
                    bgGradient: 'from-blue-600 to-indigo-800',
                    badgeBg: 'bg-blue-50 text-blue-700 border-blue-200',
                    icon: '💻'
                };
            } else if (lower.includes('health') || lower.includes('patient') || lower.includes('nursing') || lower.includes('care') || lower.includes('medical')) {
                return {
                    name: 'Nursing & Health',
                    tag: 'BSN',
                    bgGradient: 'from-emerald-600 to-teal-800',
                    badgeBg: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    icon: '🩺'
                };
            } else if (lower.includes('business') || lower.includes('inventory') || lower.includes('market') || lower.includes('finance') || lower.includes('management')) {
                return {
                    name: 'Business Administration',
                    tag: 'BSBA',
                    bgGradient: 'from-amber-500 to-yellow-700',
                    badgeBg: 'bg-amber-50 text-amber-800 border-amber-200',
                    icon: '📊'
                };
            }
            return {
                name: 'Academic Research',
                tag: 'SAC Research',
                bgGradient: 'from-[#700000] to-[#500000]',
                badgeBg: 'bg-[#700000]/10 text-[#700000] border-[#700000]/20',
                icon: '📚'
            };
        }

        // Render Document Cards
        function renderDocuments(documents) {
            if (!Array.isArray(documents) || documents.length === 0) {
                documentsList.innerHTML = `
                    <div class="rounded-3xl border border-dashed border-gray-300 bg-white p-12 text-center">
                        <span class="text-4xl">📂</span>
                        <h3 class="mt-3 text-base font-bold text-gray-800">No theses found</h3>
                        <p class="mt-1 text-xs text-gray-500">Try searching for different keywords or authors.</p>
                    </div>
                `;
                docCountBadge.textContent = '0 Theses Found';
                return;
            }

            docCountBadge.textContent = `${documents.length} Theses Available`;

            documentsList.innerHTML = documents.map((doc, idx) => {
                const theme = getDepartmentTheme(doc.title, doc.author);
                const isLongAbstract = (doc.abstract || '').length > 200;
                const truncatedAbstract = isLongAbstract ? doc.abstract.substring(0, 200) + '...' : doc.abstract;

                return `
                    <article class="relative flex flex-col md:flex-row gap-5 rounded-3xl border border-gray-200 bg-white p-5 md:p-6 shadow-sm hover:shadow-md hover:border-[#700000]/30 transition">
                        
                        <!-- Dynamic Book Cover Spine -->
                        <div class="w-full md:w-28 h-28 md:h-auto rounded-2xl bg-gradient-to-br ${theme.bgGradient} flex flex-col items-center justify-center text-white p-3 shadow-inner shrink-0">
                            <span class="text-2xl">${theme.icon}</span>
                            <span class="mt-1 text-[10px] font-black uppercase tracking-wider text-center text-white/90 leading-tight">
                                ${theme.tag}
                            </span>
                            <span class="text-[9px] text-white/70 mt-1">${formatAddedDate(doc.created_at)}</span>
                        </div>

                        <!-- Main Content -->
                        <div class="flex-1 min-w-0">
                            
                            <!-- Badges -->
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-lg border px-2.5 py-0.5 text-[10px] font-bold ${theme.badgeBg}">
                                    ${theme.name}
                                </span>
                                <span class="rounded-lg bg-green-50 text-green-700 border border-green-200 px-2.5 py-0.5 text-[10px] font-bold">
                                    ✓ Full Text Available
                                </span>
                            </div>

                            <!-- Title -->
                            <h3 class="mt-2.5 text-base md:text-lg font-bold text-gray-900 hover:text-[#700000] transition">
                                ${escapeHtml(doc.title)}
                            </h3>

                            <!-- Author -->
                            <p class="mt-1 text-xs md:text-sm font-semibold text-[#700000]">
                                by ${escapeHtml(doc.author || 'Unknown Author')}
                            </p>

                            <!-- Two-Tier Abstract -->
                            <div class="mt-3 text-xs md:text-sm text-gray-600 leading-relaxed">
                                <span id="abstract-short-${doc.id}">${escapeHtml(truncatedAbstract)}</span>
                                ${isLongAbstract ? `
                                    <span id="abstract-full-${doc.id}" class="hidden">${escapeHtml(doc.abstract)}</span>
                                    <button type="button" onclick="toggleAbstract(${doc.id})" id="abstract-btn-${doc.id}" class="ml-1 text-xs font-bold text-[#700000] hover:underline">
                                        Read More
                                    </button>
                                ` : ''}
                            </div>

                            <!-- Bottom Action Bar (ProQuest Style) -->
                            <div class="mt-5 pt-4 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <!-- Cite Button -->
                                    <button
                                        type="button"
                                        onclick="openCitationModal(${idx})"
                                        class="rounded-xl border border-gray-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-gray-700 hover:bg-[#700000] hover:text-[#FFD700] hover:border-[#700000] transition flex items-center gap-1.5"
                                    >
                                        <span>📝</span> Cite
                                    </button>

                                    <!-- Ask AI About This Button -->
                                    <a
                                        href="/chat?q=${encodeURIComponent('Tell me about the thesis: ' + doc.title)}"
                                        class="rounded-xl border border-gray-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-gray-700 hover:bg-[#700000] hover:text-[#FFD700] hover:border-[#700000] transition flex items-center gap-1.5"
                                    >
                                        <span>🤖</span> Ask AI
                                    </a>
                                </div>

                                <!-- View Thesis PDF Button -->
                                <button
                                    type="button"
                                    onclick="openPdfViewer('/backend/documents/${doc.id}/view')"
                                    class="rounded-xl bg-[#700000] px-4 py-2 text-xs font-bold text-[#FFD700] hover:bg-[#800000] transition shadow-md flex items-center gap-1.5 shrink-0"
                                >
                                    <span>📄</span> View PDF
                                </button>
                            </div>

                        </div>
                    </article>
                `;
            }).join('');
        }

        // Toggle Abstract Read More / Less
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

        // Open PDF Viewer in new window
        function openPdfViewer(url) {
            const win = window.open(url, '_blank', 'noopener,noreferrer');
            if (!win) alert('Please allow pop-ups for this website to view the PDF.');
        }

        // Fetch Documents API
        async function fetchDocuments(search = '') {
            documentsList.innerHTML = `<p class="text-center text-sm text-gray-500 py-10">Searching documents...</p>`;
            try {
                const url = new URL('/backend/documents', window.location.origin);
                if (search && search.trim()) url.searchParams.set('search', search.trim());

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

        // Search Form Submit
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            fetchDocuments(searchInput.value);
        });

        // ========================================================
        // CITATION GENERATOR LOGIC
        // ========================================================
        function openCitationModal(index) {
            currentCitationDoc = allDocuments[index];
            if (!currentCitationDoc) return;

            document.getElementById('modalDocTitle').textContent = currentCitationDoc.title;
            selectCitationFormat('APA');
            document.getElementById('citationModal').classList.remove('hidden');
            document.getElementById('citationModal').classList.add('flex');
        }

        function closeCitationModal() {
            document.getElementById('citationModal').classList.add('hidden');
            document.getElementById('citationModal').classList.remove('flex');
        }

        function selectCitationFormat(format) {
            currentFormat = format;
            ['APA', 'MLA', 'Chicago', 'IEEE'].forEach(f => {
                const tab = document.getElementById(`tab${f}`);
                if (f === format) {
                    tab.className = "flex-1 py-1.5 text-xs font-bold rounded-lg bg-white text-[#700000] shadow-sm transition";
                } else {
                    tab.className = "flex-1 py-1.5 text-xs font-bold rounded-lg text-gray-500 hover:text-gray-900 transition";
                }
            });

            generateCitationText();
        }

        function generateCitationText() {
            if (!currentCitationDoc) return;

            const author = currentCitationDoc.author || 'Author, A.';
            const title = currentCitationDoc.title || 'Untitled Thesis';
            const year = currentCitationDoc.created_at ? new Date(currentCitationDoc.created_at).getFullYear() : new Date().getFullYear();

            let citation = '';

            if (currentFormat === 'APA') {
                citation = `${author} (${year}). ${title} (Undergraduate thesis). St. Anthony's College, San Jose, Antique.`;
            } else if (currentFormat === 'MLA') {
                citation = `${author}. "${title}." Undergraduate thesis, St. Anthony's College, ${year}.`;
            } else if (currentFormat === 'Chicago') {
                citation = `${author}. "${title}." Undergraduate thesis, St. Anthony's College, ${year}.`;
            } else if (currentFormat === 'IEEE') {
                citation = `${author}, "${title}," Undergraduate thesis, Dept. of Research, St. Anthony's College, San Jose, Antique, ${year}.`;
            }

            document.getElementById('citationText').textContent = citation;
            resetCopyButton();
        }

        function copyCitationToClipboard() {
            const text = document.getElementById('citationText').textContent;
            navigator.clipboard.writeText(text).then(() => {
                const btnText = document.getElementById('copyBtnText');
                const btnIcon = document.getElementById('copyBtnIcon');
                btnText.textContent = 'Copied!';
                btnIcon.textContent = '✓';
                setTimeout(resetCopyButton, 2000);
            });
        }

        function resetCopyButton() {
            document.getElementById('copyBtnText').textContent = 'Copy Citation';
            document.getElementById('copyBtnIcon').textContent = '📋';
        }

        // Initialize on load
        fetchDocuments();
    </script>
</body>

</html>