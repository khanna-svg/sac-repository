<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $document->title }} - SAC Thesis Repository</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-50 text-slate-800 font-sans">

    @include('partials.sidebar')

    <!-- Hidden Data Attributes for Safe JavaScript Access -->
    <div id="citationData"
        data-id="{{ $document->id }}"
        data-title="{{ $document->title }}"
        data-author="{{ $document->author }}"
        data-year="{{ $document->created_at ? $document->created_at->format('Y') : date('Y') }}"
        class="hidden"></div>

    <main class="md:ml-64 min-h-screen p-4 sm:p-6 md:p-10 transition-all pt-16 md:pt-10">
        <div class="mx-auto max-w-4xl">

            <!-- Breadcrumb Navigation -->
            <nav class="mb-6 flex items-center gap-2 text-xs font-semibold text-gray-500">
                <a href="{{ route('documents') }}" class="hover:text-[#700000] flex items-center gap-1.5 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    <span>Back to Repository</span>
                </a>
                <span>/</span>
                <span class="text-gray-400 truncate max-w-xs">{{ $document->title }}</span>
            </nav>

            <!-- Main Document Card -->
            <article class="relative rounded-3xl border border-gray-200 bg-white p-6 md:p-8 shadow-sm">

                <!-- Top-Right Bookmark Button -->
                <button
                    type="button"
                    id="bookmarkDetailBtn"
                    onclick="toggleDetailBookmark()"
                    title="Bookmark Thesis"
                    class="absolute top-6 right-6 p-2.5 rounded-2xl border border-gray-200 bg-white text-gray-400 hover:text-[#700000] hover:border-gray-300 hover:bg-slate-50 transition shadow-sm flex items-center justify-center">
                    <svg id="bookmarkIcon" class="w-5 h-5 fill-none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                    </svg>
                </button>

                <!-- Badges & Cover Image File Resolution -->
                @php
                $departmentNames = [
                    'nursing' => 'Nursing Department',
                    'marine' => 'Marine Engineering Department',
                    'it' => 'Information Technology Department',
                    'hospitality' => 'Hospitality Management',
                    'education' => 'Education Department',
                    'criminology' => 'Criminology Department',
                ];

                $courseNames = [
                    'bsn' => 'BS in Nursing (BSN)',
                    'bsmare' => 'BS in Marine Engineering (BSMarE)',
                    'bsit' => 'BS in Information Technology (BSIT)',
                    'bshm' => 'BS in Hospitality Management (BSHM)',
                    'bsed' => 'Bachelor of Secondary Education (BSED)',
                    'bsc' => 'BS in Criminology (BSC)',
                ];

                $coverMap = [
                    'nursing' => 'NURSING',
                    'bsn' => 'NURSING',
                    'marine' => 'MARINE',
                    'bsmare' => 'MARINE',
                    'it' => 'IT',
                    'bsit' => 'IT',
                    'hospitality' => 'HM',
                    'bshm' => 'HM',
                    'education' => 'EDUC',
                    'bsed' => 'EDUC',
                    'criminology' => 'CRIM',
                    'bsc' => 'CRIM',
                ];

                $deptKey = strtolower($document->department ?? '');
                $courseKey = strtolower($document->course_code ?? '');
                $coverFilename = $coverMap[$deptKey] ?? $coverMap[$courseKey] ?? 'IT';
                @endphp

                <div class="flex flex-wrap items-center gap-2 mb-4 pr-12">
                    <span class="rounded-lg bg-[#700000]/10 text-[#700000] border border-[#700000]/20 px-3 py-1 text-xs font-bold">
                        St. Anthony's College
                    </span>

                    @if(!empty($document->department))
                    <span class="rounded-lg bg-blue-50 text-blue-700 border border-blue-200 px-3 py-1 text-xs font-bold">
                        {{ $departmentNames[$deptKey] ?? $document->department }}
                    </span>
                    @endif

                    @if(!empty($document->course_code))
                    <span class="rounded-lg bg-purple-50 text-purple-700 border border-purple-200 px-3 py-1 text-xs font-bold">
                        {{ $courseNames[$courseKey] ?? strtoupper($document->course_code) }}
                    </span>
                    @endif

                    <span class="rounded-lg bg-green-50 text-green-700 border border-green-200 px-3 py-1 text-xs font-bold flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Full Text Online</span>
                    </span>
                    <span class="rounded-lg bg-slate-100 text-gray-600 border border-gray-200 px-3 py-1 text-xs font-semibold">
                        {{ $document->created_at ? $document->created_at->format('F Y') : 'Recent' }}
                    </span>
                </div>

                <!-- Book Cover & Title Header Row -->
                <div class="flex flex-col sm:flex-row items-start gap-5 my-3">
                    <div class="w-20 sm:w-24 h-28 sm:h-32 shrink-0 rounded-lg overflow-hidden shadow-md border border-gray-200 bg-slate-100">
                        <img
                            src="{{ asset('images/covers/' . $coverFilename . '.webp') }}"
                            alt="{{ $document->title }} Cover"
                            class="w-full h-full object-cover"
                            onerror="handleImageError(this)">
                    </div>

                    <div class="flex-1">
                        <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 leading-tight">
                            {{ $document->title }}
                        </h1>

                        <div class="mt-3 flex flex-wrap items-center gap-y-1 gap-x-4 text-xs md:text-sm text-gray-600">
                            <p>
                                <span class="font-bold text-[#700000]">Author(s):</span>
                                <span class="font-semibold text-gray-800">{{ $document->author }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="my-6 flex flex-wrap items-center justify-between gap-3 bg-slate-50 border border-gray-200 rounded-2xl p-3 md:p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Cite Button -->
                        <button type="button" onclick="openCitationModal()" class="rounded-xl bg-white border border-gray-300 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-[#700000] hover:text-[#FFD700] hover:border-[#700000] transition flex items-center gap-1.5 shadow-sm">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                            </svg>
                            <span>Cite (IEEE)</span>
                        </button>

                        <!-- Ask AI Button -->
                        <a href="/chat?q={{ urlencode('Tell me about the thesis: ' . $document->title) }}" class="rounded-xl bg-white border border-gray-300 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-[#700000] hover:text-[#FFD700] hover:border-[#700000] transition flex items-center gap-1.5 shadow-sm">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                            </svg>
                            <span>Ask AI About This</span>
                        </a>

                        <!-- View PDF Button -->
                        <a href="/backend/documents/{{ $document->id }}/view" target="_blank" rel="noopener noreferrer" class="rounded-xl bg-white border border-gray-300 px-3.5 py-2 text-xs font-bold text-gray-700 hover:bg-gray-100 transition flex items-center gap-1.5 shadow-sm">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                            <span>View PDF</span>
                        </a>

                        <!-- Download PDF Button -->
                        <a href="/backend/documents/{{ $document->id }}/view?download=1" class="rounded-xl bg-[#700000] px-3.5 py-2 text-xs font-bold text-[#FFD700] hover:bg-[#800000] transition flex items-center gap-1.5 shadow-sm">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            <span>Download PDF</span>
                        </a>
                    </div>
                </div>

                <!-- Tabs: Abstract vs Full Text -->
                <div class="flex border-b border-gray-200 mb-6">
                    <button id="tabAbstractBtn" onclick="switchViewTab('abstract')" class="py-3 px-5 text-sm font-bold border-b-2 border-[#700000] text-[#700000] transition">
                        Abstract & Details
                    </button>
                    <button id="tabFullTextBtn" onclick="switchViewTab('fulltext')" class="py-3 px-5 text-sm font-bold border-b-2 border-transparent text-gray-500 hover:text-gray-800 transition">
                        Full Text Content
                    </button>
                </div>

                <!-- Abstract Tab Content -->
                <div id="tabAbstractContent" class="space-y-6">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-[#700000] mb-2 text-center">Abstract</h2>
                        <div class="rounded-2xl bg-slate-50 border border-gray-200 p-5 text-sm text-gray-700 leading-relaxed font-sans text-center">
                            {!! nl2br(e(preg_replace('/^[ \t]+/m', '', $document->abstract))) !!}
                        </div>
                    </div>
                </div>

                <!-- Full Text Tab Content -->
                <div id="tabFullTextContent" class="hidden space-y-6">
                    <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-[#700000]">
                            Extracted Full Text
                        </h2>
                        @if($document->chunks && $document->chunks->count() > 0)
                        <span class="rounded-lg bg-slate-100 px-3 py-1 text-xs font-semibold text-gray-600">
                            Total Pages: {{ $document->chunks->count() }}
                        </span>
                        @endif
                    </div>

                    @if($document->chunks && $document->chunks->count() > 0)
                    <div class="space-y-6 max-h-[700px] overflow-y-auto pr-2">
                        @foreach($document->chunks as $chunk)
                        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                            <div class="mb-4 flex items-center justify-between border-b border-gray-100 pb-2">
                                <span class="rounded-md bg-slate-100 px-2.5 py-1 text-xs font-bold text-[#700000]">
                                    Page {{ $chunk->page_number ?? $loop->iteration }}
                                </span>
                            </div>
                            <div class="space-y-4 text-center">
                                @foreach(explode("\n\n", $chunk->chunk_text) as $paragraph)
                                @if(trim($paragraph))
                                <p class="text-justify text-xs md:text-sm text-gray-800 leading-relaxed font-sans">
                                    {{ trim($paragraph) }}
                                </p>
                                @endif
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="rounded-2xl bg-amber-50 border border-amber-200 p-6 text-center text-sm text-amber-800">
                        <p class="font-bold">Full text extraction preview is being generated.</p>
                        <p class="mt-1 text-xs text-amber-700">You can view or download the complete original document using the button below:</p>
                        <a href="/backend/documents/{{ $document->id }}/view" target="_blank" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-[#700000] px-4 py-2 text-xs font-bold text-[#FFD700] hover:bg-[#800000]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                            <span>Open PDF File</span>
                        </a>
                    </div>
                    @endif
                </div>

            </article>
        </div>
    </main>

    <!-- FLOATING TOAST NOTIFICATION POP-UP -->
    <div id="toastNotification" class="fixed bottom-6 right-6 z-50 transform transition-all duration-300 translate-y-20 opacity-0 pointer-events-none">
        <div class="flex items-center gap-3 rounded-2xl bg-white text-gray-900 px-5 py-3.5 shadow-xl border border-gray-200">
            <span id="toastIconContainer" class="shrink-0">
                <svg id="toastBookmarkIcon" class="w-5 h-5 text-amber-500 fill-current" viewBox="0 0 24 24">
                    <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
            </span>
            <p id="toastMessage" class="text-xs md:text-sm font-semibold text-gray-800 tracking-wide">Added to bookmark</p>
        </div>
    </div>

    <!-- IEEE Citation Modal -->
    <div id="citationModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-[#700000]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                    </svg>
                    <h3 class="text-base font-bold text-gray-900">IEEE Citation</h3>
                </div>
                <button onclick="closeCitationModal()" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="mt-4">
                <div class="flex rounded-xl bg-slate-100 p-1 border border-gray-200">
                    <div class="w-full py-1.5 text-xs font-bold rounded-lg bg-white text-[#700000] shadow-sm text-center">
                        IEEE Style Format
                    </div>
                </div>
                <div class="mt-4 rounded-2xl border border-gray-200 bg-slate-50 p-4">
                    <p id="citationText" class="text-xs md:text-sm text-gray-800 leading-relaxed font-mono select-all break-words"></p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button onclick="closeCitationModal()" class="rounded-xl px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 transition">Close</button>
                <button onclick="copyCitation()" id="copyBtn" class="rounded-xl bg-[#700000] px-5 py-2.5 text-xs font-bold text-[#FFD700] hover:bg-[#800000] transition flex items-center gap-1.5 shadow-md">
                    <svg id="copyBtnIcon" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span id="copyBtnText">Copy Citation</span>
                </button>
            </div>
        </div>
    </div>

    <!-- JavaScript Logic -->
    <script>
        const dataElement = document.getElementById('citationData');
        const currentDocId = dataElement ? parseInt(dataElement.getAttribute('data-id'), 10) : null;
        const docTitle = dataElement ? dataElement.getAttribute('data-title') : 'Untitled Thesis';
        const docAuthor = dataElement ? dataElement.getAttribute('data-author') : 'Unknown Author';
        const docYear = dataElement ? dataElement.getAttribute('data-year') : new Date().getFullYear().toString();
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        let toastTimeout = null;

        function showToast(message, isSaved = true) {
            const toast = document.getElementById('toastNotification');
            const toastMsg = document.getElementById('toastMessage');
            const toastIconContainer = document.getElementById('toastIconContainer');

            if (!toast || !toastMsg) return;

            toastMsg.textContent = message;
            toastMsg.className = "text-xs md:text-sm font-semibold text-gray-800 tracking-wide";

            if (isSaved) {
                toastIconContainer.innerHTML = `
                    <svg class="w-5 h-5 text-amber-500 fill-current" viewBox="0 0 24 24">
                        <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                    </svg>
                `;
            } else {
                toastIconContainer.innerHTML = `
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                `;
            }

            toast.classList.remove('translate-y-20', 'opacity-0', 'pointer-events-none');
            toast.classList.add('translate-y-0', 'opacity-100');

            if (toastTimeout) clearTimeout(toastTimeout);

            toastTimeout = setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('translate-y-20', 'opacity-0', 'pointer-events-none');
            }, 2500);
        }

        function handleImageError(imageElement) {
            imageElement.onerror = null;
            imageElement.src = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='100' height='140' viewBox='0 0 100 140'><rect width='100%' height='100%' fill='%23700000'/><text x='50%' y='50%' font-size='12' font-weight='bold' fill='%23FFD700' text-anchor='middle' dominant-baseline='middle'>SAC THESIS</text></svg>";
        }

        function switchViewTab(tab) {
            const abstractBtn = document.getElementById('tabAbstractBtn');
            const fullTextBtn = document.getElementById('tabFullTextBtn');
            const abstractContent = document.getElementById('tabAbstractContent');
            const fullTextContent = document.getElementById('tabFullTextContent');

            if (tab === 'abstract') {
                abstractContent.classList.remove('hidden');
                fullTextContent.classList.add('hidden');
                abstractBtn.className = "py-3 px-5 text-sm font-bold border-b-2 border-[#700000] text-[#700000] transition";
                fullTextBtn.className = "py-3 px-5 text-sm font-bold border-b-2 border-transparent text-gray-500 hover:text-gray-800 transition";
            } else {
                abstractContent.classList.add('hidden');
                fullTextContent.classList.remove('hidden');
                fullTextBtn.className = "py-3 px-5 text-sm font-bold border-b-2 border-[#700000] text-[#700000] transition";
                abstractBtn.className = "py-3 px-5 text-sm font-bold border-b-2 border-transparent text-gray-500 hover:text-gray-800 transition";
            }
        }

        function openCitationModal() {
            const text = `${docAuthor}, "${docTitle}," Undergraduate thesis, St. Anthony's College, ${docYear}.`;
            document.getElementById('citationText').textContent = text;
            document.getElementById('citationModal').classList.remove('hidden');
            document.getElementById('citationModal').classList.add('flex');
        }

        function closeCitationModal() {
            document.getElementById('citationModal').classList.add('hidden');
            document.getElementById('citationModal').classList.remove('flex');
        }

        function copyCitation() {
            navigator.clipboard.writeText(document.getElementById('citationText').textContent).then(() => {
                const btnText = document.getElementById('copyBtnText');
                const btnIcon = document.getElementById('copyBtnIcon');
                btnText.textContent = 'Copied!';
                btnIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />`;
                setTimeout(() => {
                    btnText.textContent = 'Copy Citation';
                    btnIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />`;
                }, 2000);
            });
        }

        async function checkInitialBookmark(docId) {
            if (!docId) return;
            try {
                const res = await fetch('/backend/bookmarks/ids');
                if (res.ok) {
                    const ids = await res.json();
                    if (ids.includes(docId)) {
                        updateBookmarkBtnState(true);
                    }
                }
            } catch (e) {}
        }

        function updateBookmarkBtnState(isSaved) {
            const btn = document.getElementById('bookmarkDetailBtn');
            const icon = document.getElementById('bookmarkIcon');
            if (isSaved) {
                btn.title = "Remove from bookmark";
                btn.className = "absolute top-6 right-6 p-2.5 rounded-2xl border border-amber-300 bg-amber-50 text-amber-500 shadow-sm transition flex items-center justify-center";
                icon.setAttribute('class', 'w-5 h-5 fill-current');
            } else {
                btn.title = "Add to bookmark";
                btn.className = "absolute top-6 right-6 p-2.5 rounded-2xl border border-gray-200 bg-white text-gray-400 hover:text-[#700000] hover:border-gray-300 hover:bg-slate-50 transition shadow-sm flex items-center justify-center";
                icon.setAttribute('class', 'w-5 h-5 fill-none');
            }
        }

        async function toggleDetailBookmark() {
            if (!currentDocId) return;
            try {
                const res = await fetch('/backend/bookmarks/toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ document_id: currentDocId })
                });
                const data = await res.json();
                updateBookmarkBtnState(data.bookmarked);

                if (data.bookmarked) {
                    showToast('Added to bookmark', true);
                } else {
                    showToast('Removed from bookmark', false);
                }
            } catch (err) {
                console.error('Bookmark error:', err);
            }
        }

        if (currentDocId) {
            checkInitialBookmark(currentDocId);
        }
    </script>
</body>

</html>