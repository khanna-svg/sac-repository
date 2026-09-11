<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Thesis Submissions Review - SAC Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    </script>
    <link rel="icon" href="https://sac.campus-erp.com/Student/images/sac.png" type="image/png">
</head>

<body class="min-h-screen bg-slate-50 text-slate-800 font-sans">

    @include('partials.sidebar')

    <main class="md:ml-64 min-h-screen p-4 sm:p-6 md:p-10 transition-all pt-16 md:pt-10">
        <div class="mx-auto max-w-[1600px] space-y-6">

            <!-- Breadcrumb Navigation -->
            <nav class="flex items-center gap-2 text-xs font-semibold text-gray-500">
                <a href="{{ route('admin.analytics') }}" class="hover:text-[#700000] flex items-center gap-1.5 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    <span>Dashboard</span>
                </a>
                <span>/</span>
                <span class="text-gray-400">Student Submissions</span>
            </nav>

            <!-- Page Title and Description -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2.5">
                        <span class="p-2 rounded-2xl bg-[#700000] text-[#FFD700] shadow-xs inline-flex">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </span>
                        Thesis Review & Moderation Queue
                    </h1>
                    <p class="text-xs text-gray-500 mt-1">
                        Evaluate student submissions, inspect softcopies with Turnitin / Grammarly, and publish or request revisions.
                    </p>
                </div>
            </div>

            <!-- Tab Filters and Search Bar -->
            <div class="rounded-3xl border border-gray-200 bg-white p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
                
                <!-- Status Filter Tabs -->
                <div class="flex items-center gap-1.5 bg-slate-100 p-1.5 rounded-2xl w-full sm:w-auto overflow-x-auto">
                    <button
                        type="button"
                        onclick="switchTab('pending')"
                        id="tab-pending"
                        class="tab-btn px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-white text-[#700000] shadow-xs">
                        <span>Pending Review</span>
                        <span class="font-normal opacity-80">(<span id="badge-pending">0</span>)</span>
                    </button>
                    <button
                        type="button"
                        onclick="switchTab('approved')"
                        id="tab-approved"
                        class="tab-btn px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 text-gray-600 hover:text-gray-900">
                        <span>Approved</span>
                        <span class="font-normal opacity-80">(<span id="badge-approved">0</span>)</span>
                    </button>
                    <button
                        type="button"
                        onclick="switchTab('resubmit')"
                        id="tab-resubmit"
                        class="tab-btn px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 text-gray-600 hover:text-gray-900">
                        <span>Needs Resubmission</span>
                        <span class="font-normal opacity-80">(<span id="badge-resubmit">0</span>)</span>
                    </button>
                    <button
                        type="button"
                        onclick="switchTab('all')"
                        id="tab-all"
                        class="tab-btn px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 text-gray-600 hover:text-gray-900">
                        <span>All</span>
                        <span class="font-normal opacity-80">(<span id="badge-all">0</span>)</span>
                    </button>
                </div>

                <!-- Live Search Bar -->
                <div class="relative w-full sm:w-80">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </span>
                    <input
                        type="text"
                        id="submissionSearchInput"
                        oninput="handleSearch(this.value)"
                        placeholder="Search title, author, or student email..."
                        class="w-full rounded-2xl border border-gray-300 bg-white pl-10 pr-4 py-2.5 text-xs sm:text-sm text-gray-800 placeholder-gray-400 outline-none focus:border-[#700000] focus:ring-2 focus:ring-[#700000]/10 transition shadow-2xs">
                </div>
            </div>

            <!-- Submissions Table Container -->
            <div class="rounded-3xl border border-gray-200 bg-white shadow-xs overflow-hidden">
                <table class="w-full text-left border-collapse table-auto">
                    <thead>
                        <tr class="border-b border-gray-200 bg-slate-50/80 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                            <th class="py-4 px-4 sm:px-6 w-[40%]">Document Title</th>
                            <th class="py-4 px-4 w-[24%]">Student / Authors</th>
                            <th class="py-4 px-4 w-[16%]">Department</th>
                            <th class="py-4 px-4 w-[10%] text-center">Status</th>
                            <th class="py-4 px-4 sm:pr-6 text-right w-[10%]">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="submissionsTableBody" class="divide-y divide-gray-100 text-xs sm:text-sm">
                        <tr>
                            <td colspan="5" class="py-12 text-center text-gray-500">
                                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-[#700000] border-t-transparent mb-2"></div>
                                <p class="font-medium">Loading submissions...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <!-- Reject / Resubmission Notes Modal -->
    <div id="resubmitModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-xs p-4">
        <div class="w-full max-w-lg rounded-3xl bg-white p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-amber-100 text-amber-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </span>
                    <h3 class="text-base font-bold text-gray-900">Request Thesis Resubmission</h3>
                </div>
                <button type="button" onclick="closeResubmitModal()" class="p-1 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div>
                <h4 id="resubmitModalDocTitle" class="text-xs font-bold text-gray-800 line-clamp-1"></h4>
                <p class="text-[11px] text-gray-500 mt-0.5">Please provide specific review comments or Turnitin similarity feedback for the student.</p>
            </div>

            <div>
                <label for="adminNotesInput" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                    Reviewer Feedback Notes <span class="text-rose-500">*</span>
                </label>
                <textarea
                    id="adminNotesInput"
                    rows="5"
                    required
                    placeholder="e.g. Turnitin similarity index is 28% (exceeds the 15% threshold). Please paraphrase Chapter 2 (Literature Review) and verify in-text citations before resubmitting."
                    class="w-full rounded-2xl border border-gray-200 bg-slate-50/60 p-3.5 text-xs sm:text-sm text-gray-900 outline-none focus:border-[#700000] focus:bg-white focus:ring-2 focus:ring-[#700000]/10 transition shadow-2xs leading-relaxed"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-2">
                <button
                    type="button"
                    onclick="closeResubmitModal()"
                    class="rounded-xl border border-gray-200 px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-slate-100 transition cursor-pointer">
                    Cancel
                </button>
                <button
                    type="button"
                    id="confirmResubmitBtn"
                    onclick="submitResubmissionRequest()"
                    class="rounded-xl bg-amber-600 hover:bg-amber-700 px-5 py-2.5 text-xs font-bold text-white transition shadow-sm cursor-pointer flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span>Send Revision Request</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Secure In-App PDF Preview Reader Modal -->
    <div id="pdfReaderModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-xs p-2 sm:p-4">
        <div class="w-full max-w-5xl h-[92vh] rounded-3xl bg-slate-900 text-white flex flex-col overflow-hidden shadow-2xl border border-white/10">
            <!-- Modal Top Bar -->
            <div class="flex items-center justify-between px-6 py-3.5 border-b border-white/10 bg-slate-950">
                <div class="min-w-0 pr-4">
                    <h3 id="pdfPreviewTitle" class="text-xs sm:text-sm font-bold truncate text-gray-100">Thesis Manuscript Preview</h3>
                    <p id="pdfPreviewPageCount" class="text-[11px] text-amber-300 font-mono">Loading pages...</p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <a
                        id="pdfModalDownloadLink"
                        href="#"
                        target="_blank"
                        title="Download Softcopy for Turnitin / Grammarly"
                        class="p-2 rounded-xl bg-white/10 hover:bg-white/20 text-[#FFD700] transition flex items-center gap-1.5 text-xs font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        <span class="hidden sm:inline">Download</span>
                    </a>
                    <button type="button" onclick="closePdfReaderModal()" class="p-2 rounded-xl bg-white/10 hover:bg-white/20 text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- PDF Viewer Canvas Container with On-Scroll Lazy Loading -->
            <div id="pdfViewerScroll" class="flex-1 overflow-y-auto p-4 flex flex-col items-center gap-6 bg-slate-900 relative">
                <div id="pdfViewerLoading" class="py-12 flex flex-col items-center justify-center gap-2">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-[#FFD700] border-t-transparent"></div>
                    <span class="text-xs text-gray-400">Rendering manuscript pages...</span>
                </div>
                <div id="pdfCanvasWrapper" class="flex flex-col items-center gap-6 w-full max-w-3xl"></div>
            </div>
        </div>
    </div>

    <!-- Toast Notification Popup -->
    <div id="toastNotification" class="fixed bottom-6 right-6 z-50 transform transition-all duration-300 translate-y-20 opacity-0 pointer-events-none">
        <div id="toastBox" class="flex items-center gap-3 rounded-2xl bg-white text-gray-900 px-5 py-3.5 shadow-xl border border-gray-200">
            <span id="toastIcon"></span>
            <p id="toastMessage" class="text-xs md:text-sm font-semibold text-gray-800 tracking-wide"></p>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        let currentTab = 'pending';
        let currentSearch = '';
        let activeTargetDocId = null;
        let toastTimeout = null;

        const deptNames = {
            'it': 'Information Technology',
            'nursing': 'Nursing',
            'marine': 'Marine Engineering',
            'hospitality': 'Hospitality Management',
            'education': 'Education',
            'criminology': 'Criminology'
        };

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        }

        function showToast(message, isSuccess = true) {
            const toast = document.getElementById('toastNotification');
            const toastMsg = document.getElementById('toastMessage');
            const toastIcon = document.getElementById('toastIcon');

            toastMsg.textContent = message;
            toastIcon.innerHTML = isSuccess ? `
                <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            ` : `
                <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            `;

            toast.classList.remove('translate-y-20', 'opacity-0', 'pointer-events-none');
            toast.classList.add('translate-y-0', 'opacity-100');

            if (toastTimeout) clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => {
                toast.classList.add('translate-y-20', 'opacity-0', 'pointer-events-none');
                toast.classList.remove('translate-y-0', 'opacity-100');
            }, 3500);
        }

        function switchTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.className = 'tab-btn px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 text-gray-600 hover:text-gray-900';
            });
            const activeBtn = document.getElementById(`tab-${tab}`);
            if (activeBtn) {
                activeBtn.className = 'tab-btn px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-white text-[#700000] shadow-xs';
            }
            fetchSubmissions();
        }

        let searchDebounce = null;
        function handleSearch(val) {
            currentSearch = val;
            if (searchDebounce) clearTimeout(searchDebounce);
            searchDebounce = setTimeout(fetchSubmissions, 300);
        }

        async function fetchSubmissions() {
            const tbody = document.getElementById('submissionsTableBody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="py-12 text-center text-gray-500">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-[#700000] border-t-transparent mb-2"></div>
                        <p class="font-medium">Loading submissions...</p>
                    </td>
                </tr>
            `;

            try {
                const res = await fetch(`/backend/admin/submissions?tab=${encodeURIComponent(currentTab)}&search=${encodeURIComponent(currentSearch)}`);
                if (!res.ok) throw new Error('Failed to fetch submissions');
                const data = await res.json();

                // Update tab counters
                if (data.counts) {
                    document.getElementById('badge-pending').textContent = data.counts.pending || 0;
                    document.getElementById('badge-approved').textContent = data.counts.approved || 0;
                    document.getElementById('badge-resubmit').textContent = data.counts.resubmit || 0;
                    document.getElementById('badge-all').textContent = data.counts.all || 0;
                }

                renderTable(data.submissions || []);
            } catch (err) {
                console.error(err);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="py-10 text-center text-rose-600 font-semibold">
                            Failed to load submissions from server.
                        </td>
                    </tr>
                `;
            }
        }

        function renderTable(submissions) {
            const tbody = document.getElementById('submissionsTableBody');
            if (submissions.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="py-12 text-center text-gray-400">
                            No thesis submissions found for this tab.
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = submissions.map(sub => {
                const deptKey = (sub.department || 'it').toLowerCase();
                const deptName = deptNames[deptKey] || sub.department || 'N/A';
                const formattedDate = sub.created_at ? new Date(sub.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';

                const statusText = sub.status === 'approved' 
                    ? 'Approved' 
                    : (sub.status === 'resubmit' 
                        ? 'Needs Resubmit' 
                        : 'Pending Review');

                return `
                    <tr class="hover:bg-slate-50/80 transition">
                        <!-- Title & Date -->
                        <td class="py-4 px-4 sm:px-6">
                            <div class="min-w-0">
                                <h4 class="font-bold text-gray-900 leading-snug" title="${escapeHtml(sub.title)}">
                                    ${escapeHtml(sub.title)}
                                </h4>
                                <p class="text-[11px] text-gray-400 mt-0.5">Submitted on ${formattedDate}</p>
                                ${sub.admin_notes ? `
                                    <div class="mt-1.5 rounded-lg bg-rose-50 border border-rose-200 p-2 text-[11px] text-rose-800">
                                        <span class="font-bold">Feedback:</span> ${escapeHtml(sub.admin_notes)}
                                    </div>
                                ` : ''}
                            </div>
                        </td>

                        <!-- Student / Authors -->
                        <td class="py-4 px-4">
                            <p class="font-medium text-gray-800">${escapeHtml(sub.author || sub.submitted_by_name)}</p>
                            <p class="text-[11px] text-gray-400 font-mono mt-0.5">${escapeHtml(sub.submitted_by_email || 'N/A')}</p>
                        </td>

                        <!-- Department & Program -->
                        <td class="py-4 px-4 whitespace-nowrap text-xs text-gray-700 font-medium">
                            ${escapeHtml(deptName)} (${escapeHtml((sub.course_code || '').toUpperCase())})
                        </td>

                        <!-- Status -->
                        <td class="py-4 px-4 text-center whitespace-nowrap text-xs text-gray-700 font-medium">
                            ${statusText}
                        </td>

                        <!-- Actions (STRICTLY SVG ICONS, NO TEXT!) -->
                        <td class="py-4 px-4 sm:pr-6 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">
                                
                                <!-- 1. View / Preview PDF Modal (SVG Icon) -->
                                <button
                                    type="button"
                                    onclick="openPdfReader(${sub.id}, '${escapeHtml(sub.title).replace(/'/g, "\\'")}')"
                                    title="View / Preview PDF Manuscript"
                                    aria-label="View PDF"
                                    class="p-2 rounded-xl border border-gray-200 text-gray-600 hover:text-[#700000] hover:bg-slate-100 hover:border-gray-300 transition cursor-pointer shadow-2xs">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </button>

                                <!-- 2. Download Softcopy for Turnitin / Grammarly (SVG Icon) -->
                                <a
                                    href="/backend/admin/submissions/${sub.id}/download"
                                    target="_blank"
                                    title="Download PDF Softcopy for Turnitin / Grammarly check"
                                    aria-label="Download PDF for Turnitin"
                                    class="p-2 rounded-xl border border-blue-200 bg-blue-50/50 text-blue-700 hover:bg-blue-100 hover:border-blue-300 transition cursor-pointer shadow-2xs">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                    </svg>
                                </a>

                                <!-- 3. Approve & Proceed to Upload (SVG Icon) -->
                                <button
                                    type="button"
                                    onclick="approveSubmission(${sub.id}, '${escapeHtml(sub.title).replace(/'/g, "\\'")}')"
                                    title="Approve & Proceed to Upload Form"
                                    aria-label="Approve & Proceed to Upload"
                                    class="p-2 rounded-xl border border-emerald-200 bg-emerald-50/60 text-emerald-700 hover:bg-emerald-100 hover:border-emerald-300 transition cursor-pointer shadow-2xs">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </button>

                                <!-- 4. Reject / Request Resubmission (SVG Icon) -->
                                <button
                                    type="button"
                                    onclick="openResubmitModal(${sub.id}, '${escapeHtml(sub.title).replace(/'/g, "\\'")}', '${escapeHtml(sub.admin_notes || '').replace(/'/g, "\\'")}')"
                                    title="Request Resubmission with Notes (Turnitin / Revisions)"
                                    aria-label="Request Resubmission"
                                    class="p-2 rounded-xl border border-amber-200 bg-amber-50/60 text-amber-700 hover:bg-amber-100 hover:border-amber-300 transition cursor-pointer shadow-2xs">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Approve & Proceed to Upload action
        function approveSubmission(id, title) {
            window.location.href = `/admin/upload?from_submission=${id}`;
        }

        // Resubmission Modal logic
        function openResubmitModal(id, title, notes = '') {
            activeTargetDocId = id;
            document.getElementById('resubmitModalDocTitle').textContent = title;
            document.getElementById('adminNotesInput').value = notes;
            document.getElementById('resubmitModal').classList.remove('hidden');
            document.getElementById('resubmitModal').classList.add('flex');
        }

        function closeResubmitModal() {
            document.getElementById('resubmitModal').classList.add('hidden');
            document.getElementById('resubmitModal').classList.remove('flex');
            activeTargetDocId = null;
        }

        async function submitResubmissionRequest() {
            const notes = document.getElementById('adminNotesInput').value.trim();
            if (!notes) {
                alert('Please enter reviewer feedback notes for the student.');
                return;
            }

            const btn = document.getElementById('confirmResubmitBtn');
            btn.disabled = true;
            btn.innerHTML = 'Sending...';

            try {
                const res = await fetch(`/backend/admin/submissions/${activeTargetDocId}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ admin_notes: notes })
                });

                const data = await res.json();
                if (!res.ok || data.error) throw new Error(data.message || 'Failed to send revision request');

                showToast('⚠️ Thesis marked for resubmission and student notified.', true);
                closeResubmitModal();
                fetchSubmissions();
            } catch (err) {
                console.error(err);
                showToast(err.message || 'Failed to submit revision request', false);
            } finally {
                btn.disabled = false;
                btn.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span>Send Revision Request</span>
                `;
            }
        }

        // In-App PDF Reader Modal with on-scroll lazy loading
        let previewPdfDoc = null;
        let previewObserver = null;
        let renderedPreviewPages = new Set();

        async function openPdfReader(id, title) {
            const modal = document.getElementById('pdfReaderModal');
            const wrapper = document.getElementById('pdfCanvasWrapper');
            const loader = document.getElementById('pdfViewerLoading');
            const downloadLink = document.getElementById('pdfModalDownloadLink');

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            loader.classList.remove('hidden');
            wrapper.innerHTML = '';
            renderedPreviewPages.clear();
            if (previewObserver) previewObserver.disconnect();

            document.getElementById('pdfPreviewTitle').textContent = title;
            document.getElementById('pdfPreviewPageCount').textContent = 'Loading pages...';
            downloadLink.href = `/backend/admin/submissions/${id}/download`;

            try {
                const res = await fetch(`/backend/documents/${id}/signed-url`);
                if (!res.ok) throw new Error('Could not obtain signed PDF link');
                const data = await res.json();

                previewPdfDoc = await pdfjsLib.getDocument(data.url).promise;
                document.getElementById('pdfPreviewPageCount').textContent = `${previewPdfDoc.numPages} Pages`;

                // Build placeholders for all pages
                const frag = document.createDocumentFragment();
                for (let num = 1; num <= previewPdfDoc.numPages; num++) {
                    const card = document.createElement('div');
                    card.id = `preview-page-${num}`;
                    card.dataset.pageNum = num;
                    card.className = 'flex flex-col items-center bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-700 w-full max-w-full';
                    card.style.minHeight = '750px';
                    card.innerHTML = `
                        <div class="flex-1 flex items-center justify-center py-20 text-gray-400">
                            <span class="text-xs font-mono">Page ${num}</span>
                        </div>
                        <div class="w-full py-1 bg-slate-800 text-center text-[10px] text-gray-400 font-mono">
                            Page ${num} of ${previewPdfDoc.numPages}
                        </div>
                    `;
                    frag.appendChild(card);
                }
                wrapper.appendChild(frag);

                // Setup observer
                const scrollContainer = document.getElementById('pdfViewerScroll');
                previewObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const pNum = parseInt(entry.target.dataset.pageNum, 10);
                            if (pNum && !renderedPreviewPages.has(pNum)) {
                                renderPreviewPage(pNum);
                            }
                        }
                    });
                }, { root: scrollContainer, rootMargin: '400px 0px', threshold: 0.01 });

                document.querySelectorAll('#pdfCanvasWrapper > div').forEach(c => previewObserver.observe(c));

                // Render first page immediately
                renderPreviewPage(1);
                loader.classList.add('hidden');
            } catch (err) {
                console.error(err);
                loader.innerHTML = '<p class="text-rose-400 text-xs">Unable to preview PDF.</p>';
            }
        }

        async function renderPreviewPage(num) {
            if (!previewPdfDoc || renderedPreviewPages.has(num)) return;
            renderedPreviewPages.add(num);

            try {
                const page = await previewPdfDoc.getPage(num);
                const viewport = page.getViewport({ scale: 1.2 });
                const card = document.getElementById(`preview-page-${num}`);
                if (!card) return;

                const canvas = document.createElement('canvas');
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                canvas.className = 'block max-w-full h-auto';

                await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;

                card.innerHTML = '';
                card.appendChild(canvas);
                const footer = document.createElement('div');
                footer.className = 'w-full py-1 bg-slate-800 text-center text-[10px] text-gray-400 font-mono';
                footer.textContent = `Page ${num} of ${previewPdfDoc.numPages}`;
                card.appendChild(footer);
            } catch (e) {
                console.error(`Error rendering preview page ${num}:`, e);
            }
        }

        function closePdfReaderModal() {
            document.getElementById('pdfReaderModal').classList.add('hidden');
            document.getElementById('pdfReaderModal').classList.remove('flex');
            if (previewObserver) previewObserver.disconnect();
            previewPdfDoc = null;
        }

        // Close modal on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePdfReaderModal();
                closeResubmitModal();
            }
        });

        // Initialize table
        fetchSubmissions();
    </script>
</body>

</html>
