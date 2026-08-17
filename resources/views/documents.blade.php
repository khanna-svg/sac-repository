<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAC Thesis System - Documents</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    @include('partials.sidebar')

    <main class="md:ml-64 min-h-screen p-4 sm:p-6 md:p-10 transition-all pt-16 md:pt-10">
        <div class="mx-auto max-w-5xl">
            <section class="mb-6 md:mb-10">
                <h1 class="text-2xl md:text-3xl font-bold text-[#700000]">Thesis Repository</h1>
                <p class="mt-1 md:mt-2 text-xs md:text-sm text-gray-500">
                    Search and view approved thesis documents.
                </p>
            </section>

            <section>
                <form id="searchForm" class="flex flex-col sm:flex-row gap-2.5 sm:gap-3">
                    <input id="searchInput" type="search" placeholder="Search topics, authors, or keywords..." class="w-full min-w-0 flex-1 rounded-xl border border-gray-300 bg-white px-3.5 md:px-4 py-2.5 md:py-3 text-xs md:text-sm text-gray-800 placeholder-gray-400 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000] shadow-sm">
                    <button type="submit" class="w-full sm:w-auto rounded-xl bg-[#700000] px-6 py-2.5 md:py-3 text-xs md:text-sm font-bold text-[#FFD700] hover:bg-[#800000] transition shadow-md">
                        Search
                    </button>
                </form>

                <div id="documentsList" class="mt-6 space-y-4">
                    <p class="text-center text-sm text-gray-500">Loading documents...</p>
                </div>
            </section>
        </div>
    </main>

    <!-- PDF Viewer Modal -->
    <div id="pdfModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 p-2 sm:p-4 backdrop-blur-sm">
        <div class="mx-auto flex h-full max-w-6xl flex-col rounded-2xl bg-white border border-gray-200 shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-200 bg-[#700000] px-4 py-3 sm:px-5 sm:py-4 rounded-t-2xl">
                <h2 id="pdfTitle" class="text-sm sm:text-base font-bold text-[#FFD700] truncate pr-2">Thesis Viewer</h2>
                <button type="button" onclick="closePdfViewer()" class="rounded-lg bg-[#500000] px-3 py-1.5 text-xs sm:text-sm font-semibold text-white hover:bg-red-800 shrink-0 transition">Close</button>
            </div>
            <iframe id="pdfFrame" class="min-h-0 flex-1 rounded-b-2xl bg-white" title="Thesis PDF viewer"></iframe>
        </div>
    </div>

    <script>
        const documentsList = document.getElementById('documentsList');
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');

        function escapeHtml(value) {
            const element = document.createElement('div');
            element.textContent = value ?? '';
            return element.innerHTML;
        }

        async function fetchDocuments(search = '') {
            documentsList.innerHTML = `<p class="text-center text-sm text-gray-500">Loading documents...</p>`;
            try {
                const url = new URL('/backend/documents', window.location.origin);
                if (search && search.trim()) url.searchParams.set('search', search.trim().toLowerCase());

                const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const documents = await response.json();

                if (!Array.isArray(documents) || documents.length === 0) {
                    documentsList.innerHTML = `<p class="py-8 text-center text-sm text-gray-500">No documents found.</p>`;
                    return;
                }

                documentsList.innerHTML = documents.map((doc) => `
                    <article class="flex flex-col gap-3 sm:gap-4 rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 sm:flex-row sm:items-start sm:justify-between shadow-sm hover:shadow-md hover:border-[#700000]/40 transition">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base sm:text-lg font-bold text-gray-900 truncate">${escapeHtml(doc.title)}</h3>
                            <p class="mt-1 text-xs sm:text-sm font-bold text-[#700000]">${escapeHtml(doc.author || 'Unknown Author')}</p>
                            <p class="mt-2.5 text-xs sm:text-sm leading-relaxed text-gray-600">${escapeHtml(doc.abstract)}</p>
                        </div>
                        <button type="button" onclick="openPdfViewer('/backend/documents/${doc.id}/view', '${escapeHtml(doc.title)}')" class="w-full sm:w-auto rounded-xl bg-[#700000] px-4 py-2.5 text-xs font-bold text-[#FFD700] hover:bg-[#800000] transition shadow-sm shrink-0">
                            View Thesis
                        </button>
                    </article>
                `).join('');
            } catch (error) {
                documentsList.innerHTML = `<p class="py-8 text-center text-sm text-red-600">Could not load documents.</p>`;
            }
        }

        searchForm.addEventListener('submit', (e) => { e.preventDefault(); fetchDocuments(searchInput.value); });
        function openPdfViewer(url, title) { document.getElementById('pdfTitle').textContent = title; document.getElementById('pdfFrame').src = url; document.getElementById('pdfModal').classList.remove('hidden'); }
        function closePdfViewer() { document.getElementById('pdfFrame').src = ''; document.getElementById('pdfModal').classList.add('hidden'); }

        fetchDocuments();
    </script>
</body>
</html>