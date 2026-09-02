<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Theses - SAC Thesis Repository</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="https://sac.campus-erp.com/Student/images/sac.png" type="image/png">
</head>

<body class="bg-slate-50 text-slate-800 min-h-screen font-sans flex flex-col antialiased">

    @include('partials.sidebar')

    <div id="mainContent" class="md:ml-64 flex-1 transition-all duration-300 flex flex-col">

        <!-- Top Header Navigation -->
        <header class="sticky top-0 z-20 border-b border-gray-200 bg-white/95 backdrop-blur-md px-4 sm:px-8 py-4 shadow-xs flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#700000] text-[#FFD700] shadow-md shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-base sm:text-lg font-bold text-[#700000] leading-tight">Manage Uploaded Theses</h1>
                    <p class="text-xs text-gray-500">Edit metadata, fix departments, or remove outdated documents</p>
                </div>
            </div>

            <a href="/admin/upload" class="rounded-2xl bg-[#700000] hover:bg-[#850000] text-[#FFD700] px-4 py-2.5 text-xs font-bold transition shadow-md flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Upload New Thesis</span>
            </a>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 p-4 sm:p-8 max-w-7xl w-full mx-auto space-y-6">

            <!-- Search and Filter Bar -->
            <div class="rounded-3xl border border-gray-200 bg-white p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="relative flex-1 w-full">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        id="adminSearchInput"
                        type="text"
                        oninput="onSearchChange()"
                        placeholder="Search by title, author, or program code..."
                        class="w-full rounded-2xl border border-gray-300 bg-slate-50 pl-10 pr-4 py-2.5 text-xs sm:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000] transition">
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto shrink-0">
                    <select
                        id="adminDeptFilter"
                        onchange="loadTheses()"
                        class="w-full sm:w-56 rounded-2xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs sm:text-sm text-gray-700 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000] transition font-medium shadow-2xs">
                        <option value="all">All Academic Departments</option>
                        <option value="it">Information Technology (BSIT)</option>
                        <option value="marine">Marine Engineering (BSMARE)</option>
                        <option value="nursing">Nursing Department (BSN)</option>
                        <option value="hospitality">Hospitality Management (BSHM)</option>
                        <option value="education">Education Department (BSED)</option>
                        <option value="criminology">Criminology Department (BSC)</option>
                    </select>

                    <span id="thesesCountBadge" class="rounded-xl bg-slate-100 border border-gray-200 px-3 py-2 text-xs font-bold text-gray-600 whitespace-nowrap">
                        Loading...
                    </span>
                </div>
            </div>

            <!-- Theses Table Container -->
            <div class="rounded-3xl border border-gray-200 bg-white shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 bg-slate-50/80 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                                <th class="py-3.5 px-4 sm:px-6">Document</th>
                                <th class="py-3.5 px-4">Author(s)</th>
                                <th class="py-3.5 px-4">Department & Program</th>
                                <th class="py-3.5 px-4 text-center">AI Vectors</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="thesesTableBody" class="divide-y divide-gray-100 text-xs sm:text-sm">
                            <tr>
                                <td colspan="5" class="py-12 text-center text-gray-500">
                                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-[#700000] border-t-transparent mb-2"></div>
                                    <p class="font-medium">Loading repository theses...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- EDIT METADATA MODAL -->
    <div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4 overflow-y-auto">
        <div class="w-full max-w-lg rounded-3xl bg-white p-6 sm:p-8 shadow-2xl transition-all my-8">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-5">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-700 border border-amber-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Edit Thesis Metadata</h3>
                </div>
                <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 transition p-1 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="editForm" onsubmit="submitEditForm(event)" class="space-y-4">
                <input type="hidden" id="editDocId">

                <div>
                    <label for="editTitle" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-700">Thesis Title</label>
                    <input id="editTitle" type="text" required class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs sm:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000]">
                </div>

                <div>
                    <label for="editAuthor" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-700">Author(s)</label>
                    <input id="editAuthor" type="text" required class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs sm:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000]">
                    <p class="mt-0.5 text-[10px] text-gray-400">Separate multiple authors with commas</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="editDepartment" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-700">Department</label>
                        <select id="editDepartment" required onchange="handleEditDeptChange(this.value)" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-xs sm:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000]">
                            <option value="it">Information Technology</option>
                            <option value="marine">Marine Engineering</option>
                            <option value="nursing">Nursing</option>
                            <option value="hospitality">Hospitality Management</option>
                            <option value="education">Education</option>
                            <option value="criminology">Criminology</option>
                        </select>
                    </div>

                    <div>
                        <label for="editCourseCode" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-700">Degree Program</label>
                        <select id="editCourseCode" required class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-xs sm:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000]">
                            <option value="bsit">BSIT - Information Technology</option>
                            <option value="bsmare">BSMARE - Marine Engineering</option>
                            <option value="bsn">BSN - Nursing</option>
                            <option value="bshm">BSHM - Hospitality Management</option>
                            <option value="bsed">BSED - Secondary Education</option>
                            <option value="bsc">BSC - Criminology</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="editAbstract" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-700">Abstract</label>
                    <textarea id="editAbstract" rows="4" class="w-full rounded-xl border border-gray-300 bg-white p-3 text-xs sm:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000] leading-relaxed"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeEditModal()" class="rounded-xl border border-gray-300 px-4 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="saveEditBtn" class="rounded-xl bg-[#700000] hover:bg-[#850000] text-[#FFD700] px-5 py-2.5 text-xs font-bold transition shadow-md flex items-center gap-2 cursor-pointer">
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div id="deleteModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="w-full max-w-sm rounded-3xl bg-white p-6 sm:p-8 text-center shadow-2xl transition-all">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 ring-8 ring-rose-50/70">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900">Delete Thesis</h3>
            <p class="mt-2 text-xs text-gray-500 leading-relaxed">
                Are you sure you want to permanently delete <br>
                <strong id="deleteDocTitle" class="text-gray-800 font-semibold"></strong>?
            </p>
            <p class="mt-1 text-[11px] text-rose-600 font-medium">
                This will remove the PDF document, all vector embeddings, and student bookmarks.
            </p>
            <div class="mt-6 flex items-center justify-center gap-3">
                <button type="button" onclick="closeDeleteModal()" class="w-1/2 rounded-xl border border-gray-300 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 transition cursor-pointer">
                    Cancel
                </button>
                <button type="button" id="confirmDeleteBtn" onclick="submitDelete()" class="w-1/2 rounded-xl bg-rose-600 py-2.5 text-xs font-bold text-white shadow-md hover:bg-rose-700 transition cursor-pointer flex items-center justify-center gap-1.5">
                    <span>Delete</span>
                </button>
            </div>
        </div>
    </div>

    <!-- FLOATING TOAST -->
    <div id="adminToast" class="fixed bottom-6 right-6 z-50 hidden rounded-2xl bg-gray-900 px-4 py-3 text-xs font-semibold text-white shadow-2xl transition-all items-center gap-2">
        <span id="adminToastIcon">✓</span>
        <span id="adminToastMsg">Success</span>
    </div>

    <script>
        const COVERS_BASE_URL = "{{ asset('images/covers') }}";
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let allTheses = [];
        let pendingDeleteId = null;

        const programMap = {
            it: [{ code: 'bsit', name: 'BSIT - Information Technology' }],
            marine: [{ code: 'bsmare', name: 'BSMARE - Marine Engineering' }],
            nursing: [{ code: 'bsn', name: 'BSN - Nursing' }],
            hospitality: [{ code: 'bshm', name: 'BSHM - Hospitality Management' }],
            education: [{ code: 'bsed', name: 'BSED - Secondary Education' }],
            criminology: [{ code: 'bsc', name: 'BSC - Criminology' }]
        };

        const deptNames = {
            it: { name: 'Information Technology', badge: 'bg-blue-50 text-blue-700 border-blue-200', cover: 'IT.webp' },
            marine: { name: 'Marine Engineering', badge: 'bg-sky-50 text-sky-700 border-sky-200', cover: 'MARINE.webp' },
            nursing: { name: 'Nursing', badge: 'bg-emerald-50 text-emerald-700 border-emerald-200', cover: 'NURSING.webp' },
            hospitality: { name: 'Hospitality Management', badge: 'bg-amber-50 text-amber-800 border-amber-200', cover: 'HM.webp' },
            education: { name: 'Education', badge: 'bg-purple-50 text-purple-700 border-purple-200', cover: 'EDUC.webp' },
            criminology: { name: 'Criminology', badge: 'bg-red-50 text-red-700 border-red-200', cover: 'CRIM.webp' }
        };

        function showToast(message, isSuccess = true) {
            const toast = document.getElementById('adminToast');
            const msg = document.getElementById('adminToastMsg');
            const icon = document.getElementById('adminToastIcon');

            msg.textContent = message;
            icon.textContent = isSuccess ? '✓' : '✕';
            toast.className = `fixed bottom-6 right-6 z-50 flex rounded-2xl px-4 py-3 text-xs font-semibold text-white shadow-2xl transition-all items-center gap-2 ${isSuccess ? 'bg-emerald-800' : 'bg-rose-800'}`;
            
            setTimeout(() => {
                toast.classList.add('hidden');
                toast.classList.remove('flex');
            }, 3000);
        }

        async function loadTheses() {
            const tbody = document.getElementById('thesesTableBody');
            const deptFilter = document.getElementById('adminDeptFilter').value;
            const search = document.getElementById('adminSearchInput').value.trim();

            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="py-12 text-center text-gray-500">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-[#700000] border-t-transparent mb-2"></div>
                        <p class="font-medium">Loading repository theses...</p>
                    </td>
                </tr>
            `;

            try {
                const url = new URL('/backend/admin/theses', window.location.origin);
                if (search) url.searchParams.set('search', search);
                if (deptFilter && deptFilter !== 'all') url.searchParams.set('department', deptFilter);

                const res = await fetch(url.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (res.status === 401) {
                    window.location.href = '/login';
                    return;
                }

                const data = await res.json();
                allTheses = data.theses || [];
                renderTable(allTheses);
            } catch (err) {
                console.error(err);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="py-10 text-center text-rose-600 font-semibold">
                            Failed to load theses from server.
                        </td>
                    </tr>
                `;
            }
        }

        let searchDebounceTimer = null;
        function onSearchChange() {
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(loadTheses, 300);
        }

        function renderTable(theses) {
            const tbody = document.getElementById('thesesTableBody');
            const countBadge = document.getElementById('thesesCountBadge');
            countBadge.textContent = `${theses.length} Total Theses`;

            if (theses.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="py-12 text-center text-gray-400">
                            No theses match your current search or filter criteria.
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = theses.map(doc => {
                const deptKey = (doc.department || 'it').toLowerCase();
                const deptInfo = deptNames[deptKey] || { name: doc.department, badge: 'bg-gray-100 text-gray-700 border-gray-200', cover: 'IT.webp' };
                const formattedDate = doc.created_at ? new Date(doc.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
                const chunkCount = doc.chunks_count ?? 0;

                return `
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3.5 px-4 sm:px-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-14 rounded-lg bg-slate-100 border border-gray-200 overflow-hidden shrink-0 shadow-2xs">
                                    <img src="${COVERS_BASE_URL}/${deptInfo.cover}" class="w-full h-full object-cover" alt="Cover" onerror="this.src='${COVERS_BASE_URL}/IT.webp'">
                                </div>
                                <div class="min-w-0 max-w-sm sm:max-w-md">
                                    <h4 class="font-bold text-gray-900 truncate leading-snug" title="${escapeHtml(doc.title)}">
                                        ${escapeHtml(doc.title)}
                                    </h4>
                                    <p class="text-[11px] text-gray-400 mt-0.5">Uploaded: ${formattedDate}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-4 font-medium text-gray-700 max-w-xs truncate" title="${escapeHtml(doc.author)}">
                            ${escapeHtml(doc.author)}
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <span class="rounded-lg border px-2.5 py-1 text-[11px] font-bold ${deptInfo.badge}">
                                ${deptInfo.name} (${(doc.course_code || '').toUpperCase()})
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-mono font-bold text-slate-700">
                                ${chunkCount} chunks
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">
                                <a href="/documents/${doc.id}" target="_blank" title="View Thesis" class="p-2 rounded-xl border border-gray-200 text-gray-500 hover:text-[#700000] hover:bg-slate-100 transition cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </a>
                                <button type="button" onclick="openEditModal(${doc.id})" title="Edit Metadata" class="p-2 rounded-xl border border-gray-200 text-gray-500 hover:text-amber-600 hover:bg-amber-50 hover:border-amber-200 transition cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                    </svg>
                                </button>
                                <button type="button" onclick="openDeleteModal(${doc.id}, '${escapeHtml(doc.title)}')" title="Delete Thesis" class="p-2 rounded-xl border border-gray-200 text-gray-500 hover:text-rose-600 hover:bg-rose-50 hover:border-rose-200 transition cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function handleEditDeptChange(deptVal) {
            const courseSelect = document.getElementById('editCourseCode');
            courseSelect.innerHTML = '';
            const programs = programMap[deptVal] || programMap.it;
            programs.forEach(prog => {
                const opt = document.createElement('option');
                opt.value = prog.code;
                opt.textContent = prog.name;
                courseSelect.appendChild(opt);
            });
        }

        function openEditModal(docId) {
            const doc = allTheses.find(d => d.id === docId);
            if (!doc) return;

            document.getElementById('editDocId').value = doc.id;
            document.getElementById('editTitle').value = doc.title || '';
            document.getElementById('editAuthor').value = doc.author || '';
            
            const deptKey = (doc.department || 'it').toLowerCase();
            document.getElementById('editDepartment').value = deptKey;
            handleEditDeptChange(deptKey);
            
            document.getElementById('editCourseCode').value = (doc.course_code || 'bsit').toLowerCase();
            document.getElementById('editAbstract').value = doc.abstract || '';

            const modal = document.getElementById('editModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        async function submitEditForm(e) {
            e.preventDefault();
            const docId = document.getElementById('editDocId').value;
            const saveBtn = document.getElementById('saveEditBtn');
            
            saveBtn.disabled = true;
            saveBtn.innerHTML = `<span>Saving...</span>`;

            try {
                const res = await fetch(`/backend/admin/theses/${docId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        title: document.getElementById('editTitle').value.trim(),
                        author: document.getElementById('editAuthor').value.trim(),
                        department: document.getElementById('editDepartment').value,
                        course_code: document.getElementById('editCourseCode').value,
                        abstract: document.getElementById('editAbstract').value.trim(),
                    })
                });

                const data = await res.json();
                if (!res.ok || data.error) {
                    throw new Error(data.message || 'Failed to update thesis.');
                }

                closeEditModal();
                showToast('Thesis updated successfully!', true);
                loadTheses();
            } catch (err) {
                console.error(err);
                alert('Error updating thesis: ' + err.message);
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerHTML = `<span>Save Changes</span>`;
            }
        }

        function openDeleteModal(docId, title) {
            pendingDeleteId = docId;
            document.getElementById('deleteDocTitle').textContent = `"${title}"`;
            const modal = document.getElementById('deleteModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeDeleteModal() {
            pendingDeleteId = null;
            const modal = document.getElementById('deleteModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        async function submitDelete() {
            if (!pendingDeleteId) return;
            const btn = document.getElementById('confirmDeleteBtn');
            btn.disabled = true;
            btn.textContent = 'Deleting...';

            try {
                const res = await fetch(`/backend/admin/theses/${pendingDeleteId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const data = await res.json();
                if (!res.ok || data.error) {
                    throw new Error(data.message || 'Failed to delete thesis.');
                }

                closeDeleteModal();
                showToast('Thesis deleted permanently!', true);
                loadTheses();
            } catch (err) {
                console.error(err);
                alert('Error deleting thesis: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.textContent = 'Delete';
            }
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Global Escape Listener
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEditModal();
                closeDeleteModal();
            }
        });

        loadTheses();
    </script>
</body>
</html>