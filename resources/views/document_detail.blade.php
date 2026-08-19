<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $document->title }} - SAC Thesis Repository</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-50 text-slate-800 font-sans">

    @include('partials.sidebar')

    <!-- Hidden Data Attributes for Safe JavaScript Access -->
    <div id="citationData"
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

                <!-- Badges -->
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <span class="rounded-lg bg-[#700000]/10 text-[#700000] border border-[#700000]/20 px-3 py-1 text-xs font-bold">
                        St. Anthony's College
                    </span>
                    <span class="rounded-lg bg-green-50 text-green-700 border border-green-200 px-3 py-1 text-xs font-bold flex items-center gap-1">
                        ✓ Full Text Online
                    </span>
                    <span class="rounded-lg bg-slate-100 text-gray-600 border border-gray-200 px-3 py-1 text-xs font-semibold">
                        {{ $document->created_at ? $document->created_at->format('F Y') : 'Recent' }}
                    </span>
                </div>

                <!-- Title -->
                <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 leading-tight">
                    {{ $document->title }}
                </h1>

                <!-- Authors -->
                <div class="mt-3 flex flex-wrap items-center gap-y-1 gap-x-4 text-xs md:text-sm text-gray-600 border-b border-gray-100 pb-5">
                    <p>
                        <span class="font-bold text-[#700000]">Author(s):</span>
                        <span class="font-semibold text-gray-800">{{ $document->author }}</span>
                    </p>
                </div>

                <!-- ProQuest Action Bar -->
                <div class="my-6 flex flex-wrap items-center justify-between gap-3 bg-slate-50 border border-gray-200 rounded-2xl p-3 md:p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Cite Button -->
                        <button onclick="openCitationModal()" class="rounded-xl bg-white border border-gray-300 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-[#700000] hover:text-[#FFD700] hover:border-[#700000] transition flex items-center gap-1.5 shadow-sm">
                            <span>📝</span> Cite This Thesis
                        </button>

                        <!-- Ask AI Button -->
                        <a href="/chat?q={{ urlencode('Tell me about the thesis: ' . $document->title) }}" class="rounded-xl bg-white border border-gray-300 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-[#700000] hover:text-[#FFD700] hover:border-[#700000] transition flex items-center gap-1.5 shadow-sm">
                            <span>🤖</span> Ask AI About This
                        </a>

                        <!-- Copy Link -->
                        <button onclick="copyShareLink()" id="shareBtn" class="rounded-xl bg-white border border-gray-300 px-3.5 py-2 text-xs font-bold text-gray-700 hover:bg-gray-100 transition flex items-center gap-1.5 shadow-sm">
                            <span>🔗</span> <span id="shareBtnText">Share Link</span>
                        </button>
                    </div>

                    <!-- View / Download PDF -->
                    <a href="/backend/documents/{{ $document->id }}/view" target="_blank" class="rounded-xl bg-[#700000] px-5 py-2 text-xs font-bold text-[#FFD700] hover:bg-[#800000] transition shadow-md flex items-center gap-2">
                        <span>📄</span> View Original PDF
                    </a>
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
                        <h2 class="text-sm font-bold uppercase tracking-wider text-[#700000] mb-2">Abstract</h2>
                        <div class="rounded-2xl bg-slate-50 border border-gray-200 p-5 text-sm text-gray-700 leading-relaxed font-sans text-center">
                            {!! nl2br(e(preg_replace('/^[ \t]+/m', '', $document->abstract))) !!}
                        </div>
                    </div>
                </div>

                <!-- Full Text Tab Content -->
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

                            <!-- Page Number Label -->
                            <div class="mb-4 flex items-center justify-between border-b border-gray-100 pb-2">
                                <span class="rounded-md bg-slate-100 px-2.5 py-1 text-xs font-bold text-[#700000]">
                                    Page {{ $chunk->page_number ?? $loop->iteration }}
                                </span>
                            </div>

                            <!-- Formatted Paragraph Blocks -->
                            <div class="space-y-4">
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

    <!-- Citation Modal -->
    <div id="citationModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <h3 class="text-base font-bold text-gray-900">📝 Cite This Thesis</h3>
                <button onclick="closeCitationModal()" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100">✕</button>
            </div>
            <div class="mt-4">
                <div class="flex rounded-xl bg-slate-100 p-1 border border-gray-200">
                    <button id="tabAPA" onclick="selectCitationFormat('APA')" class="flex-1 py-1.5 text-xs font-bold rounded-lg bg-white text-[#700000] shadow-sm">APA 7th</button>
                    <button id="tabMLA" onclick="selectCitationFormat('MLA')" class="flex-1 py-1.5 text-xs font-bold rounded-lg text-gray-500">MLA 9th</button>
                    <button id="tabChicago" onclick="selectCitationFormat('Chicago')" class="flex-1 py-1.5 text-xs font-bold rounded-lg text-gray-500">Chicago</button>
                    <button id="tabIEEE" onclick="selectCitationFormat('IEEE')" class="flex-1 py-1.5 text-xs font-bold rounded-lg text-gray-500">IEEE</button>
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

        const dataElement = document.getElementById('citationData');
        const docTitle = dataElement ? dataElement.getAttribute('data-title') : 'Untitled Thesis';
        const docAuthor = dataElement ? dataElement.getAttribute('data-author') : 'Unknown Author';
        const docYear = dataElement ? dataElement.getAttribute('data-year') : '2025';
        let currentFormat = 'APA';

        function openCitationModal() {
            selectCitationFormat('APA');
            document.getElementById('citationModal').classList.remove('hidden');
            document.getElementById('citationModal').classList.add('flex');
        }

        function closeCitationModal() {
            document.getElementById('citationModal').classList.add('hidden');
            document.getElementById('citationModal').classList.remove('flex');
        }

        function selectCitationFormat(format) {
            currentFormat = format;
            ['APA', 'MLA', 'Chicago', 'IEEE'].forEach(f => {
                const el = document.getElementById(`tab${f}`);
                if (el) {
                    el.className = f === format ?
                        "flex-1 py-1.5 text-xs font-bold rounded-lg bg-white text-[#700000] shadow-sm transition" :
                        "flex-1 py-1.5 text-xs font-bold rounded-lg text-gray-500 hover:text-gray-900 transition";
                }
            });

            let text = '';
            if (format === 'APA') text = `${docAuthor} (${docYear}). ${docTitle} (Undergraduate thesis). St. Anthony's College.`;
            else if (format === 'MLA') text = `${docAuthor}. "${docTitle}." Undergraduate thesis, St. Anthony's College, ${docYear}.`;
            else if (format === 'Chicago') text = `${docAuthor}. "${docTitle}." Undergraduate thesis, St. Anthony's College, ${docYear}.`;
            else if (format === 'IEEE') text = `${docAuthor}, "${docTitle}," Undergraduate thesis, St. Anthony's College, ${docYear}.`;

            document.getElementById('citationText').textContent = text;
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

        function copyShareLink() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                const text = document.getElementById('shareBtnText');
                text.textContent = 'Copied!';
                setTimeout(() => {
                    text.textContent = 'Share Link';
                }, 2000);
            });
        }
    </script>
</body>

</html>