<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SAC Thesis System - Documents</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gray-950 text-gray-100">
    @include('partials.sidebar')

    <main class="ml-64 min-h-screen p-6 md:p-10">
        <div class="mx-auto max-w-5xl">
            <section class="mb-10">
                <h1 class="text-3xl font-bold">Thesis Documents</h1>
                <p class="mt-2 text-gray-400">
                    Upload, search, and view approved thesis documents.
                </p>
            </section>

            <section class="rounded-2xl border border-gray-700 bg-gray-900 p-6 shadow-xl">
                <h2 class="text-lg font-bold">Upload Thesis Document</h2>
                <p class="mt-1 text-sm text-gray-400">
                    Enter metadata and select the thesis PDF file.
                </p>

                <div id="uploadMessage" class="mt-4 hidden rounded-lg p-3 text-sm"></div>

                <form id="uploadForm" class="mt-6 space-y-5">
                    <div>
                        <label for="title" class="mb-2 block text-sm font-medium">
                            THESIS TITLE
                        </label>
                        <input
                            id="title"
                            name="title"
                            type="text"
                            required
                            placeholder="e.g., A Mobile-Based Medication Adherence System"
                            class="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-sm outline-none focus:border-indigo-500"
                        >
                    </div>

                    <div>
                        <label for="authors" class="mb-2 block text-sm font-medium">
                            AUTHOR(S)
                        </label>
                        <input
                            id="authors"
                            name="authors"
                            type="text"
                            required
                            placeholder="e.g., Juan Dela Cruz, Maria Santos"
                            class="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-sm outline-none focus:border-indigo-500"
                        >
                    </div>

                    <div>
                        <label for="abstract" class="mb-2 block text-sm font-medium">
                            ABSTRACT
                        </label>
                        <textarea
                            id="abstract"
                            name="abstract"
                            rows="5"
                            required
                            placeholder="Paste thesis abstract here..."
                            class="w-full resize-none rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-sm outline-none focus:border-indigo-500"
                        ></textarea>
                    </div>

                    <div>
                        <label for="pdf" class="mb-2 block text-sm font-medium">
                            UPLOAD PDF FILE
                        </label>
                        <input
                            id="pdf"
                            name="pdf"
                            type="file"
                            accept=".pdf,application/pdf"
                            required
                            class="block w-full cursor-pointer rounded-lg border border-gray-700 bg-gray-950 text-sm file:mr-4 file:cursor-pointer file:border-0 file:bg-indigo-600 file:px-4 file:py-3 file:text-sm file:font-semibold file:text-white hover:file:bg-indigo-500"
                        >
                    </div>

                    <button
                        id="uploadButton"
                        type="submit"
                        class="w-full rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        Submit & Upload Thesis
                    </button>
                </form>
            </section>

            <section class="mt-10">
                <h2 class="text-xl font-bold">Repository Search</h2>
                <p class="mt-1 text-sm text-gray-400">
                    Search stored theses by title, author, or keywords.
                </p>

                <form id="searchForm" class="mt-4 flex gap-3">
                    <input
                        id="searchInput"
                        type="search"
                        placeholder="Search topics, authors, or keywords..."
                        class="min-w-0 flex-1 rounded-lg border border-gray-700 bg-gray-900 px-4 py-3 text-sm outline-none focus:border-indigo-500"
                    >
                    <button
                        type="submit"
                        class="rounded-lg bg-indigo-600 px-6 py-3 text-sm font-semibold hover:bg-indigo-500"
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

    <div id="pdfModal" class="fixed inset-0 z-50 hidden bg-black/80 p-4">
        <div class="mx-auto flex h-full max-w-6xl flex-col rounded-xl bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-700 p-4">
                <h2 id="pdfTitle" class="font-semibold">Thesis Viewer</h2>
                <button
                    type="button"
                    onclick="closePdfViewer()"
                    class="rounded-lg bg-gray-700 px-4 py-2 text-sm hover:bg-gray-600"
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
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute('content');

        const uploadForm = document.getElementById('uploadForm');
        const uploadButton = document.getElementById('uploadButton');
        const uploadMessage = document.getElementById('uploadMessage');
        const documentsList = document.getElementById('documentsList');
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');

        function showUploadMessage(message, type = 'success') {
            uploadMessage.textContent = message;
            uploadMessage.className = type === 'success'
                ? 'mt-4 rounded-lg border border-green-700 bg-green-950/40 p-3 text-sm text-green-300'
                : 'mt-4 rounded-lg border border-red-700 bg-red-950/40 p-3 text-sm text-red-300';
        }

        function escapeHtml(value) {
            const element = document.createElement('div');
            element.textContent = value ?? '';
            return element.innerHTML;
        }

        async function fetchDocuments(search = '') {
            documentsList.innerHTML = `
                <p class="text-center text-sm text-gray-400">Loading documents...</p>
            `;

            try {
                const url = new URL('/backend/documents', window.location.origin);

                if (search.trim()) {
                    url.searchParams.set('search', search.trim());
                }

                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const contentType = response.headers.get('content-type') || '';

                if (!response.ok || !contentType.includes('application/json')) {
                    throw new Error(`Could not load documents (${response.status}).`);
                }

                const documents = await response.json();

                if (!Array.isArray(documents) || documents.length === 0) {
                    documentsList.innerHTML = `
                        <p class="py-8 text-center text-sm text-gray-400">
                            No documents found.
                        </p>
                    `;
                    return;
                }

                documentsList.innerHTML = documents.map((doc) => `
                    <article class="flex flex-col gap-4 rounded-xl border border-gray-700 bg-gray-900 p-5 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold">
                                ${escapeHtml(doc.title)}
                            </h3>
                            <p class="mt-1 text-sm text-indigo-300">
                                ${escapeHtml(doc.authors)}
                            </p>
                            <p class="mt-3 text-sm leading-6 text-gray-400">
                                ${escapeHtml(doc.abstract)}
                            </p>
                        </div>

                        <button
                            type="button"
                            onclick="openPdfViewer('/backend/documents/${doc.id}/view', '${escapeHtml(doc.title)}')"
                            class="shrink-0 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-500"
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

                showUploadMessage('Thesis uploaded successfully.');
                uploadForm.reset();
                await fetchDocuments(searchInput.value);
            } catch (error) {
                console.error('Upload failed:', error);
                showUploadMessage(error.message || 'Upload failed. Check the Vercel Runtime Logs.', 'error');
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