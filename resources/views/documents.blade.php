<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>SAC Thesis System - Documents</title>

    <script src="https://cdn.tailwindcss.com"></script>

</head>


<body class="min-h-screen bg-slate-50 text-slate-800">

    @include('partials.sidebar')


    <main
        class="md:ml-64 min-h-screen p-4 sm:p-6 md:p-10 transition-all pt-16 md:pt-10">

        <div class="mx-auto max-w-5xl">

            <!-- HEADER -->

            <section class="mb-6 md:mb-10">

                <h1
                    class="text-2xl md:text-3xl font-bold text-[#700000]">
                    Thesis Repository
                </h1>

                <p
                    class="mt-1 md:mt-2 text-xs md:text-sm text-gray-500">
                    Search and view approved thesis documents.
                </p>

            </section>


            <!-- SEARCH -->

            <section>

                <form
                    id="searchForm"
                    class="flex flex-col sm:flex-row gap-2.5 sm:gap-3">

                    <input
                        id="searchInput"
                        type="search"
                        placeholder="Search topics, authors, or keywords..."
                        class="w-full min-w-0 flex-1 rounded-xl border border-gray-300 bg-white px-3.5 md:px-4 py-2.5 md:py-3 text-xs md:text-sm text-gray-800 placeholder-gray-400 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000] shadow-sm">

                    <button
                        type="submit"
                        class="w-full sm:w-auto rounded-xl bg-[#700000] px-6 py-2.5 md:py-3 text-xs md:text-sm font-bold text-[#FFD700] hover:bg-[#800000] transition shadow-md">
                        Search
                    </button>

                </form>


                <!-- DOCUMENT LIST -->

                <div
                    id="documentsList"
                    class="mt-6 space-y-4">

                    <p class="text-center text-sm text-gray-500">
                        Loading documents...
                    </p>

                </div>

            </section>

        </div>

    </main>


    <!--
        NO IFRAME HERE.

        The PDF is opened directly in a new browser tab.
        Laravel generates a temporary Supabase signed URL
        and redirects the browser to it.
    -->


    <script>
        const documentsList =
            document.getElementById('documentsList');

        const searchForm =
            document.getElementById('searchForm');

        const searchInput =
            document.getElementById('searchInput');


        // ========================================
        // ESCAPE HTML
        // ========================================

        function escapeHtml(value) {
            const element =
                document.createElement('div');

            element.textContent =
                value ?? '';

            return element.innerHTML;
        }

        function formatAddedDate(dateString) {
            if (!dateString) return 'Unknown date';

            const date = new Date(dateString);

            if (isNaN(date.getTime())) {
                return 'Unknown date';
            }

            return new Intl.DateTimeFormat('en-US', {
                month: 'long',
                day: 'numeric',
                year: 'numeric'
            }).format(date);
        }


        // ========================================
        // OPEN PDF
        // ========================================

        function openPdfViewer(url) {
            /*
             * Open the Laravel endpoint in a new tab.
             *
             * Laravel will:
             *
             * 1. Check the logged-in user.
             * 2. Check the document.
             * 3. Generate a temporary Supabase signed URL.
             * 4. Redirect the browser directly to Supabase.
             *
             * The PDF therefore NEVER passes through Vercel.
             */

            const newWindow =
                window.open(
                    url,
                    '_blank',
                    'noopener,noreferrer'
                );


            if (!newWindow) {

                alert(
                    'Please allow pop-ups for this website to view the thesis.'
                );

            }
        }


        // ========================================
        // FETCH DOCUMENTS
        // ========================================

        async function fetchDocuments(search = '') {

            documentsList.innerHTML = `
                <p class="text-center text-sm text-gray-500">
                    Loading documents...
                </p>
            `;


            try {

                const url =
                    new URL(
                        '/backend/documents',
                        window.location.origin
                    );


                if (
                    search &&
                    search.trim()
                ) {

                    url.searchParams.set(
                        'search',
                        search.trim()
                    );

                }


                const response =
                    await fetch(
                        url.toString(), {
                            method: 'GET',

                            headers: {
                                'Accept': 'application/json'
                            },

                            credentials: 'same-origin'
                        }
                    );


                if (!response.ok) {

                    throw new Error(
                        'Failed to load documents.'
                    );

                }


                const documents =
                    await response.json();


                if (
                    !Array.isArray(documents) ||
                    documents.length === 0
                ) {

                    documentsList.innerHTML = `
                        <p class="py-8 text-center text-sm text-gray-500">
                            No documents found.
                        </p>
                    `;

                    return;

                }


                documentsList.innerHTML = documents.map((doc) => `
                    <article class="flex flex-col gap-3 sm:gap-4 rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 sm:flex-row sm:items-start sm:justify-between shadow-sm hover:shadow-md hover:border-[#700000]/40 transition">

                        <div class="flex-1 min-w-0">

                            <h3 class="text-base sm:text-lg font-bold text-gray-900">
                                ${escapeHtml(doc.title)}
                            </h3>

                            <p class="mt-1 text-xs sm:text-sm font-bold text-[#700000]">
                                ${escapeHtml(doc.author || 'Unknown Author')}
                            </p>

                            <p class="mt-2.5 text-xs sm:text-sm leading-relaxed text-gray-600">
                                ${escapeHtml(doc.abstract)}
                            </p>

                            <!-- ADDED ON -->
                            <p class="mt-3 text-xs text-gray-400">
                                Added on:
                                <span class="font-medium text-gray-500">
                                    ${formatAddedDate(doc.created_at)}
                                </span>
                            </p>

                        </div>

                        <button
                            type="button"
                            onclick="openPdfViewer('/backend/documents/${doc.id}/view', '${escapeHtml(doc.title)}')"
                            class="w-full sm:w-auto rounded-xl bg-[#700000] px-4 py-2.5 text-xs font-bold text-[#FFD700] hover:bg-[#800000] transition shadow-sm shrink-0"
                        >
                            View Thesis
                        </button>

                    </article>
                `).join('');


                // ========================================
                // BUTTON EVENTS
                // ========================================

                document
                    .querySelectorAll('.view-pdf-button')
                    .forEach(button => {

                        button.addEventListener(
                            'click',
                            function() {

                                const url =
                                    this.dataset.url;

                                openPdfViewer(url);

                            }
                        );

                    });


            } catch (error) {

                console.error(
                    'Document loading error:',
                    error
                );


                documentsList.innerHTML = `
                    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-center">

                        <p class="text-sm text-red-700">
                            Could not load documents.
                        </p>

                    </div>
                `;

            }

        }


        // ========================================
        // SEARCH
        // ========================================

        searchForm.addEventListener(
            'submit',
            function(event) {

                event.preventDefault();

                fetchDocuments(
                    searchInput.value
                );

            }
        );


        // ========================================
        // LOAD DOCUMENTS
        // ========================================

        fetchDocuments();
    </script>


</body>

</html>