<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $document->title }} - SAC Thesis Repository</title>
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- PDF.js Library for Protected Canvas Rendering (No downloads, no raw text selection) --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
</head>

<body class="min-h-screen bg-slate-50 text-slate-800 font-sans select-none" oncontextmenu="return false;">

    @include('partials.sidebar')

    {{-- Hidden Data Attributes for Safe JavaScript Access --}}
    <div id="citationData"
        data-id="{{ $document->id }}"
        data-title="{{ $document->title }}"
        data-author="{{ $document->author }}"
        data-year="{{ $document->created_at ? $document->created_at->format('Y') : date('Y') }}"
        class="hidden"></div>

    <main class="md:ml-64 min-h-screen p-4 sm:p-6 md:p-10 transition-all pt-16 md:pt-10">
        <div class="mx-auto max-w-4xl">

            {{-- Breadcrumb Navigation --}}
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

            {{-- Main Document Card --}}
            <article class="relative rounded-3xl border border-gray-200 bg-white p-6 md:p-8 shadow-sm">

                {{-- Top-Right Bookmark Button --}}
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

                {{-- Department & Degree Badges --}}
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

                <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1 mb-4 pr-12 text-xs font-semibold text-gray-500">
                    <span class="font-bold text-[#700000]">St. Anthony's College</span>

                    @if(!empty($document->department))
                    <span class="text-gray-300">•</span>
                    <span class="text-gray-700">{{ $departmentNames[$deptKey] ?? $document->department }}</span>
                    @endif

                    @if(!empty($document->course_code))
                    <span class="text-gray-300">•</span>
                    <span class="text-gray-700 font-bold">{{ $courseNames[$courseKey] ?? strtoupper($document->course_code) }}</span>
                    @endif

                    <span class="text-gray-300">•</span>
                    <span class="text-gray-500">{{ $document->created_at ? $document->created_at->format('F Y') : 'Recent' }}</span>
                </div>

                {{-- Book Cover & Title Header Row --}}
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

                {{-- Action Bar --}}
                <div class="my-6 flex flex-wrap items-center justify-between gap-3 bg-slate-50 border border-gray-200 rounded-2xl p-3 md:p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        {{-- Cite Button --}}
                        <button type="button" onclick="openCitationModal()" class="rounded-xl bg-white border border-gray-300 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-[#700000] hover:text-[#FFD700] hover:border-[#700000] transition flex items-center gap-1.5 shadow-sm">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                            </svg>
                            <span>Cite (IEEE)</span>
                        </button>

                        {{-- Ask AI Button (Opens Right-Side AI Drawer) --}}
                        <button
                            type="button"
                            onclick="openAiDrawer()"
                            class="rounded-xl bg-white border border-gray-300 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-[#700000] hover:text-[#FFD700] hover:border-[#700000] transition flex items-center gap-1.5 shadow-sm cursor-pointer">
                            <svg class="w-3.5 h-3.5 shrink-0 text-[#700000]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                            </svg>
                            <span>Ask AI About This</span>
                        </button>

                        {{-- Protected View PDF Button (Opens Secure In-App Reader) --}}
                        <button
                            type="button"
                            onclick="openSecurePdfReader('/backend/documents/{{ $document->id }}/view')"
                            class="rounded-xl bg-[#700000] px-4 py-2 text-xs font-bold text-[#FFD700] hover:bg-[#850000] transition flex items-center gap-1.5 shadow-sm">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                            </svg>
                            <span>View PDF (Protected)</span>
                        </button>
                    </div>
                </div>

                {{-- Tabs: Abstract vs Full Text Content --}}
                <div class="flex border-b border-gray-200 mb-6">
                    <button id="tabAbstractBtn" onclick="switchViewTab('abstract')" class="py-3 px-5 text-sm font-bold border-b-2 border-[#700000] text-[#700000] transition">
                        Abstract
                    </button>
                    <button id="tabFullTextBtn" onclick="switchViewTab('fulltext')" class="py-3 px-5 text-sm font-bold border-b-2 border-transparent text-gray-500 hover:text-gray-800 transition">
                        Full Text Content
                    </button>
                </div>

                {{-- Abstract Tab Content --}}
                <div id="tabAbstractContent" class="space-y-6">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-[#700000] mb-2 text-center">Abstract</h2>
                        <div class="rounded-2xl bg-slate-50 border border-gray-200 p-5 text-sm text-gray-700 leading-relaxed font-sans text-center">
                            {!! nl2br(e(preg_replace('/^[ \t]+/m', '', $document->abstract))) !!}
                        </div>
                    </div>
                </div>

                {{-- Full Text Tab Content (Exact PDF Layout with Protected Canvas Viewer) --}}
                <div id="tabFullTextContent" class="hidden space-y-4 select-none" oncontextmenu="return false;">
                    <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-[#700000]">
                                Full Text Content
                            </h2>
                            <span class="rounded-md bg-red-50 text-[#700000] border border-red-200/60 px-2 py-0.5 text-[10px] font-extrabold uppercase">
                                Protected Read-Only
                            </span>
                        </div>

                        <!-- Zoom Controls & Page Count -->
                        <div class="flex items-center gap-2">
                            <span id="tabPdfPageCount" class="text-xs font-semibold text-gray-500 hidden sm:inline">Loading pages...</span>
                            <div class="flex items-center gap-1 bg-slate-100 rounded-xl px-2 py-1 border border-gray-200 text-xs">
                                <button
                                    type="button"
                                    onclick="zoomTabPdf(-0.2)"
                                    class="p-1 rounded hover:bg-white text-gray-700 transition cursor-pointer"
                                    title="Zoom Out">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                                    </svg>
                                </button>
                                <span id="tabPdfZoomPercent" class="px-1.5 text-[11px] font-mono font-bold text-gray-700">100%</span>
                                <button
                                    type="button"
                                    onclick="zoomTabPdf(0.2)"
                                    class="p-1 rounded hover:bg-white text-gray-700 transition cursor-pointer"
                                    title="Zoom In">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Scrollable Container for Exact PDF Pages -->
                    <div id="tabPdfContainer" class="rounded-3xl border border-gray-200 bg-slate-100/70 p-4 sm:p-6 max-h-[850px] overflow-y-auto flex flex-col items-center gap-6 shadow-inner scroll-smooth">
                        <!-- Loading Spinner -->
                        <div id="tabPdfLoader" class="flex flex-col items-center justify-center py-12 gap-3">
                            <svg class="w-8 h-8 animate-spin text-[#700000]" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-xs font-semibold text-gray-500">Rendering exact PDF manuscript pages...</p>
                        </div>

                        <!-- Rendered Pages List -->
                        <div id="tabPdfPagesWrapper" class="flex flex-col items-center gap-6 w-full max-w-2xl"></div>
                    </div>
                </div>

            </article>
        </div>
    </main>

    {{-- =========================================================
         PROTECTED SECURE PDF READER MODAL (CONTINUOUS SCROLLABLE CANVAS)
    ========================================================== --}}
    <div
        id="securePdfModal"
        class="fixed inset-0 z-50 hidden bg-slate-950/95 backdrop-blur-md flex-col select-none"
        oncontextmenu="return false;">

        {{-- Top Reader Header --}}
        <div class="flex items-center justify-between px-4 md:px-6 py-3 bg-[#500000] text-white border-b border-[#700000] shadow-md shrink-0">
            <div class="flex items-center gap-3 min-w-0 pr-4">
                <span class="rounded bg-amber-400/20 text-[#FFD700] border border-[#FFD700]/30 px-2.5 py-0.5 text-[10px] font-extrabold uppercase shrink-0">
                    Protected Read-Only
                </span>
                <h3 class="text-xs md:text-sm font-bold text-white truncate">
                    {{ $document->title }}
                </h3>
            </div>

            {{-- Reader Controls (Total Pages, Zoom, Close) --}}
            <div class="flex items-center gap-2 shrink-0">
                {{-- Total Pages Badge --}}
                <div class="flex items-center bg-black/40 rounded-xl px-3 py-1 border border-white/10 text-xs">
                    <span id="pageCount" class="text-amber-200 font-mono text-[11px]">Loading...</span>
                </div>

                {{-- Zoom Controls --}}
                <div class="hidden sm:flex items-center gap-1 bg-black/40 rounded-xl px-2 py-1 border border-white/10 text-xs">
                    <button
                        type="button"
                        onclick="onZoomOut()"
                        class="p-1 rounded hover:bg-white/20 text-white"
                        title="Zoom Out">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                        </svg>
                    </button>
                    <span id="zoomPercent" class="px-1 text-[11px] font-mono text-gray-300">100%</span>
                    <button
                        type="button"
                        onclick="onZoomIn()"
                        class="p-1 rounded hover:bg-white/20 text-white"
                        title="Zoom In">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </button>
                </div>

                {{-- Close Reader Button --}}
                <button
                    type="button"
                    onclick="closeSecurePdfReader()"
                    class="rounded-xl p-1.5 bg-white/10 hover:bg-white/20 text-white transition ml-2"
                    title="Close (Esc)">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Security Policy Sub-header --}}
        <div class="bg-black/60 text-amber-200/90 text-[10px] sm:text-xs py-1 px-4 text-center border-b border-white/5 flex items-center justify-center gap-2 shrink-0">
            <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
            <span>St. Anthony's College Protected Document • Copying, printing, and downloading are prohibited by institutional policy.</span>
        </div>

        {{-- Continuous Vertical Scrollable Canvas Container --}}
        <div id="pdfScrollContainer" class="flex-1 overflow-y-auto p-4 md:p-8 flex flex-col items-center relative bg-slate-900 scroll-smooth">
            {{-- Loading Spinner --}}
            <div id="pdfLoader" class="sticky top-20 flex flex-col items-center justify-center gap-3 bg-slate-950/90 p-6 rounded-2xl border border-white/10 z-20 shadow-2xl">
                <svg class="w-8 h-8 animate-spin text-[#FFD700]" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-xs font-semibold text-amber-200">Loading...</p>
            </div>

            {{-- Pages Canvas List --}}
            <div id="pdfPagesWrapper" class="flex flex-col items-center gap-6 w-full max-w-3xl"></div>
        </div>
    </div>

    {{-- =========================================================
         RIGHT-SIDE AI RESEARCH ASSISTANT DRAWER (YOUTUBE / GEMINI STYLE)
    ========================================================== --}}
    <!-- Mobile Backdrop for AI Drawer -->
    <div
        id="aiDrawerBackdrop"
        onclick="closeAiDrawer()"
        class="fixed inset-0 z-50 bg-black/40 opacity-0 pointer-events-none transition-opacity duration-300 md:hidden">
    </div>

    <!-- Right-Side AI Drawer -->
    <aside
        id="aiDrawer"
        class="fixed inset-y-0 right-0 z-50 w-full sm:w-[420px] md:w-[460px] bg-white border-l border-gray-200 shadow-2xl flex flex-col transition-transform duration-300 ease-in-out translate-x-full">
        
        <!-- Drawer Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-white">
            <div class="flex items-center gap-2.5 min-w-0 pr-2">
                <div class="w-8 h-8 rounded-xl bg-[#700000] text-[#FFD700] flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h2 class="text-sm font-bold text-gray-900 truncate">Ask about this thesis</h2>
                    <p class="text-[10px] text-gray-500 truncate">{{ $document->title }}</p>
                </div>
            </div>
            <button
                type="button"
                onclick="closeAiDrawer()"
                aria-label="Close AI Drawer"
                class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Chat Conversation Feed -->
        <div id="aiDrawerMessages" class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4 bg-slate-50/60">
            
            <!-- Initial Greeting & Quick Question Chips (YouTube Style) -->
            <div id="aiInitialCard" class="space-y-3.5">
                <div class="flex items-start gap-2.5">
                    <div class="w-6 h-6 rounded-lg bg-[#700000] text-[#FFD700] flex items-center justify-center shrink-0 text-xs font-bold mt-0.5 shadow-2xs">
                        ✨
                    </div>
                    <div class="flex-1">
                        <p class="text-xs sm:text-sm text-gray-800 leading-relaxed font-medium">
                            Hello! Curious about what you're reading? I'm here to help analyze this thesis.
                        </p>
                        <p class="text-[11px] text-gray-500 mt-2 font-medium">
                            Not sure what to ask? Choose something:
                        </p>
                    </div>
                </div>

                <!-- Quick Prompt Chips -->
                <div class="flex flex-col gap-2 pl-8">
                    <button
                        type="button"
                        onclick="sendQuickQuestion('Summarize this thesis in 3 concise bullet points.')"
                        class="text-left px-3.5 py-2.5 rounded-xl bg-white border border-gray-200 text-xs font-semibold text-gray-700 hover:border-[#700000] hover:text-[#700000] hover:shadow-xs transition cursor-pointer">
                        📑 Summarize this thesis
                    </button>

                    <button
                        type="button"
                        onclick="sendQuickQuestion('What is the main problem and objective of this research?')"
                        class="text-left px-3.5 py-2.5 rounded-xl bg-white border border-gray-200 text-xs font-semibold text-gray-700 hover:border-[#700000] hover:text-[#700000] hover:shadow-xs transition cursor-pointer">
                        🎯 What problem does this study solve?
                    </button>

                    <button
                        type="button"
                        onclick="sendQuickQuestion('What methodology, tools, and technologies were used in this system?')"
                        class="text-left px-3.5 py-2.5 rounded-xl bg-white border border-gray-200 text-xs font-semibold text-gray-700 hover:border-[#700000] hover:text-[#700000] hover:shadow-xs transition cursor-pointer">
                        💻 What methodology and tech stack was used?
                    </button>

                    <button
                        type="button"
                        onclick="sendQuickQuestion('What are the key conclusions, findings, and recommendations of this study?')"
                        class="text-left px-3.5 py-2.5 rounded-xl bg-white border border-gray-200 text-xs font-semibold text-gray-700 hover:border-[#700000] hover:text-[#700000] hover:shadow-xs transition cursor-pointer">
                        📊 What are the conclusions & findings?
                    </button>
                </div>
            </div>

            <!-- Dynamic Messages Thread -->
            <div id="aiDrawerChatThread" class="space-y-4"></div>

            <!-- Typing Indicator -->
            <div id="aiDrawerTyping" class="hidden items-center gap-2 text-xs text-gray-400 pl-2">
                <span class="w-2 h-2 rounded-full bg-[#700000] animate-pulse"></span>
                <span class="w-2 h-2 rounded-full bg-[#700000] animate-pulse delay-150"></span>
                <span class="w-2 h-2 rounded-full bg-[#700000] animate-pulse delay-300"></span>
                <span class="text-[11px] text-gray-500 font-medium ml-1">Gemini is analyzing thesis...</span>
            </div>
        </div>

        <!-- Drawer Input Footer -->
        <div class="p-3 border-t border-gray-200 bg-white">
            <form id="aiDrawerForm" onsubmit="handleAiDrawerSubmit(event)" class="relative flex items-center">
                <input
                    id="aiDrawerInput"
                    type="text"
                    placeholder="Ask a question..."
                    autocomplete="off"
                    class="w-full rounded-2xl border border-gray-300 bg-slate-50 pl-4 pr-12 py-3 text-xs sm:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000] shadow-2xs">
                
                <button
                    id="aiDrawerSendBtn"
                    type="submit"
                    title="Send question"
                    class="absolute right-2 p-2 rounded-xl bg-[#700000] text-[#FFD700] hover:bg-[#850000] transition disabled:opacity-50 cursor-pointer shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                </button>
            </form>
            <p class="text-[9px] text-gray-400 text-center mt-1.5">
                Grounded in St. Anthony's College research • Powered by Gemini
            </p>
        </div>

    </aside>

    {{-- FLOATING TOAST NOTIFICATION POP-UP --}}
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

    {{-- IEEE Citation Modal --}}
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

    {{-- JAVASCRIPT LOGIC --}}
    <script>
        // Set worker source for PDF.js
        if (typeof pdfjsLib !== 'undefined') {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        }

        const dataElement = document.getElementById('citationData');
        const currentDocId = dataElement ? parseInt(dataElement.getAttribute('data-id'), 10) : null;
        const docTitle = dataElement ? dataElement.getAttribute('data-title') : 'Untitled Thesis';
        const docAuthor = dataElement ? dataElement.getAttribute('data-author') : 'Unknown Author';
        const docYear = dataElement ? dataElement.getAttribute('data-year') : new Date().getFullYear().toString();
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        let toastTimeout = null;

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        }

        // Continuous Scrollable PDF Reader state
        let pdfDoc = null;
        let currentScale = 1.3;

        // Open Secure Scrollable Reader Modal
        async function openSecurePdfReader() {
            const modal = document.getElementById('securePdfModal');
            const loader = document.getElementById('pdfLoader');
            const pagesWrapper = document.getElementById('pdfPagesWrapper');

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            loader.classList.remove('hidden');
            pagesWrapper.innerHTML = '';

            try {
                // Fetch temporary signed URL from backend JSON endpoint
                const res = await fetch(`/backend/documents/${currentDocId}/signed-url`);
                if (!res.ok) throw new Error('Could not obtain secure PDF link');
                const data = await res.json();
                if (!data.url) throw new Error('Invalid PDF URL');

                const loadingTask = pdfjsLib.getDocument({
                    url: data.url,
                    cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
                    cMapPacked: true
                });
                pdfDoc = await loadingTask.promise;
                document.getElementById('pageCount').textContent = `${pdfDoc.numPages} Pages`;

                // Render all pages in vertical scroll sequence
                await renderAllPages();
                loader.classList.add('hidden');
            } catch (err) {
                console.error('Error loading secure PDF:', err);
                loader.innerHTML = `
                    <div class="p-6 text-center text-red-400 bg-slate-900 rounded-2xl border border-red-500/30 max-w-sm mx-auto">
                        <p class="font-bold text-sm">Unable to render protected PDF</p>
                        <p class="text-xs text-gray-400 mt-1">You can read the full text content in the tab below.</p>
                        <button onclick="closeSecurePdfReader(); switchViewTab('fulltext');" class="mt-4 px-4 py-2 rounded-xl bg-[#700000] text-[#FFD700] font-bold text-xs">Switch to Full Text Tab</button>
                    </div>
                `;
            }
        }

        // Render all PDF pages vertically as HTML5 Canvas elements
        async function renderAllPages() {
            if (!pdfDoc) return;
            const pagesWrapper = document.getElementById('pdfPagesWrapper');
            pagesWrapper.innerHTML = '';

            for (let num = 1; num <= pdfDoc.numPages; num++) {
                const page = await pdfDoc.getPage(num);
                const viewport = page.getViewport({ scale: currentScale });

                const card = document.createElement('div');
                card.className = 'flex flex-col items-center bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-300 w-full max-w-full';

                const canvas = document.createElement('canvas');
                canvas.className = 'block max-w-full h-auto';
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                const ctx = canvas.getContext('2d');
                const renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };
                await page.render(renderContext).promise;

                const pageFooter = document.createElement('div');
                pageFooter.className = 'w-full py-1.5 bg-slate-100 border-t border-gray-200 text-center text-[10px] sm:text-xs font-semibold text-gray-500 tracking-wider uppercase font-mono';
                pageFooter.textContent = `Page ${num} of ${pdfDoc.numPages}`;

                card.appendChild(canvas);
                card.appendChild(pageFooter);
                pagesWrapper.appendChild(card);
            }

            document.getElementById('zoomPercent').textContent = Math.round((currentScale / 1.3) * 100) + '%';
        }

        // Zoom In (Scales all pages)
        async function onZoomIn() {
            if (currentScale >= 2.5) return;
            currentScale += 0.2;
            const loader = document.getElementById('pdfLoader');
            loader.classList.remove('hidden');
            await renderAllPages();
            loader.classList.add('hidden');
        }

        // Zoom Out (Scales all pages)
        async function onZoomOut() {
            if (currentScale <= 0.7) return;
            currentScale -= 0.2;
            const loader = document.getElementById('pdfLoader');
            loader.classList.remove('hidden');
            await renderAllPages();
            loader.classList.add('hidden');
        }

        function closeSecurePdfReader() {
            const modal = document.getElementById('securePdfModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            pdfDoc = null;
        }

        // Global Keydown Listener (Blocks Copy/Print/Save shortcuts & handles Esc)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && ['c', 'p', 's', 'u'].includes(e.key.toLowerCase())) {
                e.preventDefault();
            }
            if (e.key === 'Escape') {
                closeSecurePdfReader();
                closeCitationModal();
                closeAiDrawer();
            }
        });

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

        // =========================================================
        // FULL TEXT TAB - EMBEDDED REAL PDF VIEWER (EXACT PDF FORMAT)
        // =========================================================
        let tabPdfDoc = null;
        let tabPdfScale = 1.15;
        let tabPdfLoaded = false;

        async function loadTabPdf() {
            if (tabPdfLoaded && tabPdfDoc) return;
            const loader = document.getElementById('tabPdfLoader');
            const pagesWrapper = document.getElementById('tabPdfPagesWrapper');
            const pageCountElem = document.getElementById('tabPdfPageCount');

            if (!loader || !pagesWrapper) return;
            loader.classList.remove('hidden');
            pagesWrapper.innerHTML = '';

            try {
                const res = await fetch(`/backend/documents/${currentDocId}/signed-url`);
                if (!res.ok) throw new Error('Could not obtain PDF link');
                const data = await res.json();
                if (!data.url) throw new Error('Invalid PDF URL');

                const loadingTask = pdfjsLib.getDocument({
                    url: data.url,
                    cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
                    cMapPacked: true
                });
                tabPdfDoc = await loadingTask.promise;
                tabPdfLoaded = true;
                if (pageCountElem) pageCountElem.textContent = `${tabPdfDoc.numPages} Pages`;

                await renderTabPdfPages();
                loader.classList.add('hidden');
            } catch (err) {
                console.error('Error loading tab PDF:', err);
                loader.innerHTML = `
                    <div class="p-6 text-center text-red-500 bg-red-50 rounded-2xl border border-red-200 max-w-sm mx-auto">
                        <p class="font-bold text-sm">Unable to render PDF manuscript pages</p>
                        <p class="text-xs text-gray-500 mt-1">Please try opening the full protected reader.</p>
                        <button onclick="openSecurePdfReader('/backend/documents/${currentDocId}/view')" class="mt-3 px-3.5 py-1.5 rounded-xl bg-[#700000] text-[#FFD700] text-xs font-bold">Open Full Reader</button>
                    </div>
                `;
            }
        }

        async function renderTabPdfPages() {
            if (!tabPdfDoc) return;
            const pagesWrapper = document.getElementById('tabPdfPagesWrapper');
            if (!pagesWrapper) return;
            pagesWrapper.innerHTML = '';

            for (let num = 1; num <= tabPdfDoc.numPages; num++) {
                const page = await tabPdfDoc.getPage(num);
                const viewport = page.getViewport({ scale: tabPdfScale });

                const card = document.createElement('div');
                card.className = 'flex flex-col items-center bg-white shadow-md rounded-2xl overflow-hidden border border-gray-200 w-full max-w-full transition hover:shadow-lg';

                const canvas = document.createElement('canvas');
                canvas.className = 'block max-w-full h-auto';
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                const ctx = canvas.getContext('2d');
                const renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };
                await page.render(renderContext).promise;

                const pageFooter = document.createElement('div');
                pageFooter.className = 'w-full py-2 bg-slate-50 border-t border-gray-100 text-center text-[11px] font-bold text-gray-500 tracking-wider uppercase font-mono';
                pageFooter.textContent = `Page ${num} of ${tabPdfDoc.numPages}`;

                card.appendChild(canvas);
                card.appendChild(pageFooter);
                pagesWrapper.appendChild(card);
            }

            const zoomElem = document.getElementById('tabPdfZoomPercent');
            if (zoomElem) zoomElem.textContent = Math.round((tabPdfScale / 1.15) * 100) + '%';
        }

        async function zoomTabPdf(delta) {
            const newScale = tabPdfScale + delta;
            if (newScale < 0.6 || newScale > 2.2) return;
            tabPdfScale = newScale;
            const loader = document.getElementById('tabPdfLoader');
            if (loader) loader.classList.remove('hidden');
            await renderTabPdfPages();
            if (loader) loader.classList.add('hidden');
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
                loadTabPdf();
            }
        }

        function openCitationModal() {
            const preferredStyle = localStorage.getItem('sac_preferred_citation') || 'ieee';
            let text = '';
            let styleLabel = 'IEEE Style Format';

            if (preferredStyle === 'apa') {
                styleLabel = 'APA 7th Edition Format';
                text = `${docAuthor} (${docYear}). ${docTitle} [Undergraduate thesis, St. Anthony's College]. SAC Institutional Research Repository.`;
            } else if (preferredStyle === 'mla') {
                styleLabel = 'MLA 9th Edition Format';
                text = `${docAuthor}. "${docTitle}." Undergraduate thesis, St. Anthony's College, ${docYear}.`;
            } else {
                styleLabel = 'IEEE Style Format';
                text = `${docAuthor}, "${docTitle}," Undergraduate thesis, St. Anthony's College, ${docYear}.`;
            }

            const styleHeader = document.querySelector('#citationModal .bg-white.text-\\[\\#700000\\]');
            if (styleHeader) styleHeader.textContent = styleLabel;

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

        // =========================================================
        // RIGHT-SIDE AI RESEARCH DRAWER (YOUTUBE / GEMINI STYLE)
        // =========================================================
        function openAiDrawer() {
            const drawer = document.getElementById('aiDrawer');
            const backdrop = document.getElementById('aiDrawerBackdrop');
            if (!drawer || !backdrop) return;

            drawer.classList.remove('translate-x-full');
            backdrop.classList.remove('opacity-0', 'pointer-events-none');
            backdrop.classList.add('opacity-100');

            setTimeout(() => {
                const input = document.getElementById('aiDrawerInput');
                if (input) input.focus();
            }, 250);
        }

        function closeAiDrawer() {
            const drawer = document.getElementById('aiDrawer');
            const backdrop = document.getElementById('aiDrawerBackdrop');
            if (!drawer || !backdrop) return;

            drawer.classList.add('translate-x-full');
            backdrop.classList.remove('opacity-100');
            backdrop.classList.add('opacity-0', 'pointer-events-none');
        }

        async function sendQuickQuestion(questionText) {
            await processAiDrawerQuestion(questionText);
        }

        async function handleAiDrawerSubmit(e) {
            e.preventDefault();
            const input = document.getElementById('aiDrawerInput');
            if (!input) return;
            const question = input.value.trim();
            if (!question) return;
            input.value = '';
            await processAiDrawerQuestion(question);
        }

        async function processAiDrawerQuestion(question) {
            const thread = document.getElementById('aiDrawerChatThread');
            const typing = document.getElementById('aiDrawerTyping');
            const sendBtn = document.getElementById('aiDrawerSendBtn');
            const messagesContainer = document.getElementById('aiDrawerMessages');

            if (!thread || !typing || !sendBtn || !messagesContainer) return;

            // 1. Append user message bubble
            const userBubble = document.createElement('div');
            userBubble.className = 'flex justify-end';
            userBubble.innerHTML = `
                <div class="max-w-[85%] rounded-2xl rounded-tr-xs bg-[#700000] text-white px-4 py-2.5 text-xs sm:text-sm font-medium shadow-sm leading-relaxed">
                    ${escapeHtml(question)}
                </div>
            `;
            thread.appendChild(userBubble);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;

            // 2. Show typing indicator
            typing.classList.remove('hidden');
            typing.classList.add('flex');
            sendBtn.disabled = true;
            messagesContainer.scrollTop = messagesContainer.scrollHeight;

            try {
                const res = await fetch('/backend/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        message: question,
                        document_id: currentDocId
                    })
                });

                const data = await res.json();
                typing.classList.add('hidden');
                typing.classList.remove('flex');
                sendBtn.disabled = false;

                const aiBubble = document.createElement('div');
                aiBubble.className = 'flex items-start gap-2.5';

                let rawAnswer = data.answer || 'I could not generate an answer for this thesis.';
                let formattedAnswer = rawAnswer;

                if (typeof marked !== 'undefined' && marked.parse) {
                    formattedAnswer = marked.parse(rawAnswer);
                } else {
                    formattedAnswer = escapeHtml(rawAnswer).replace(/\n/g, '<br>');
                }

                aiBubble.innerHTML = `
                    <div class="w-6 h-6 rounded-lg bg-[#700000] text-[#FFD700] flex items-center justify-center shrink-0 text-xs font-bold mt-0.5 shadow-2xs">
                        ✨
                    </div>
                    <div class="flex-1 max-w-[90%] bg-white border border-gray-200 rounded-2xl rounded-tl-xs p-3.5 shadow-sm text-xs sm:text-sm text-gray-800 leading-relaxed space-y-2">
                        ${formattedAnswer}
                    </div>
                `;
                thread.appendChild(aiBubble);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;

            } catch (err) {
                console.error('AI Drawer error:', err);
                typing.classList.add('hidden');
                typing.classList.remove('flex');
                sendBtn.disabled = false;

                const errorBubble = document.createElement('div');
                errorBubble.className = 'flex items-start gap-2.5';
                errorBubble.innerHTML = `
                    <div class="w-6 h-6 rounded-lg bg-rose-600 text-white flex items-center justify-center shrink-0 text-xs font-bold mt-0.5">
                        ✕
                    </div>
                    <div class="flex-1 max-w-[90%] bg-rose-50 border border-rose-200 rounded-2xl rounded-tl-xs p-3.5 text-xs text-rose-800 font-medium">
                        Failed to connect to AI Assistant. Please try again.
                    </div>
                `;
                thread.appendChild(errorBubble);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }

        if (currentDocId) {
            checkInitialBookmark(currentDocId);
        }
    </script>
</body>

</html>