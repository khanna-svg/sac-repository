<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SAC Thesis System - Admin Upload</title>

    <!-- Supabase JS -->
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>

    <!-- PDF.js for automated full-text extraction -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    </script>

    <script>
        const supabaseClient = window.supabase.createClient(
            "{{ config('services.supabase.url', env('SUPABASE_URL')) }}",
            "{{ config('services.supabase.key', env('SUPABASE_PUBLISHABLE_KEY')) }}"
        );
    </script>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        #progressContainer {
            display: none;
        }

        #successModalCard {
            animation: modalPopIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes modalPopIn {
            from {
                opacity: 0;
                transform: scale(0.85);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>

<body class="min-h-screen bg-slate-50 text-slate-800">

    @include('partials.sidebar')

    <main class="md:ml-64 min-h-screen p-4 sm:p-6 md:p-10 transition-all pt-16 md:pt-10">
        <div class="mx-auto max-w-4xl space-y-6">

            <!-- HEADER -->
            <section>
                <h1 class="text-2xl md:text-3xl font-bold text-[#700000]">
                    Admin Management
                </h1>
                <p class="mt-1 md:mt-2 text-xs md:text-sm text-gray-500">
                    Upload and publish new thesis documents with automatic full-text indexing.
                </p>
            </section>

            <!-- ERROR ALERT MESSAGE CONTAINER -->
            <div id="uploadMessage" class="hidden"></div>

            <!-- UPLOAD CARD -->
            <section class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-6 shadow-sm">
                <h2 class="text-base md:text-lg font-bold text-[#700000]">
                    Upload Thesis Document
                </h2>
                <p class="mt-1 text-xs md:text-sm text-gray-500">
                    Enter metadata and select the thesis PDF file.
                </p>

                <!-- FORM -->
                <form id="uploadForm" class="mt-6 space-y-4 md:space-y-5">

                    <!-- TITLE -->
                    <div>
                        <label for="title" class="mb-2 block text-xs md:text-sm font-semibold text-gray-700">
                            THESIS TITLE
                        </label>
                        <input id="title" name="title" type="text" required class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-xs md:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000]">
                    </div>

                    <!-- AUTHOR -->
                    <div>
                        <label for="author" class="mb-2 block text-xs md:text-sm font-semibold text-gray-700">
                            AUTHOR(S)
                        </label>
                        <input id="author" name="author" type="text" required class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-xs md:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000]">
                    </div>

                    <!-- DEPARTMENT & COURSE GRID -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- DEPARTMENT -->
                        <div>
                            <label for="department" class="mb-2 block text-xs md:text-sm font-semibold text-gray-700">
                                DEPARTMENT
                            </label>
                            <select id="department" name="department" required onchange="handleDepartmentChange(this.value)" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-xs md:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000]">
                                <option value="" disabled selected>Select Department</option>
                                <option value="nursing">Nursing Department</option>
                                <option value="marine">Marine Engineering Department</option>
                                <option value="it">Information Technology Department</option>
                                <option value="hospitality">Hospitality Management</option>
                                <option value="education">Education Department</option>
                                <option value="criminology">Criminology Department</option>
                            </select>
                        </div>

                        <!-- COURSE -->
                        <div>
                            <label for="course_code" class="mb-2 block text-xs md:text-sm font-semibold text-gray-700">
                                COURSE
                            </label>
                            <select id="course_code" name="course_code" required class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-xs md:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000]">
                                <option value="" disabled selected>Select Course</option>
                                <option value="bsn">BS in Nursing (BSN)</option>
                                <option value="bsmare">BS in Marine Engineering (BSMarE)</option>
                                <option value="bsit">BS in Information Technology (BSIT)</option>
                                <option value="bshm">BS in Hospitality Management (BSHM)</option>
                                <option value="bsed">Bachelor of Secondary Education (BSED)</option>
                                <option value="bsc">BS in Criminology (BSC)</option>
                            </select>
                        </div>
                    </div>

                    <!-- ABSTRACT -->
                    <div>
                        <label for="abstract" class="mb-2 block text-xs md:text-sm font-semibold text-gray-700">
                            ABSTRACT
                        </label>
                        <textarea id="abstract" name="abstract" rows="4" required class="w-full resize-none rounded-xl border border-gray-300 bg-white px-4 py-3 text-xs md:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000]"></textarea>
                    </div>

                    <!-- PDF -->
                    <div>
                        <label for="pdf" class="mb-2 block text-xs md:text-sm font-semibold text-gray-700">
                            UPLOAD PDF FILE
                        </label>
                        <input id="pdf" name="pdf" type="file" accept=".pdf,application/pdf" required class="block w-full cursor-pointer rounded-xl border border-gray-300 bg-white text-xs md:text-sm text-gray-600 file:mr-4 file:border-0 file:bg-[#700000] file:px-4 file:py-3 file:text-[#FFD700] file:font-bold">
                        <p class="mt-2 text-xs text-gray-400">
                            Maximum file size: 50 MB (Full text will be automatically extracted and optimized)
                        </p>
                    </div>

                    <!-- PROGRESS -->
                    <div id="progressContainer" class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <div class="mb-2 flex justify-between text-xs text-gray-500">
                            <span id="progressText">Preparing upload...</span>
                            <span id="progressPercent">0%</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                            <div id="progressBar" class="h-full w-0 rounded-full bg-[#700000] transition-all duration-200"></div>
                        </div>
                    </div>

                    <!-- BUTTON -->
                    <button id="uploadButton" type="submit" class="w-full rounded-xl bg-[#700000] px-4 py-3 text-xs md:text-sm font-bold text-[#FFD700] shadow-md transition hover:bg-[#800000] disabled:cursor-not-allowed disabled:opacity-60">
                        Submit & Upload Thesis
                    </button>

                </form>
            </section>
        </div>
    </main>

    <!-- SUCCESS MODAL (MATCHING YOUR DESIGN) -->
    <div id="successModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div id="successModalCard" class="w-full max-w-sm rounded-3xl bg-white p-8 text-center shadow-2xl">
            <!-- Green Circle Checkmark -->
            <div class="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-green-50 ring-8 ring-green-100/70">
                <svg class="h-12 w-12 text-[#22c55e]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            
            <!-- Success Title -->
            <h3 class="text-2xl font-bold text-gray-800">Success</h3>
            
            <!-- Success Message -->
            <p id="successModalMessage" class="mt-3 text-sm text-gray-500 leading-relaxed">
                Thesis created successfully.
            </p>
            
            <!-- OK Button -->
            <button onclick="closeSuccessModal()" class="mt-8 w-28 rounded-xl bg-[#0284c7] py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-[#0369a1] focus:outline-none">
                OK
            </button>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const uploadForm = document.getElementById('uploadForm');
        const uploadButton = document.getElementById('uploadButton');
        const uploadMessage = document.getElementById('uploadMessage');
        const successModal = document.getElementById('successModal');
        const successModalMessage = document.getElementById('successModalMessage');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        const progressPercent = document.getElementById('progressPercent');

        const courseMapping = {
            'nursing': 'bsn',
            'marine': 'bsmare',
            'it': 'bsit',
            'hospitality': 'bshm',
            'education': 'bsed',
            'criminology': 'bsc'
        };

        function handleDepartmentChange(selectedDepartment) {
            const courseSelect = document.getElementById('course_code');
            if (courseMapping[selectedDepartment]) {
                courseSelect.value = courseMapping[selectedDepartment];
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }

        function openSuccessModal(message = 'Thesis uploaded and indexed successfully.') {
            successModalMessage.textContent = message;
            successModal.classList.remove('hidden');
            successModal.classList.add('flex');
            progressContainer.style.display = 'none';
        }

        function closeSuccessModal() {
            successModal.classList.add('hidden');
            successModal.classList.remove('flex');
        }

        function showErrorAlert(title = 'An error occurred', message = 'There was a problem with your request. Please try again.') {
            uploadMessage.innerHTML = `
                <div class="rounded-2xl bg-red-50 p-4 ring-1 ring-inset ring-red-200 shadow-sm">
                    <div class="flex items-start gap-x-4">
                        <div class="shrink-0">
                            <svg aria-hidden="true" class="size-6 text-red-600" viewBox="0 0 55 55" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M27.5 5C15.0736 5 5 15.0736 5 27.5C5 39.9265 15.0736 50 27.5 50C39.9265 50 50 39.9265 50 27.5C50 15.0736 39.9265 5 27.5 5ZM0 27.5C0 12.3122 12.3122 0 27.5 0C42.6879 0 55 12.3122 55 27.5C55 42.6879 42.6879 55 27.5 55C12.3122 55 0 42.6879 0 27.5ZM14.6211 14.6211C15.5975 13.6448 17.1804 13.6448 18.1567 14.6211L27.5 23.9645L36.8433 14.6211C37.8197 13.6448 39.4026 13.6448 40.3789 14.6211C41.3552 15.5974 41.3552 17.1803 40.3789 18.1567L31.0355 27.5L40.3789 36.8433C41.3552 37.8197 41.3552 39.4026 40.3789 40.3789C39.4026 41.3552 37.8197 41.3552 36.8433 40.3789L27.5 31.0355L18.1567 40.3789C17.1803 41.3552 15.5974 41.3552 14.6211 40.3789C13.6448 39.4026 13.6448 37.8197 14.6211 36.8433L23.9645 27.5L14.6211 18.1567C13.6448 17.1803 13.6448 15.5974 14.6211 14.6211Z" fill="currentColor"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-bold text-red-800">${escapeHtml(title)}</h3>
                            <div class="mt-1">
                                <p class="text-xs sm:text-sm text-red-700 leading-relaxed">${escapeHtml(message)}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            uploadMessage.classList.remove('hidden');
            progressContainer.style.display = 'none';
            uploadMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function updateProgress(percent, text) {
            const rounded = Math.round(percent);
            progressBar.style.width = `${rounded}%`;
            progressPercent.textContent = `${rounded}%`;
            if (text) {
                progressText.textContent = text;
            }
        }

        // Fast client-side PDF text extraction using PDF.js
        async function extractPdfText(file) {
            const arrayBuffer = await file.arrayBuffer();
            const pdf = await pdfjsLib.getDocument({
                data: arrayBuffer
            }).promise;
            const totalPages = pdf.numPages;
            const chunks = [];

            for (let pageNum = 1; pageNum <= totalPages; pageNum++) {
                updateProgress(
                    (pageNum / totalPages) * 30,
                    `Extracting text from page ${pageNum} of ${totalPages}...`
                );

                const page = await pdf.getPage(pageNum);
                const textContent = await page.getTextContent();
                const pageText = textContent.items
                    .map(item => item.str)
                    .join(' ')
                    .replace(/[ \t]+/g, ' ')
                    .replace(/[\r\n]+/g, '\n\n')
                    .trim();

                if (pageText.length > 0) {
                    chunks.push({
                        page: pageNum,
                        text: pageText
                    });
                }
            }

            return chunks;
        }

        uploadForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const file = document.getElementById('pdf').files[0];
            if (!file) return;

            uploadButton.disabled = true;
            uploadButton.textContent = 'Processing...';
            uploadMessage.classList.add('hidden');
            progressContainer.style.display = 'block';

            try {
                // 1. Extract text from all pages in browser (1-2 seconds)
                updateProgress(5, 'Extracting full text from PDF...');
                const extractedChunks = await extractPdfText(file);

                // 2. Get Supabase Signed Upload URL
                updateProgress(35, 'Requesting upload authorization...');
                const urlRes = await fetch('/backend/documents/upload-url', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        filename: file.name
                    })
                });
                const urlData = await urlRes.json();
                if (!urlRes.ok || urlData.error) {
                    throw new Error(urlData.message || 'Failed to prepare upload URL');
                }

                // 3. Upload PDF directly to Supabase Storage
                updateProgress(45, 'Uploading PDF to Supabase Storage...');
                const bucketName = "{{ config('services.supabase.bucket', env('SUPABASE_STORAGE_BUCKET', 'thesis')) }}";
                const {
                    error: uploadError
                } = await supabaseClient
                    .storage
                    .from(bucketName)
                    .uploadToSignedUrl(urlData.path, urlData.token, file, {
                        contentType: 'application/pdf'
                    });

                if (uploadError) throw uploadError;

                // 4. Save Document Metadata & all page chunks
                updateProgress(60, 'Saving thesis document and page chunks...');
                const thesisTitle = document.getElementById('title').value.trim();
                const metadataResponse = await fetch('/backend/documents/store-signed', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        title: thesisTitle,
                        author: document.getElementById('author').value.trim(),
                        department: document.getElementById('department').value,
                        course_code: document.getElementById('course_code').value,
                        abstract: document.getElementById('abstract').value.trim(),
                        file_path: urlData.path,
                        chunks: extractedChunks
                    })
                });

                const metadataData = await metadataResponse.json();
                if (!metadataResponse.ok || metadataData.error) {
                    throw new Error(metadataData.message || 'Failed to save metadata');
                }

                const docId = metadataData.document.id;

                // 5. Generate AI Embeddings in Batches of 20
                let remaining = 1;
                let totalProcessed = 0;
                const totalChunks = extractedChunks.length;

                while (remaining > 0) {
                    const embRes = await fetch(`/documents/${docId}/generate-embeddings`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });

                    const embData = await embRes.json();
                    if (!embRes.ok || embData.error) {
                        console.warn('Embedding warning:', embData.message);
                        break;
                    }

                    totalProcessed += (embData.processed || 0);
                    remaining = embData.remaining || 0;

                    const percent = 60 + Math.min(38, Math.round((totalProcessed / totalChunks) * 38));
                    updateProgress(
                        percent,
                        `Generating AI embeddings (${totalProcessed}/${totalChunks} pages)...`
                    );

                    if (embData.processed === 0 || remaining === 0) {
                        break;
                    }
                }

                updateProgress(100, 'Upload and indexing complete!');
                uploadForm.reset();

                // Open the Success Modal
                openSuccessModal(`"${thesisTitle}" has been uploaded and indexed successfully.`);

            } catch (err) {
                showErrorAlert('An error occurred', err.message || 'There was a problem uploading the thesis.');
            } finally {
                uploadButton.disabled = false;
                uploadButton.textContent = 'Submit & Upload Thesis';
            }
        });
    </script>
</body>

</html>