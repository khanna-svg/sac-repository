<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Upload & Index Thesis - SAC Admin</title>

    <!-- Supabase JS -->
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>

    <!-- PDF.js for automated text extraction -->
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
    <link rel="icon" href="https://sac.campus-erp.com/Student/images/sac.png" type="image/png">

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
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>

<body class="min-h-screen bg-slate-50 text-slate-800 font-sans">

    @include('partials.sidebar')

    <main class="md:ml-64 min-h-screen p-4 sm:p-6 md:p-10 transition-all pt-16 md:pt-10">
        <div class="mx-auto max-w-4xl space-y-6">

            <!-- Breadcrumb Navigation -->
            <nav class="flex items-center gap-2 text-xs font-semibold text-gray-500">
                <a href="{{ route('documents') }}" class="hover:text-[#700000] flex items-center gap-1.5 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    <span>Back to Repository</span>
                </a>
                <span>/</span>
                <span class="text-gray-400">Upload Thesis</span>
            </nav>

            <!-- Page Title Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-gray-200 pb-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-[#700000] tracking-tight">
                        Upload Thesis
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600">
                        Upload and Publish to the repository.
                    </p>
                </div>
            </div>

            <!-- Error Alert Message Container -->
            <div id="uploadMessage" class="hidden"></div>

            <!-- Main Upload Form Card -->
            <section class="rounded-3xl border border-gray-200 bg-white p-6 sm:p-8 shadow-sm">
                
                <form id="uploadForm" class="space-y-5">

                    <!-- Title -->
                    <div>
                        <label for="title" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-700">
                            Thesis Title <span class="text-rose-600"></span>
                        </label>
                        <input
                            id="title"
                            name="title"
                            type="text"
                            placeholder="e.g. APC CAR RENTAL MANAGEMENT SYSTEM WITH GPS VEHICLE TRACKING"
                            required
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-xs md:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000] shadow-2xs">
                    </div>

                    <!-- Author -->
                    <div>
                        <label for="author" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-700">
                            Author(s) <span class="text-rose-600"></span>
                        </label>
                        <input
                            id="author"
                            name="author"
                            type="text"
                            placeholder="e.g. Charmie Lou A. Abayon, Maria Victoria S. Peria, Jomar Rhey D. Requirme"
                            required
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-xs md:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000] shadow-2xs">
                        <p class="mt-1 text-[11px] text-gray-400">Separate multiple authors with commas.</p>
                    </div>

                    <!-- Department & Program -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="department" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-700">
                                Department <span class="text-rose-600"></span>
                            </label>
                            <select
                                id="department"
                                name="department"
                                required
                                onchange="handleDepartmentChange(this.value)"
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-xs md:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000] shadow-2xs">
                                <option value="" disabled selected>Select Department</option>
                                <option value="it">Information Technology Department</option>
                                <option value="marine">Marine Engineering Department</option>
                                <option value="nursing">Nursing Department</option>
                                <option value="hospitality">Hospitality Management</option>
                                <option value="education">Education Department</option>
                                <option value="criminology">Criminology Department</option>
                            </select>
                        </div>

                        <div>
                            <label for="course_code" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-700">
                                Degree Program <span class="text-rose-600"></span>
                            </label>
                            <select
                                id="course_code"
                                name="course_code"
                                required
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-xs md:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000] shadow-2xs">
                                <option value="" disabled selected>Select Program</option>
                                <option value="bsit">BS in Information Technology (BSIT)</option>
                                <option value="bsmare">BS in Marine Engineering (BSMarE)</option>
                                <option value="bsn">BS in Nursing (BSN)</option>
                                <option value="bshm">BS in Hospitality Management (BSHM)</option>
                                <option value="bsed">Bachelor of Secondary Education (BSED)</option>
                                <option value="bsc">BS in Criminology (BSC)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Abstract -->
                    <div>
                        <label for="abstract" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-700">
                            Abstract <span class="text-rose-600"></span>
                        </label>
                        <textarea
                            id="abstract"
                            name="abstract"
                            rows="5"
                            placeholder="Paste the complete abstract of the thesis paper here..."
                            required
                            class="w-full rounded-xl border border-gray-300 bg-white p-4 text-xs md:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000] shadow-2xs"></textarea>
                    </div>

                    <!-- PDF Manuscript File (Interactive Dropzone) -->
                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-700">
                            Upload PDF File <span class="text-rose-600"></span>
                        </label>

                        <!-- Drag & Drop Container -->
                        <div
                            id="dropzoneContainer"
                            onclick="document.getElementById('pdf').click()"
                            class="relative flex flex-col items-center justify-center p-8 border-2 border-dashed border-gray-300 hover:border-[#700000] rounded-2xl bg-slate-50 hover:bg-rose-50/20 cursor-pointer transition text-center group">
                            
                            <input
                                id="pdf"
                                name="pdf"
                                type="file"
                                accept=".pdf,application/pdf"
                                required
                                onchange="handleFileSelected(this.files[0])"
                                class="hidden">

                            <div class="w-12 h-12 rounded-2xl bg-[#700000]/10 text-[#700000] flex items-center justify-center mb-3 group-hover:scale-110 transition">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                                </svg>
                            </div>

                            <p class="text-xs sm:text-sm font-bold text-gray-800">
                                Click to select manuscript or drag and drop PDF here
                            </p>
                            <p class="text-[11px] text-gray-400 mt-1">
                                Supports standard PDF documents up to 50 MB
                            </p>
                        </div>

                        <!-- Selected File Preview Badge -->
                        <div id="filePreviewCard" class="hidden items-center justify-between p-3.5 rounded-2xl bg-slate-100 border border-gray-200">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-lg bg-[#700000] text-[#FFD700] flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p id="previewFileName" class="text-xs font-bold text-gray-800 truncate"></p>
                                    <p id="previewFileSize" class="text-[10px] text-gray-500"></p>
                                </div>
                            </div>
                            <button
                                type="button"
                                onclick="clearSelectedFile(event)"
                                class="p-1 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-gray-200 transition"
                                title="Remove File">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Progress Bar & AI Vectorizing Feedback -->
                    <div id="progressContainer" class="rounded-2xl border border-gray-200 bg-slate-50 p-5 space-y-3">
                        <div class="flex items-center justify-between text-xs">
                            <span id="progressText" class="font-bold text-[#700000]">Preparing manuscript upload...</span>
                            <span id="progressPercent" class="font-mono font-bold text-gray-700">0%</span>
                        </div>
                        <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200">
                            <div id="progressBar" class="h-full w-0 rounded-full bg-[#700000] transition-all duration-300"></div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            id="uploadButton"
                            type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#700000] px-6 py-3.5 text-xs md:text-sm font-bold text-[#FFD700] shadow-md transition hover:bg-[#850000] disabled:cursor-not-allowed disabled:opacity-60">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                            </svg>
                            <span>Upload Thesis</span>
                        </button>
                    </div>

                </form>
            </section>
        </div>
    </main>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div id="successModalCard" class="w-full max-w-sm rounded-3xl bg-white p-8 text-center shadow-2xl">
            <!-- Green Circle Checkmark -->
            <div class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-emerald-50 ring-8 ring-emerald-100/70">
                <svg class="h-10 w-10 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            
            <h3 class="text-xl font-bold text-gray-900">Thesis Published!</h3>
            <p id="successModalMessage" class="mt-2 text-xs md:text-sm text-gray-600 leading-relaxed">
                Thesis uploaded, text extracted, and vectorized successfully.
            </p>
            
            <div class="mt-6 flex flex-col gap-2">
                <a href="{{ route('documents') }}" class="w-full py-2.5 rounded-xl bg-[#700000] text-xs font-bold text-[#FFD700] shadow-md hover:bg-[#850000] transition">
                    View in Repository
                </a>
                <button onclick="closeSuccessModal()" class="w-full py-2 rounded-xl text-xs font-semibold text-gray-500 hover:bg-gray-100 transition">
                    Upload Another Thesis
                </button>
            </div>
        </div>
    </div>

    <!-- JavaScript Upload Handler -->
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
        const dropzoneContainer = document.getElementById('dropzoneContainer');
        const filePreviewCard = document.getElementById('filePreviewCard');

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

        function handleFileSelected(file) {
            if (!file) return;
            document.getElementById('previewFileName').textContent = file.name;
            document.getElementById('previewFileSize').textContent = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
            filePreviewCard.classList.remove('hidden');
            filePreviewCard.classList.add('flex');
        }

        function clearSelectedFile(e) {
            e.stopPropagation();
            document.getElementById('pdf').value = '';
            filePreviewCard.classList.add('hidden');
            filePreviewCard.classList.remove('flex');
        }

        // Drag & Drop event handlers
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzoneContainer.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropzoneContainer.classList.add('border-[#700000]', 'bg-rose-50/40');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzoneContainer.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropzoneContainer.classList.remove('border-[#700000]', 'bg-rose-50/40');
            }, false);
        });

        dropzoneContainer.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length && files[0].type === 'application/pdf') {
                document.getElementById('pdf').files = files;
                handleFileSelected(files[0]);
            }
        }, false);

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
                        <div class="shrink-0 text-red-600 font-bold">✕</div>
                        <div class="flex-1">
                            <h3 class="text-xs md:text-sm font-bold text-red-800">${escapeHtml(title)}</h3>
                            <p class="text-xs text-red-700 mt-0.5 leading-relaxed">${escapeHtml(message)}</p>
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
            if (text) progressText.textContent = text;
        }

        // Fast client-side PDF text extraction using PDF.js
        async function extractPdfText(file) {
            const arrayBuffer = await file.arrayBuffer();
            const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
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
                    chunks.push({ page: pageNum, text: pageText });
                }
            }

            return chunks;
        }

        uploadForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const file = document.getElementById('pdf').files[0];
            if (!file) {
                showErrorAlert('Missing PDF file', 'Please select a PDF manuscript file to upload.');
                return;
            }

            uploadButton.disabled = true;
            uploadButton.innerHTML = `
                <svg class="w-4 h-4 animate-spin text-[#FFD700]" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Processing & Vectorizing...</span>
            `;
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
                    body: JSON.stringify({ filename: file.name })
                });
                const urlData = await urlRes.json();
                if (!urlRes.ok || urlData.error) {
                    throw new Error(urlData.message || 'Failed to prepare upload URL');
                }

                // 3. Upload PDF directly to Supabase Storage
                updateProgress(45, 'Uploading PDF to Supabase Storage...');
                const bucketName = "{{ config('services.supabase.bucket', env('SUPABASE_STORAGE_BUCKET', 'thesis')) }}";
                const { error: uploadError } = await supabaseClient
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

                    if (embData.processed === 0 || remaining === 0) break;
                }

                updateProgress(100, 'Upload and indexing complete!');
                uploadForm.reset();
                filePreviewCard.classList.add('hidden');
                filePreviewCard.classList.remove('flex');

                openSuccessModal(`"${thesisTitle}" has been uploaded and indexed successfully.`);

            } catch (err) {
                showErrorAlert('Upload Failed', err.message || 'There was a problem uploading the thesis.');
            } finally {
                uploadButton.disabled = false;
                uploadButton.innerHTML = `
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                    <span>Upload Thesis</span>
                `;
            }
        });
    </script>
</body>

</html>