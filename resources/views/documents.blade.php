<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SAC Thesis System - Documents</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes drawCheck {
            0% { stroke-dashoffset: 100; }
            100% { stroke-dashoffset: 0; }
        }
        .animate-check {
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
            animation: drawCheck 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards 0.2s;
        }
        .no-select {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
    </style>
</head>

<body class="min-h-screen bg-gray-950 text-gray-100">
    @include('partials.sidebar')

    <!-- Updated ml-64 to md:ml-64 and responsive padding -->
    <main class="md:ml-64 min-h-screen p-4 sm:p-6 md:p-10 transition-all">
        <div class="mx-auto max-w-5xl">
            <section class="mb-6 md:mb-10">
                <h1 class="text-2xl md:text-3xl font-bold">Thesis Documents</h1>
                <p class="mt-1 md:mt-2 text-xs md:text-sm text-gray-400">
                    Upload, search, and view approved thesis documents.
                </p>
            </section>

            <section class="rounded-2xl border border-gray-700 bg-gray-900 p-4 sm:p-6 shadow-xl">
                <h2 class="text-base md:text-lg font-bold">Upload Thesis Document</h2>
                <p class="mt-1 text-xs md:text-sm text-gray-400">
                    Enter metadata and select the thesis PDF file.
                </p>

                <div id="uploadMessage" class="mt-4 hidden rounded-lg p-3 text-sm"></div>

                <form id="uploadForm" class="mt-6 space-y-4 md:space-y-5">
                    <div>
                        <label for="title" class="mb-2 block text-xs md:text-sm font-medium">
                            THESIS TITLE
                        </label>
                        <input
                            id="title"
                            name="title"
                            type="text"
                            required
                            placeholder="e.g., A Mobile-Based Medication Adherence System"
                            class="w-full rounded-lg border border-gray-700 bg-gray-950 px-3.5 md:px-4 py-2.5 md:py-3 text-xs md:text-sm outline-none focus:border-indigo-500"
                        >
                    </div>

                    <div>
                        <label for="author" class="mb-2 block text-xs md:text-sm font-medium">
                            AUTHOR(S)
                        </label>
                        <input
                            id="author"
                            name="author"
                            type="text"
                            required
                            placeholder="e.g., Juan Dela Cruz, Maria Santos"
                            class="w-full rounded-lg border border-gray-700 bg-gray-950 px-3.5 md:px-4 py-2.5 md:py-3 text-xs md:text-sm outline-none focus:border-indigo-500"
                        >
                    </div>

                    <div>
                        <label for="abstract" class="mb-2 block text-xs md:text-sm font-medium">
                            ABSTRACT
                        </label>
                        <textarea
                            id="abstract"
                            name="abstract"
                            rows="4"
                            required
                            placeholder="Paste thesis abstract here..."
                            class="w-full resize-none rounded-lg border border-gray-700 bg-gray-950 px-3.5 md:px-4 py-2.5 md:py-3 text-xs md:text-sm outline-none focus:border-indigo-500"
                        ></textarea>
                    </div>

                    <div>
                        <label for="pdf" class="mb-2 block text-xs md:text-sm font-medium">
                            UPLOAD PDF FILE
                        </label>
                        <input
                            id="pdf"
                            name="pdf"
                            type="file"
                            accept=".pdf,application/pdf"
                            required
                            class="block w-full cursor-pointer rounded-lg border border-gray-700 bg-gray-950 text-xs md:text-sm file:mr-3 file:md:mr-4 file:cursor-pointer file:border-0 file:bg-indigo-600 file:px-3 file:md:px-4 file:py-2.5 file:md:py-3 file:text-xs file:md:text-sm file:font-semibold file:text-white hover:file:bg-indigo-500"
                        >
                    </div>

                    <button
                        id="uploadButton"
                        type="submit"
                        class="w-full rounded-lg bg-indigo-600 px-4 py-3 text-xs md:text-sm font-semibold text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        Submit & Upload Thesis
                    </button>
                </form>
            </section>

            <section class="mt-8 md:mt-10">
                <h2 class="text-lg md:text-xl font-bold">Repository Search</h2>
                <p class="mt-1 text-xs md:text-sm text-gray-400">
                    Search stored theses by title, author, or keywords.
                </p>

                <!-- Responsive search input stack on mobile -->
                <form id="searchForm" class="mt-4 flex flex-col sm:flex-row gap-2.5 sm:gap-3">
                    <input
                        id="searchInput"
                        type="search"
                        placeholder="Search topics, authors, or keywords..."
                        class="w-full min-w-0 flex-1 rounded-lg border border-gray-700 bg-gray-900 px-3.5 md:px-4 py-2.5 md:py-3 text-xs md:text-sm outline-none focus:border-indigo-500"
                    >
                    <button
                        type="submit"
                        class="w-full sm:w-auto rounded-lg bg-indigo-600 px-5 py-2.5 md:py-3 text-xs md:text-sm font-semibold hover:bg-indigo-500 transition"
                    >
                        Search
                    </button>
                </form>

                <div id="documentsList" class="mt-6 space-y-4">
                    <p class="text-center text-sm text-gray-400">Loading documents...</p>
                </div>
            </section>
        </div>
    </main>

    <!-- Success Modal Pop-up -->
    <div id="successModal" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-black/70 p-4 transition-opacity">
        <div class="w-full max-w-sm rounded-2xl border border-gray-800 bg-gray-900 p-6 text-center shadow-2xl">
            <div class="mx-auto mb-4 flex h-14 w-14 md:h-16 md:w-16 items-center justify-center rounded-full bg-emerald-950/60 ring-8 ring-emerald-900/30">
                <svg class="h-8 w-8 md:h-10 md:w-10 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path class="animate-check" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            
            <h3 class="text-base md:text-lg font-bold text-white">Upload Successful!</h3>
            <p class="mt-1 md:mt-2 text-xs md:text-sm text-gray-300">Thesis Uploaded to the Library</p>
            
            <button
                type="button"
                onclick="closeSuccessModal()"
                class="mt-5 md:mt-6 w-full rounded-xl bg-indigo-600 py-2.5 text-xs md:text-sm font-medium text-white hover:bg-indigo-500 transition"
            >
                Done
            </button>
        </div>
    </div>

    <!-- PDF Viewer Modal -->
    <div id="pdfModal" class="fixed inset-0 z-50 hidden bg-black/80 p-2 sm:p-4">
        <div class="mx-auto flex h-full max-w-6xl flex-col rounded-xl bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-700 p-3 sm:p-4">
                <h2 id="pdfTitle" class="text-sm sm:text-base font-semibold truncate pr-2">Thesis Viewer</h2>
                <button
                    type="button"
                    onclick="closePdfViewer()"
                    class="rounded-lg bg-gray-700 px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm hover:bg-gray-600 shrink-0"
                >
                    Close
                </button>
            </div>

            <iframe
                id="pdfFrame"
                class="min-h-0 flex-1 rounded-b-xl bg-white"
                title="Thesis PDF viewer"
            ></iframe>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const uploadForm = document.getElementById('uploadForm');
        const uploadButton = document.getElementById('uploadButton');
        const uploadMessage = document.getElementById('uploadMessage');
        const documentsList = document.getElementById('documentsList');
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');

        function showUploadError(message) {
            uploadMessage.textContent = message;
            uploadMessage.className = 'mt-4 rounded-lg border border-red-700 bg-red-950/40 p-3 text-sm text-red-300';
        }

        function showSuccessModal() {
            document.getElementById('successModal').classList.remove('hidden');
        }

        function closeSuccessModal() {
            document.getElementById('successModal').classList.add('hidden');
        }

        function escapeHtml(value) {
            const element = document.createElement('div');
            element.textContent = value ?? '';
            return element.innerHTML;
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateString).toLocaleDateString(undefined, options);
        }

        async function fetchDocuments(search = '') {
            documentsList.innerHTML = `<p class="text-center text-sm text-gray-400">Loading documents...</p>`;

            try {
                const url = new URL('/backend/documents', window.location.origin);
                if (search && search.trim()) {
                    url.searchParams.set('search', search.trim().toLowerCase());
                }

                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json' }
                });

                const contentType = response.headers.get('content-type') || '';
                if (!response.ok || !contentType.includes('application/json')) {
                    throw new Error(`Could not load documents (${response.status}).`);
                }

                const documents = await response.json();

                if (!Array.isArray(documents) || documents.length === 0) {
                    documentsList.innerHTML = `
                        <p class="py-8 text-center text-sm text-gray-400">No documents found.</p>
                    `;
                    return;
                }

                documentsList.innerHTML = documents.map((doc) => `
                    <article class="flex flex-col gap-3 sm:gap-4 rounded-xl border border-gray-700 bg-gray-900 p-4 sm:p-5 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base sm:text-lg font-semibold text-white truncate">
                                ${escapeHtml(doc.title)}
                            </h3>

                            <p class="mt-1 text-xs sm:text-sm font-medium text-indigo-300 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span class="truncate">${escapeHtml(doc.author || doc.authors || 'Unknown Author')}</span>
                            </p>

                            <p class="mt-1 text-[11px] sm:text-xs text-gray-400 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Uploaded on ${formatDate(doc.created_at || doc.created_date)}
                            </p>

                            <p class="mt-2.5 text-xs sm:text-sm leading-relaxed text-gray-400 line-clamp-3 sm:line-clamp-none">
                                ${escapeHtml(doc.abstract)}
                            </p>
                        </div>

                        <button
                            type="button"
                            onclick="openPdfViewer('/backend/documents/${doc.id}/view', '${escapeHtml(doc.title)}')"
                            class="w-full sm:w-auto shrink-0 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-500 transition text-center"
                        >
                            View Thesis
                        </button>
                    </article>
                `).join('');
            } catch (error) {
                console.error('Error loading documents:', error);
                documentsList.innerHTML = `
                    <p class="py-8 text-center text-sm text-red-300">
                        Could not load documents. Please refresh and try again.
                    </p>
                `;
            }
        }

        uploadForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(uploadForm);

            uploadButton.disabled = true;
            uploadButton.textContent = 'Uploading...';
            uploadMessage.className = 'mt-4 hidden';

            try {
                const response = await fetch('/backend/documents/upload', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });

                const data = await response.json();

                if (!response.ok) {
                    const validationErrors = data.errors
                        ? Object.values(data.errors).flat().join(' ')
                        : (data.message || 'Upload failed.');

                    throw new Error(validationErrors);
                }

                showSuccessModal();
                uploadForm.reset();
                await fetchDocuments(searchInput.value);
            } catch (error) {
                console.error('Upload failed:', error);
                showUploadError(error.message || 'Upload failed. Check logs.');
            } finally {
                uploadButton.disabled = false;
                uploadButton.textContent = 'Submit & Upload Thesis';
            }
        });

        searchForm.addEventListener('submit', (event) => {
            event.preventDefault();
            fetchDocuments(searchInput.value);
        });

        function openPdfViewer(url, title) {
            document.getElementById('pdfTitle').textContent = title || 'Thesis Viewer';
            document.getElementById('pdfFrame').src = url;
            document.getElementById('pdfModal').classList.remove('hidden');
        }

        function closePdfViewer() {
            document.getElementById('pdfFrame').src = '';
            document.getElementById('pdfModal').classList.add('hidden');
        }

        fetchDocuments();
    </script>
</body>
</html>