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
                <a href="{{ route('documents') }}" class="hover:text-[#700000] flex items-center gap-1">
                    ← Back to Repository
                </a>
                <span>/</span>
                <span class="text-gray-400 truncate max-w-xs">{{ $document->title }}</span>
            </nav>

            <!-- Main Document Card -->
            <article class="rounded-3xl border border-gray-200 bg-white p-6 md:p-8 shadow-sm">

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

                <div class="flex flex-wrap items-center gap-2 mb-4">
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
                        ✓ Full Text Online
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
                            <span>📝</span> Cite (IEEE)
                        </button>

                        <!-- Bookmark Button -->
                        <button type="button" id="bookmarkDetailBtn" onclick="toggleDetailBookmark()" class="rounded-xl bg-white border border-gray-300 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-amber-50 hover:text-amber-900 transition flex items-center gap-1.5 shadow-sm">
                            <span id="bookmarkDetailIcon">🔖</span> <span id="bookmarkDetailText">Bookmark</span>
                        </button>

                        <!-- Ask AI Button -->
                        <a href="/chat?q={{ urlencode('Tell me about the thesis: ' . $document->title) }}" class="rounded-xl bg-white border border-gray-300 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-[#700000] hover:text-[#FFD700] hover:border-[#700000] transition flex items-center gap-1.5 shadow-sm">
                            <span>🤖</span> Ask AI About This
                        </a>

                        <!-- View PDF Button -->
                        <a href="/backend/documents/{{ $document->id }}/view" target="_blank" rel="noopener noreferrer" class="rounded-xl bg-white border border-gray-300 px-3.5 py-2 text-xs font-bold text-gray-700 hover:bg-gray-100 transition flex items-center gap-1.5 shadow-sm">
                            <span>📄</span> <span>View PDF</span>
                        </a>

                        <!-- Download PDF Button -->
                        <a href="/backend/documents/{{ $document->id }}/view?download=1" class="rounded-xl bg-[#700000] px-3.5 py-2 text-xs font-bold text-[#FFD700] hover:bg-[#800000] transition flex items-center gap-1.5 shadow-sm">
                            <span>📥</span> <span>Download PDF</span>
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
                            📄 Open PDF File
                        </a>
                    </div>
                    @endif
                </div>

            </article>
        </div>
    </main>

    <!-- IEEE Citation Modal -->
    <div id="citationModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <h3 class="text-base font-bold text-gray-900">📝 IEEE Citation</h3>
                <button onclick="closeCitationModal()" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100">✕</button>
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
                <button onclick="closeCitationModal()" class="rounded-xl px-4 py-2 text-xs font-bold text-gray-600">Close</button>
                <button onclick="copyCitation()" id="copyBtn" class="rounded-xl bg-[#700000] px-5 py-2.5 text-xs font-bold text-[#FFD700] hover:bg-[#800000]">
                    📋 Copy Citation
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
                const btn = document.getElementById('copyBtn');
                btn.textContent = '✓ Copied!';
                setTimeout(() => {
                    btn.textContent = '📋 Copy Citation';
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
            const text = document.getElementById('bookmarkDetailText');
            if (isSaved) {
                btn.className = "rounded-xl bg-amber-50 border border-amber-300 px-4 py-2 text-xs font-bold text-amber-900 transition flex items-center gap-1.5 shadow-sm";
                text.textContent = "Saved";
            } else {
                btn.className = "rounded-xl bg-white border border-gray-300 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-amber-50 hover:text-amber-900 transition flex items-center gap-1.5 shadow-sm";
                text.textContent = "Bookmark";
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
                    body: JSON.stringify({
                        document_id: currentDocId
                    })
                });
                const data = await res.json();
                updateBookmarkBtnState(data.bookmarked);
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