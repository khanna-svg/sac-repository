<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Submit Thesis Manuscript - SAC Repository</title>

    <!-- Supabase JS -->
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>

    <!-- PDF.js for client-side text chunking -->
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
                transform: scale(0.92);
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
        <div class="mx-auto max-w-6xl space-y-6">

            <!-- Top Header & Breadcrumb -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs font-semibold text-gray-500 mb-1">
                        <a href="{{ route('documents') }}" class="hover:text-[#700000] flex items-center gap-1.5 transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                            </svg>
                            <span>Repository</span>
                        </a>
                        <span>/</span>
                        <span class="text-gray-400">Upload Manuscript</span>
                    </nav>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2.5">
                        <span class="p-2 rounded-2xl bg-[#700000] text-[#FFD700] shadow-xs inline-flex">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                            </svg>
                        </span>
                        Submit Thesis for Peer Review
                    </h1>
                    <p class="text-xs text-gray-500 mt-1">
                        Submissions undergo administrator verification and Turnitin similarity review before publication.
                    </p>
                </div>

                <!-- Live Notification Center Button -->
                <div class="relative">
                    <button
                        type="button"
                        id="notifBellBtn"
                        onclick="toggleNotificationDropdown()"
                        class="px-4 py-2 rounded-2xl border border-gray-200 bg-white hover:bg-slate-50 text-gray-700 text-xs font-bold transition flex items-center gap-2 shadow-2xs relative">
                        <svg class="w-4 h-4 text-[#700000]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        </svg>
                        <span>Notifications</span>
                        <span id="notifBadge" class="hidden rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-black text-white">0</span>
                    </button>

                    <!-- Notifications Dropdown Box -->
                    <div
                        id="notifDropdown"
                        class="hidden absolute right-0 mt-2 w-80 sm:w-96 rounded-3xl bg-white p-4 shadow-2xl border border-gray-200 z-50 transition-all">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-2">
                            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Submission Updates</h3>
                            <button onclick="markAllNotificationsAsRead()" class="text-[10px] font-semibold text-[#700000] hover:underline cursor-pointer">Mark all as read</button>
                        </div>
                        <div id="notifList" class="max-h-72 overflow-y-auto divide-y divide-gray-100 text-xs">
                            <p class="py-4 text-center text-gray-400">Loading notifications...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two-Column Layout (Form on Left, My Submissions on Right) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- Submission Form Column (7 cols) -->
                <div class="lg:col-span-7 space-y-6">
                    <form id="studentUploadForm" class="rounded-3xl border border-gray-200 bg-white p-6 sm:p-8 shadow-xs space-y-5">
                        
                        <!-- Research Title -->
                        <div>
                            <label for="title" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                Research Title <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="title"
                                name="title"
                                required
                                placeholder="e.g. Automated Attendance System using Facial Recognition for SAC"
                                class="w-full rounded-2xl border border-gray-200 bg-slate-50/60 px-4 py-3 text-sm text-gray-900 outline-none focus:border-[#700000] focus:bg-white focus:ring-2 focus:ring-[#700000]/10 transition shadow-2xs font-medium">
                        </div>

                        <!-- Authors: Group Leader & Members -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="leader_name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Lead Author / Leader <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="leader_name"
                                    name="leader_name"
                                    required
                                    placeholder="e.g. Kurt Russel C. Calderon"
                                    class="w-full rounded-2xl border border-gray-200 bg-slate-50/60 px-4 py-2.5 text-sm text-gray-900 outline-none focus:border-[#700000] focus:bg-white focus:ring-2 focus:ring-[#700000]/10 transition shadow-2xs">
                            </div>
                            <div>
                                <label for="members" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Co-Authors / Members
                                </label>
                                <input
                                    type="text"
                                    id="members"
                                    name="members"
                                    placeholder="e.g. Juan Dela Cruz, Maria Santos"
                                    class="w-full rounded-2xl border border-gray-200 bg-slate-50/60 px-4 py-2.5 text-sm text-gray-900 outline-none focus:border-[#700000] focus:bg-white focus:ring-2 focus:ring-[#700000]/10 transition shadow-2xs">
                            </div>
                        </div>

                        <!-- Academic Department & Degree Program -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="department" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Department <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    id="department"
                                    name="department"
                                    required
                                    onchange="handleDepartmentChange()"
                                    class="w-full rounded-2xl border border-gray-200 bg-slate-50/60 px-3.5 py-2.5 text-xs sm:text-sm text-gray-900 outline-none focus:border-[#700000] focus:bg-white focus:ring-2 focus:ring-[#700000]/10 transition shadow-2xs font-semibold cursor-pointer">
                                    <option value="" disabled selected>Select Department</option>
                                    <option value="it">Information Technology</option>
                                    <option value="nursing">Nursing</option>
                                    <option value="marine">Marine Engineering</option>
                                    <option value="hospitality">Hospitality Management</option>
                                    <option value="education">Education</option>
                                    <option value="criminology">Criminology</option>
                                </select>
                            </div>
                            <div>
                                <label for="course_code" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Degree Program <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    id="course_code"
                                    name="course_code"
                                    required
                                    class="w-full rounded-2xl border border-gray-200 bg-slate-50/60 px-3.5 py-2.5 text-xs sm:text-sm text-gray-900 outline-none focus:border-[#700000] focus:bg-white focus:ring-2 focus:ring-[#700000]/10 transition shadow-2xs font-semibold cursor-pointer">
                                    <option value="" disabled selected>Select Program</option>
                                </select>
                            </div>
                        </div>

                        <!-- Abstract -->
                        <div>
                            <label for="abstract" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                Research Abstract <span class="text-rose-500">*</span>
                            </label>
                            <textarea
                                id="abstract"
                                name="abstract"
                                rows="4"
                                required
                                placeholder="Paste your complete research abstract here..."
                                class="w-full rounded-2xl border border-gray-200 bg-slate-50/60 p-4 text-xs sm:text-sm text-gray-900 outline-none focus:border-[#700000] focus:bg-white focus:ring-2 focus:ring-[#700000]/10 transition shadow-2xs leading-relaxed"></textarea>
                        </div>

                        <!-- PDF Manuscript File Upload Dropzone -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                PDF Softcopy Manuscript <span class="text-rose-500">*</span>
                            </label>
                            
                            <div
                                id="dropzoneContainer"
                                onclick="document.getElementById('pdf').click()"
                                class="border-2 border-dashed border-gray-300 hover:border-[#700000] rounded-3xl p-6 text-center cursor-pointer bg-slate-50/50 hover:bg-rose-50/20 transition group">
                                <input
                                    type="file"
                                    id="pdf"
                                    name="pdf"
                                    accept="application/pdf"
                                    class="hidden"
                                    onchange="handleFileSelected(this.files[0])">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 group-hover:bg-[#700000] text-[#700000] group-hover:text-[#FFD700] flex items-center justify-center transition mb-3 shadow-2xs">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                        </svg>
                                    </div>
                                    <p class="text-xs sm:text-sm font-bold text-gray-800">
                                        Click or drag PDF manuscript file here
                                    </p>
                                    <p class="text-[11px] text-gray-400 mt-1">
                                        PDF format up to 50MB • Ensure preliminary pages and references are intact
                                    </p>
                                </div>
                            </div>

                            <!-- Selected File Badge Preview -->
                            <div id="filePreviewCard" class="hidden items-center justify-between rounded-2xl bg-rose-50/80 border border-rose-200 p-3 mt-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <svg class="w-5 h-5 text-[#700000] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                    <div class="min-w-0">
                                        <p id="previewFileName" class="text-xs font-bold text-gray-900 truncate"></p>
                                        <p id="previewFileSize" class="text-[10px] text-gray-500"></p>
                                    </div>
                                </div>
                                <button type="button" onclick="clearSelectedFile(event)" class="p-1 text-gray-400 hover:text-rose-600 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Upload Progress Bar Container -->
                        <div id="progressContainer" class="space-y-2 pt-2">
                            <div class="flex justify-between text-xs font-bold text-gray-700">
                                <span id="progressText">Extracting text & uploading...</span>
                                <span id="progressPercent" class="font-mono text-[#700000]">0%</span>
                            </div>
                            <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100 border border-gray-200">
                                <div id="progressBar" class="h-full bg-gradient-to-r from-[#700000] to-[#b80000] transition-all duration-300 w-0"></div>
                            </div>
                        </div>

                        <!-- Feedback Message Box -->
                        <div id="uploadMessage" class="hidden"></div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            id="submitButton"
                            class="w-full rounded-2xl bg-[#700000] py-3.5 px-6 text-sm font-bold text-[#FFD700] hover:bg-[#850000] transition shadow-md hover:shadow-lg flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                            </svg>
                            <span>Submit Thesis for Review</span>
                        </button>
                    </form>
                </div>

                <!-- My Submissions History Column (5 cols) -->
                <div class="lg:col-span-5 space-y-4">
                    <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-xs">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-[#700000]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="text-sm font-bold text-gray-900">My Submissions</h3>
                            </div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-mono font-bold text-slate-700">
                                {{ count($submissions) }} total
                            </span>
                        </div>

                        @if($submissions->isEmpty())
                            <div class="py-10 text-center text-gray-400 text-xs">
                                <p>You haven't submitted any thesis manuscripts yet.</p>
                                <p class="mt-1 text-[11px] text-gray-400">Fill out the form to submit your research for review.</p>
                            </div>
                        @else
                            <div class="space-y-3.5 max-h-[600px] overflow-y-auto pr-1">
                                @foreach($submissions as $sub)
                                    @php
                                        $statusBadge = match($sub->status) {
                                            'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'resubmit' => 'bg-rose-50 text-rose-700 border-rose-200',
                                            default => 'bg-amber-50 text-amber-700 border-amber-200'
                                        };
                                        $statusLabel = match($sub->status) {
                                            'approved' => 'Approved & Published',
                                            'resubmit' => 'Needs Resubmission',
                                            default => 'Pending Review'
                                        };
                                    @endphp

                                    <div class="rounded-2xl border border-gray-200 bg-slate-50/60 p-4 space-y-2 hover:bg-white hover:shadow-xs transition">
                                        <div class="flex items-start justify-between gap-2">
                                            <h4 class="font-bold text-xs text-gray-900 leading-snug line-clamp-2">
                                                {{ $sub->title }}
                                            </h4>
                                            <span class="shrink-0 rounded-lg border px-2 py-0.5 text-[10px] font-bold {{ $statusBadge }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </div>

                                        <p class="text-[11px] text-gray-500">
                                            Author: <span class="font-medium text-gray-700">{{ $sub->author }}</span>
                                        </p>
                                        
                                        <p class="text-[10px] text-gray-400">
                                            Submitted: {{ $sub->created_at ? $sub->created_at->format('M d, Y') : 'N/A' }}
                                        </p>

                                        @if($sub->status === 'resubmit' && !empty($sub->admin_notes))
                                            <div class="mt-2 rounded-xl bg-rose-50 border border-rose-200 p-3 text-xs text-rose-900">
                                                <p class="font-bold text-[11px] flex items-center gap-1.5 text-rose-700">
                                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                                    </svg>
                                                    Admin Reviewer Feedback:
                                                </p>
                                                <p class="mt-1 text-[11px] text-rose-800 leading-relaxed font-sans select-all whitespace-pre-line">{{ $sub->admin_notes }}</p>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-xs p-4">
        <div id="successModalCard" class="w-full max-w-md rounded-3xl bg-white p-6 sm:p-8 shadow-2xl text-center space-y-4">
            <div class="mx-auto w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center shadow-xs">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>
            <h3 class="text-lg font-black text-gray-900">Submission Received!</h3>
            <p id="successModalMessage" class="text-xs text-gray-600 leading-relaxed">
                Your thesis has been submitted successfully. It is now queued for administrator review. You will receive an in-app notification once it has been evaluated.
            </p>
            <div class="pt-2">
                <button
                    type="button"
                    onclick="window.location.reload()"
                    class="w-full rounded-2xl bg-[#700000] py-3 text-xs sm:text-sm font-bold text-[#FFD700] hover:bg-[#850000] transition shadow-md cursor-pointer">
                    Done
                </button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const deptPrograms = {
            'it': [
                { code: 'bsit', name: 'BS in Information Technology (BSIT)' }
            ],
            'nursing': [
                { code: 'bsn', name: 'BS in Nursing (BSN)' }
            ],
            'marine': [
                { code: 'bsmare', name: 'BS in Marine Engineering (BSMarE)' }
            ],
            'hospitality': [
                { code: 'bshm', name: 'BS in Hospitality Management (BSHM)' }
            ],
            'education': [
                { code: 'bsed', name: 'Bachelor of Secondary Education (BSED)' }
            ],
            'criminology': [
                { code: 'bsc', name: 'BS in Criminology (BSC)' }
            ]
        };

        function handleDepartmentChange() {
            const dept = document.getElementById('department').value;
            const courseSelect = document.getElementById('course_code');
            courseSelect.innerHTML = '<option value="" disabled selected>Select Program</option>';

            if (deptPrograms[dept]) {
                deptPrograms[dept].forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.code;
                    opt.textContent = p.name;
                    courseSelect.appendChild(opt);
                });
                if (deptPrograms[dept].length === 1) {
                    courseSelect.selectedIndex = 1;
                }
            }
        }

        function handleFileSelected(file) {
            if (!file) return;
            document.getElementById('previewFileName').textContent = file.name;
            document.getElementById('previewFileSize').textContent = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
            document.getElementById('filePreviewCard').classList.remove('hidden');
            document.getElementById('filePreviewCard').classList.add('flex');
        }

        function clearSelectedFile(e) {
            e.stopPropagation();
            document.getElementById('pdf').value = '';
            document.getElementById('filePreviewCard').classList.add('hidden');
            document.getElementById('filePreviewCard').classList.remove('flex');
        }

        function updateProgress(percent, text) {
            const rounded = Math.round(percent);
            document.getElementById('progressBar').style.width = `${rounded}%`;
            document.getElementById('progressPercent').textContent = `${rounded}%`;
            if (text) document.getElementById('progressText').textContent = text;
        }

        function showError(msg) {
            const box = document.getElementById('uploadMessage');
            box.innerHTML = `
                <div class="rounded-2xl bg-rose-50 border border-rose-200 p-3.5 text-xs text-rose-800 font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span>${msg}</span>
                </div>
            `;
            box.classList.remove('hidden');
            document.getElementById('progressContainer').style.display = 'none';
        }

        // Fast Client-Side PDF Text Extraction
        async function extractPdfText(file) {
            const arrayBuffer = await file.arrayBuffer();
            const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
            const totalPages = pdf.numPages;
            const chunks = [];

            for (let pageNum = 1; pageNum <= totalPages; pageNum++) {
                updateProgress((pageNum / totalPages) * 35, `Extracting page ${pageNum} of ${totalPages}...`);
                const page = await pdf.getPage(pageNum);
                const textContent = await page.getTextContent();
                const text = textContent.items
                    .map(item => item.str)
                    .join(' ')
                    .replace(/[ \t]+/g, ' ')
                    .replace(/[\r\n]+/g, '\n\n')
                    .trim();

                if (text.length > 0) {
                    chunks.push({ page: pageNum, text: text });
                }
            }
            return chunks;
        }

        // Submission Pipeline
        document.getElementById('studentUploadForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const file = document.getElementById('pdf').files[0];
            if (!file) {
                showError('Please select your thesis manuscript PDF file.');
                return;
            }

            const submitBtn = document.getElementById('submitButton');
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="w-4 h-4 animate-spin text-[#FFD700]" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Processing submission...</span>
            `;

            document.getElementById('uploadMessage').classList.add('hidden');
            document.getElementById('progressContainer').style.display = 'block';

            try {
                // 1. Text extraction
                updateProgress(5, 'Extracting manuscript text...');
                const extractedChunks = await extractPdfText(file);

                // 2. Request upload URL
                updateProgress(45, 'Requesting secure upload authorization...');
                const urlRes = await fetch('/backend/student/upload-url', {
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
                    throw new Error(urlData.message || 'Failed to prepare upload destination.');
                }

                // 3. Upload directly to Supabase storage
                updateProgress(65, 'Uploading PDF manuscript to storage...');
                const bucket = "{{ config('services.supabase.bucket', env('SUPABASE_STORAGE_BUCKET', 'thesis')) }}";
                const { error: uploadErr } = await supabaseClient
                    .storage
                    .from(bucket)
                    .uploadToSignedUrl(urlData.path, urlData.token, file, { contentType: 'application/pdf' });

                if (uploadErr) throw uploadErr;

                // 4. Save metadata to Laravel
                updateProgress(85, 'Finalizing manuscript submission...');
                const storeRes = await fetch('/backend/student/submit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        title: document.getElementById('title').value.trim(),
                        leader_name: document.getElementById('leader_name').value.trim(),
                        members: document.getElementById('members').value.trim(),
                        department: document.getElementById('department').value,
                        course_code: document.getElementById('course_code').value,
                        abstract: document.getElementById('abstract').value.trim(),
                        file_path: urlData.path,
                        chunks: extractedChunks
                    })
                });

                const storeData = await storeRes.json();
                if (!storeRes.ok || storeData.error) {
                    throw new Error(storeData.message || 'Failed to store submission metadata.');
                }

                updateProgress(100, 'Submission complete!');
                document.getElementById('successModal').classList.remove('hidden');
                document.getElementById('successModal').classList.add('flex');
            } catch (err) {
                console.error(err);
                showError(err.message || 'An unexpected error occurred during submission.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                    <span>Submit Thesis for Review</span>
                `;
            }
        });

        // Notifications Dropdown and Fetch
        async function fetchNotifications() {
            try {
                const res = await fetch('/backend/notifications');
                if (!res.ok) return;
                const data = await res.json();
                const badge = document.getElementById('notifBadge');
                const list = document.getElementById('notifList');

                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }

                if (data.notifications.length === 0) {
                    list.innerHTML = '<p class="py-4 text-center text-gray-400">No notifications yet.</p>';
                    return;
                }

                list.innerHTML = data.notifications.map(n => {
                    const iconColor = n.type === 'approved' ? 'text-emerald-500' : (n.type === 'resubmit' ? 'text-rose-500' : 'text-amber-500');
                    const timeAgo = n.created_at ? new Date(n.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';
                    return `
                        <div class="py-2.5 space-y-1 ${!n.is_read ? 'bg-amber-50/40 p-2 rounded-xl' : ''}">
                            <div class="flex items-center justify-between gap-1">
                                <h4 class="font-bold text-gray-900 ${iconColor} flex items-center gap-1.5">
                                    <span>${n.title}</span>
                                </h4>
                                <span class="text-[9px] text-gray-400 shrink-0 font-mono">${timeAgo}</span>
                            </div>
                            <p class="text-[11px] text-gray-700 leading-snug whitespace-pre-line">${n.message}</p>
                        </div>
                    `;
                }).join('');
            } catch (e) {
                console.error('Failed to fetch notifications:', e);
            }
        }

        function toggleNotificationDropdown() {
            const dd = document.getElementById('notifDropdown');
            dd.classList.toggle('hidden');
            if (!dd.classList.contains('hidden')) {
                fetchNotifications();
            }
        }

        async function markAllNotificationsAsRead() {
            try {
                await fetch('/backend/notifications/read-all', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                });
                fetchNotifications();
            } catch (e) {}
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dd = document.getElementById('notifDropdown');
            const btn = document.getElementById('notifBellBtn');
            if (!dd.contains(e.target) && !btn.contains(e.target)) {
                dd.classList.add('hidden');
            }
        });

        // Initialize notification count on load
        fetchNotifications();
    </script>
</body>

</html>
