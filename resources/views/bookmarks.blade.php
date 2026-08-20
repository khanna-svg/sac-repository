<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Saved / Bookmarks - SAC Thesis Repository</title>
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
                        <h1 class="text-2xl md:text-3xl font-bold text-[#700000] flex items-center gap-2.5">
                            Saved / Bookmarks
                        </h1>
                        <p class="mt-1 text-xs md:text-sm text-gray-500">
                            Your saved thesis and capstone projects for quick reading and citation.
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span id="bookmarkCountBadge" class="rounded-xl bg-[#700000]/10 px-3.5 py-1.5 text-xs font-bold text-[#700000] border border-[#700000]/20">
                            0 Saved Theses
                        </span>
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

    <!-- CITATION MODAL (IEEE) -->
    <div id="citationModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl transition-all">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <div class="flex items-center gap-2.5">
                    <span class="text-xl">📝</span>
                    <h3 class="text-base md:text-lg font-bold text-gray-900">IEEE Citation</h3>
                </div>
                <button onclick="closeCitationModal()" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition">
                    ✕
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
                    <span id="copyBtnIcon">📋</span>
                    <span id="copyBtnText">Copy Citation</span>
                </button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const COVERS_BASE_URL = "{{ asset('images/covers') }}";

        let bookmarkedDocuments = [];
        let currentCitationDoc = null;

        const documentsList = document.getElementById('documentsList');
        const bookmarkCountBadge = document.getElementById('bookmarkCountBadge');

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
                }
            } catch (err) {
                console.error('Failed to toggle bookmark:', err);
            }
        }

        function renderDocuments(documents) {
            if (!Array.isArray(documents) || documents.length === 0) {
                documentsList.innerHTML = `
                    <div class="rounded-3xl border border-dashed border-gray-300 bg-white p-12 text-center">
                        <span class="text-4xl">🔖</span>
                        <h3 class="mt-3 text-base font-bold text-gray-800">No saved thesis</h3>
                        <p class="mt-1 text-xs text-gray-500">When you bookmark a thesis in the repository, it will appear here for easy access.</p>
                        <a href="/documents" class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-[#700000] px-4 py-2.5 text-xs font-bold text-[#FFD700] hover:bg-[#800000] shadow-sm transition">
                            Explore Thesis Repository →
                        </a>
                    </div>
                `;
                bookmarkCountBadge.textContent = '0 Saved Theses';
                return;
            }

            bookmarkCountBadge.textContent = `${documents.length} Saved Theses`;

            documentsList.innerHTML = documents.map((doc, idx) => {
                const details = getDepartmentDetails(doc.department, doc.course_code, doc.title);
                const isLongAbstract = (doc.abstract || '').length > 200;
                const truncatedAbstract = isLongAbstract ? doc.abstract.substring(0, 200) + '...' : doc.abstract;

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
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-lg border px-2.5 py-0.5 text-[10px] font-bold ${details.badgeBg}">
                                    ${details.name}
                                </span>
                                <span class="rounded-lg bg-green-50 text-green-700 border border-green-200 px-2.5 py-0.5 text-[10px] font-bold">
                                    ✓ Full Text Available
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
                                <p>${escapeHtml(truncatedAbstract)}</p>
                            </div>

                            <div class="mt-5 pt-4 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        onclick="openCitationModal(${idx})"
                                        class="rounded-xl border border-gray-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-gray-700 hover:bg-[#700000] hover:text-[#FFD700] hover:border-[#700000] transition flex items-center gap-1.5">
                                        <span>📝</span> Cite (IEEE)
                                    </button>

                                    <a
                                        href="/chat?q=${encodeURIComponent('Tell me about the thesis: ' + doc.title)}"
                                        class="rounded-xl border border-gray-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-gray-700 hover:bg-[#700000] hover:text-[#FFD700] hover:border-[#700000] transition flex items-center gap-1.5">
                                        <span>🤖</span> Ask AI
                                    </a>

                                    <a
                                        href="/backend/documents/${doc.id}/view"
                                        target="_blank"
                                        class="rounded-xl border border-gray-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-gray-700 hover:bg-gray-100 transition flex items-center gap-1.5">
                                        <span>📄</span> View PDF
                                    </a>
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
                        'Accept': 'application/json'
                    }
                });
                if (!res.ok) throw new Error('Failed to load');
                bookmarkedDocuments = await res.json();
                renderDocuments(bookmarkedDocuments);
            } catch (err) {
                // If anything fails, gracefully show the empty state "No saved thesis"
                renderDocuments([]);
            }
        }

        function openCitationModal(index) {
            currentCitationDoc = bookmarkedDocuments[index];
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
                btnIcon.textContent = '✓';
                setTimeout(resetCopyButton, 2000);
            });
        }

        function resetCopyButton() {
            document.getElementById('copyBtnText').textContent = 'Copy Citation';
            document.getElementById('copyBtnIcon').textContent = '📋';
        }

        fetchBookmarks();
    </script>
</body>

</html>