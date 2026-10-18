<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Submit Manuscript - AIRIS</title>

    <!-- Supabase JS -->
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>

    <!-- PDF.js for client-side text chunking -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        if (window.pdfjsLib) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        }
    </script>

    <script>
        const supabaseUrl = "{{ config('services.supabase.url', env('SUPABASE_URL')) }}";
        const supabaseKey = "{{ config('services.supabase.key', env('SUPABASE_PUBLISHABLE_KEY')) }}";
        const supabaseClient = (window.supabase && supabaseUrl && supabaseKey)
            ? window.supabase.createClient(supabaseUrl, supabaseKey)
            : null;
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="{{ asset('images/airis-logo.webp') }}" type="image/webp">

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

    {{-- SAC PORTAL TOP HEADER --}}
    @include('partials.header', ['title' => 'SUBMIT MANUSCRIPT'])

    @include('partials.sidebar')

    <main class="md:ml-64 min-h-screen p-4 sm:p-6 md:p-10 transition-all pt-20 md:pt-28">
        <div class="mx-auto max-w-6xl space-y-6">

            <!-- Top Sub-Header & Breadcrumb -->
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
                </div>
            </div>

            @if($submissions->isNotEmpty())
                @php
                    $latestSub = $submissions->first();
                    $latestCleared = in_array($latestSub->status, ['cleared', 'approved'], true);
                @endphp

                @if($latestCleared)
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-start sm:items-center gap-3.5 min-w-0">
                            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200/60 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-sm font-semibold text-gray-900 truncate">
                                    {{ $latestSub->title }}
                                </h4>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Cleared for defense. Plagiarism and formatting screening passed.
                                </p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-medium border border-emerald-200 shrink-0">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            Cleared
                        </span>
                    </div>
                @elseif($latestSub->status === 'pending')
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start sm:items-center gap-3.5 min-w-0">
                            <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 border border-amber-200/60 flex items-center justify-center shrink-0">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-sm font-semibold text-gray-900 truncate">
                                    {{ $latestSub->title }}
                                </h4>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Submitted for plagiarism review. You will be notified once reviewed.
                                </p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 text-xs font-medium border border-amber-200/80 shrink-0 self-start sm:self-center">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            Pending
                        </span>
                    </div>
                @endif
            @endif

            @php
                $activeResubmitDoc = $submissions->first(fn($s) => $s->status === 'resubmit');
            @endphp

            <!-- Two-Column Layout (Form on Left, My Submissions on Right) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- Submission Form Column (7 cols) -->
                <div class="lg:col-span-7 space-y-6">
                    <form id="studentUploadForm" class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 shadow-xs space-y-5"
                        data-resubmit-dept="{{ strtolower($activeResubmitDoc->department ?? '') }}"
                        data-resubmit-course="{{ strtolower($activeResubmitDoc->course_code ?? '') }}">
                        <input type="hidden" id="submission_id" name="submission_id" hidden class="hidden" value="{{ $activeResubmitDoc ? $activeResubmitDoc->id : '' }}">

                        <!-- Research Title -->
                        <div class="!mt-0">
                            <label for="title" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                Research Title
                            </label>
                            <input
                                type="text"
                                id="title"
                                name="title"
                                required
                                value="{{ $activeResubmitDoc ? $activeResubmitDoc->title : '' }}"
                                placeholder="Enter Title"
                                class="w-full rounded-xl border border-gray-200 bg-slate-50/60 px-4 py-3 text-sm text-gray-900 outline-none focus:border-[#700000] focus:bg-white focus:ring-2 focus:ring-[#700000]/10 transition shadow-2xs font-medium">
                        </div>

                        <!-- Author(s) -->
                        <div>
                            <label for="author" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                Author(s)
                            </label>
                            <input
                                type="text"
                                id="author"
                                name="author"
                                required
                                value="{{ $activeResubmitDoc ? $activeResubmitDoc->author : '' }}"
                                placeholder="Enter Author(s)"
                                class="w-full rounded-xl border border-gray-200 bg-slate-50/60 px-4 py-2.5 text-sm text-gray-900 outline-none focus:border-[#700000] focus:bg-white focus:ring-2 focus:ring-[#700000]/10 transition shadow-2xs">
                            <p class="text-[11px] text-gray-400 mt-1">Separate multiple authors with commas.</p>
                        </div>

                        <!-- Academic Department & Degree Program -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="department" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Department
                                </label>
                                <select
                                    id="department"
                                    name="department"
                                    required
                                    onchange="handleDepartmentChange()"
                                    class="w-full rounded-xl border border-gray-200 bg-slate-50/60 px-3.5 py-2.5 text-xs sm:text-sm text-gray-900 outline-none focus:border-[#700000] focus:bg-white focus:ring-2 focus:ring-[#700000]/10 transition shadow-2xs font-semibold cursor-pointer">
                                    <option value="" disabled {{ !$activeResubmitDoc ? 'selected' : '' }}>Select Department</option>
                                    <option value="bused">Business Education Department</option>
                                    <option value="cjed">Criminal Justice Education Department</option>
                                    <option value="dte">Department of Teacher Education</option>
                                    <option value="eng">Engineering Department</option>
                                    <option value="itd">Information Technology Department</option>
                                    <option value="lad">Liberal Arts Department</option>
                                    <option value="nursing">Nursing Department</option>
                                </select>
                            </div>
                            <div>
                                <label for="course_code" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Degree Program
                                </label>
                                <select
                                    id="course_code"
                                    name="course_code"
                                    required
                                    class="w-full rounded-xl border border-gray-200 bg-slate-50/60 px-3.5 py-2.5 text-xs sm:text-sm text-gray-900 outline-none focus:border-[#700000] focus:bg-white focus:ring-2 focus:ring-[#700000]/10 transition shadow-2xs font-semibold cursor-pointer">
                                    <option value="" disabled selected>Select Program</option>
                                </select>
                            </div>
                        </div>

                        <!-- Abstract -->
                        <div>
                            <label for="abstract" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                Abstract
                            </label>
                            <textarea
                                id="abstract"
                                name="abstract"
                                rows="4"
                                required
                                placeholder="Paste your complete research abstract here..."
                                class="w-full rounded-xl border border-gray-200 bg-slate-50/60 p-4 text-xs sm:text-sm text-gray-900 outline-none focus:border-[#700000] focus:bg-white focus:ring-2 focus:ring-[#700000]/10 transition shadow-2xs leading-relaxed">{{ $activeResubmitDoc ? $activeResubmitDoc->abstract : '' }}</textarea>
                        </div>

                        <!-- PDF Manuscript File Upload Dropzone -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                 Upload PDF File
                            </label>
                            
                            <div
                                id="dropzoneContainer"
                                onclick="document.getElementById('pdf').click()"
                                class="border-2 border-dashed border-gray-300 hover:border-[#700000] rounded-2xl p-6 text-center cursor-pointer bg-slate-50/50 hover:bg-slate-100/70 transition group">
                                <input
                                    type="file"
                                    id="pdf"
                                    name="pdf"
                                    accept="application/pdf"
                                    class="hidden"
                                    onchange="handleFileSelected(this.files[0])">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 group-hover:bg-[#700000] text-[#700000] group-hover:text-[#FFD700] flex items-center justify-center transition mb-3 shadow-2xs">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                        </svg>
                                    </div>
                                    <p class="text-xs sm:text-sm font-semibold text-gray-800">
                                        Click or drag PDF manuscript file here
                                    </p>
                                    <p class="text-[11px] text-gray-400 mt-1">
                                        Supports standard PDF documents up to 50 MB
                                    </p>
                                </div>
                            </div>

                            <!-- Selected File Badge Preview -->
                            <div id="filePreviewCard" class="hidden items-center justify-between rounded-xl bg-[#700000]/5 border border-[#700000]/20 p-3 mt-3">
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

                            <!-- Auto-fill Status Notification -->
                            <div id="autofillStatus" class="hidden text-xs rounded-xl px-3.5 py-2 mt-2.5 transition"></div>
                        </div>

                        <!-- Upload Progress Bar Container -->
                        <div id="progressContainer" class="space-y-2 pt-2">
                            <div class="flex justify-between text-xs font-bold text-gray-700">
                                <span id="progressText">Extracting text & uploading...</span>
                                <span id="progressPercent" class="font-mono text-[#700000]">0%</span>
                            </div>
                            <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100 border border-gray-200">
                                <div id="progressBar" class="h-full bg-gradient-to-r from-[#700000] to-[#FFD700] transition-all duration-300 w-0"></div>
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
                            <span>Submit Manuscript</span>
                        </button>
                    </form>
                </div>

                <!-- My Submissions History Column (5 cols) -->
                <div class="lg:col-span-5 space-y-4">
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 shadow-xs">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#700000]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="text-sm font-bold text-gray-900">My Submissions</h3>
                            </div>
                        </div>

                        @if($submissions->isEmpty())
                            <div class="py-10 text-center text-gray-400 text-xs">
                                <p>You haven't submitted any manuscripts yet.</p>
                                <p class="mt-1 text-[11px] text-gray-400">Fill out the form to submit your research for screening.</p>
                            </div>
                        @else
                            <div class="space-y-3.5 max-h-[600px] overflow-y-auto pr-1">
                                @foreach($submissions as $sub)
                                    @php
                                        $isCleared = in_array($sub->status, ['cleared', 'approved'], true);
                                        $isResubmit = ($sub->status === 'resubmit');

                                        $statusBadge = $isCleared
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                            : ($isResubmit ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200');

                                        $statusLabel = $isCleared
                                            ? 'Approved'
                                            : ($isResubmit ? 'Needs Revision' : 'Pending');
                                    @endphp

                                    <div class="rounded-xl border border-gray-200 bg-slate-50/50 p-3.5 sm:p-4 space-y-2 hover:bg-white hover:border-gray-300 transition">
                                        <div class="flex items-start justify-between gap-2">
                                            <h4 class="font-semibold text-xs text-gray-900 leading-snug line-clamp-2">
                                                {{ $sub->title }}
                                            </h4>
                                            <span class="shrink-0 rounded-md border px-2 py-0.5 text-[10px] font-medium {{ $statusBadge }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </div>

                                        <p class="text-[11px] text-gray-500">
                                            Author: <span class="text-gray-700">{{ $sub->author }}</span>
                                        </p>
                                        
                                        <p class="text-[10px] text-gray-400">
                                            Submitted: {{ $sub->created_at ? $sub->created_at->format('M d, Y') : 'N/A' }}
                                        </p>

                                        @if($isCleared)
                                            <div class="mt-2 rounded-lg bg-emerald-50/50 border border-emerald-200/80 p-2.5 text-xs text-emerald-900 space-y-1.5">
                                                <div class="flex items-center justify-between">
                                                    <p class="font-semibold text-xs flex items-center gap-1.5 text-emerald-700">
                                                        <svg class="w-3.5 h-3.5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                        </svg>
                                                        <span>Cleared for Oral Defense &bull; Published</span>
                                                    </p>
                                                    @if($sub->turnitin_similarity)
                                                        <span class="font-mono text-[10px] bg-emerald-100 text-emerald-900 px-1.5 py-0.5 rounded font-semibold border border-emerald-200">
                                                            {{ $sub->turnitin_similarity }}
                                                        </span>
                                                    @endif
                                                </div>
                                                @if(!empty($sub->admin_notes))
                                                    <div class="bg-white rounded p-2 border border-emerald-100 text-[11px] text-gray-700">
                                                        <span class="font-semibold text-emerald-800 block text-[10px] uppercase tracking-wider mb-0.5">Feedback:</span>
                                                        <span class="whitespace-pre-line">{{ $sub->admin_notes }}</span>
                                                    </div>
                                                @endif
                                                <div class="pt-1 border-t border-emerald-200/60 flex items-center justify-between">
                                                    <a href="/documents/{{ $sub->id }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-[#700000] hover:text-[#850000] hover:underline">
                                                        <span>View Published in Repository</span>
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                                    </a>
                                                    <span class="text-[10px] text-emerald-700 font-medium">Publicly Live</span>
                                                </div>
                                            </div>
                                        @elseif($isResubmit)
                                            <div class="mt-2 rounded-lg bg-rose-50/50 border border-rose-200/80 p-2.5 text-xs text-rose-900 space-y-1.5">
                                                <div class="flex items-center justify-between">
                                                    <p class="font-semibold text-xs flex items-center gap-1.5 text-rose-700">
                                                        <svg class="w-3.5 h-3.5 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                                        </svg>
                                                        <span>Revisions Required</span>
                                                    </p>
                                                    @if($sub->turnitin_similarity)
                                                        <span class="font-mono text-[10px] bg-rose-100 text-rose-900 px-1.5 py-0.5 rounded font-semibold border border-rose-200">
                                                            {{ $sub->turnitin_similarity }}
                                                        </span>
                                                    @endif
                                                </div>
                                                @if(!empty($sub->admin_notes))
                                                    <div class="bg-white rounded p-2 border border-rose-100 text-[11px] text-gray-700">
                                                        <span class="font-semibold text-rose-800 block text-[10px] uppercase tracking-wider mb-0.5">Reviewer Feedback:</span>
                                                        <span class="whitespace-pre-line select-all font-medium text-rose-950">{{ $sub->admin_notes }}</span>
                                                    </div>
                                                @endif
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
            <h3 class="text-lg font-black text-gray-900">Submission Successfully Uploaded!</h3>
            <p id="successModalMessage" class="text-xs text-gray-600 leading-relaxed">
                Your manuscript is being checked for plagiarism and grammar. You will be notified once the review is completed.
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
            'bused': [
                { code: 'bsa', name: 'Bachelor of Science in Accountancy (BSA)' },
                { code: 'bsais', name: 'Bachelor of Science in Accounting Information System (BSAIS)' },
                { code: 'ba', name: 'Business Research (BA)' },
                { code: 'bshm', name: 'Bachelor of Science in Hospitality Management (BSHM)' }
            ],
            'cjed': [
                { code: 'bscrim', name: 'Bachelor of Science in Criminology (BSCrim)' }
            ],
            'dte': [
                { code: 'bsed_english', name: 'Bachelor of Secondary Education Major in English' },
                { code: 'bsed_math', name: 'Bachelor of Secondary Education Major in Mathematics' },
                { code: 'bsed_science', name: 'Bachelor of Secondary Education Major in Science' },
                { code: 'beed', name: 'Bachelor of Elementary Education (BEED)' }
            ],
            'eng': [
                { code: 'bsce', name: 'Bachelor of Science in Civil Engineering (BSCE)' },
                { code: 'bscpe', name: 'Bachelor of Science in Computer Engineering (BSCpE)' }
            ],
            'itd': [
                { code: 'bsit', name: 'Bachelor of Science in Information Technology (BSIT)' }
            ],
            'lad': [
                { code: 'ab_philo', name: 'Bachelor of Arts in Philosophy (AB Philosophy)' }
            ],
            'nursing': [
                { code: 'bsn', name: 'Bachelor of Science in Nursing (BSN)' }
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

        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('studentUploadForm');
            const dept = form ? (form.dataset.resubmitDept || '') : '';
            const course = form ? (form.dataset.resubmitCourse || '') : '';
            if (dept) {
                const deptSelect = document.getElementById('department');
                if (deptSelect) {
                    deptSelect.value = dept;
                    handleDepartmentChange();
                    if (course) {
                        const courseSelect = document.getElementById('course_code');
                        if (courseSelect) courseSelect.value = course;
                    }
                }
            }
        });

        function handleFileSelected(file) {
            if (!file) return;
            document.getElementById('previewFileName').textContent = file.name;
            document.getElementById('previewFileSize').textContent = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
            document.getElementById('filePreviewCard').classList.remove('hidden');
            document.getElementById('filePreviewCard').classList.add('flex');

            // Automatically extract Title, Author, Abstract, Department, Course
            autoExtractMetadataFromFile(file);
        }

        function clearSelectedFile(e) {
            e.stopPropagation();
            document.getElementById('pdf').value = '';
            document.getElementById('filePreviewCard').classList.add('hidden');
            document.getElementById('filePreviewCard').classList.remove('flex');
            const statusBadge = document.getElementById('autofillStatus');
            if (statusBadge) {
                statusBadge.classList.add('hidden');
            }
        }

        async function autoExtractMetadataFromFile(file) {
            if (!file) return;
            const statusBadge = document.getElementById('autofillStatus');
            if (statusBadge) {
                statusBadge.className = "flex items-center gap-2 text-xs rounded-xl px-3.5 py-2 mt-2.5 bg-amber-50 border border-amber-200 text-amber-800 transition";
                statusBadge.innerHTML = `
                    <svg class="w-3.5 h-3.5 animate-spin text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Reading manuscript PDF and extracting details...</span>
                `;
                statusBadge.classList.remove('hidden');
            }

            try {
                const arrayBuffer = await file.arrayBuffer();
                const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
                const pagesToScan = Math.min(pdf.numPages, 3);
                let frontText = '';

                for (let i = 1; i <= pagesToScan; i++) {
                    const page = await pdf.getPage(i);
                    const content = await page.getTextContent();
                    const pageStr = content.items.map(item => item.str).join(' ');
                    frontText += `\n--- PAGE ${i} ---\n` + pageStr;
                }

                if (frontText.trim().length > 30) {
                    const res = await fetch('/backend/student/extract-metadata', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ text: frontText })
                    });

                    if (res.ok) {
                        const json = await res.json();
                        if (json.success && json.data) {
                            const d = json.data;
                            const titleInput = document.getElementById('title');
                            const authorInput = document.getElementById('author');
                            const abstractInput = document.getElementById('abstract');
                            const deptSelect = document.getElementById('department');
                            const courseSelect = document.getElementById('course_code');

                            let filledFields = [];
                            if (d.title) {
                                titleInput.value = d.title;
                                filledFields.push('Title');
                            }
                            if (d.author) {
                                authorInput.value = d.author;
                                filledFields.push('Author(s)');
                            }
                            if (d.department) {
                                deptSelect.value = d.department;
                                handleDepartmentChange();
                                if (d.course_code) {
                                    courseSelect.value = d.course_code;
                                }
                            }
                            if (d.abstract) {
                                abstractInput.value = d.abstract;
                                filledFields.push('Abstract');
                            }

                            if (statusBadge && filledFields.length > 0) {
                                statusBadge.className = "flex items-center justify-between text-xs rounded-xl px-3.5 py-2 mt-2.5 bg-emerald-50 border border-emerald-200 text-emerald-800 transition";
                                statusBadge.innerHTML = `
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                        <span>Auto-filled ${filledFields.join(', ')} from PDF. Please verify before submitting.</span>
                                    </div>
                                    <button type="button" onclick="this.parentElement.classList.add('hidden')" class="text-emerald-600 hover:text-emerald-900 font-bold ml-2">✕</button>
                                `;
                                return;
                            }
                        }
                    }
                }
                if (statusBadge) statusBadge.classList.add('hidden');
            } catch (err) {
                console.warn('PDF auto-extract skipped:', err);
                if (statusBadge) statusBadge.classList.add('hidden');
            }
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
                showError('Please select your manuscript PDF file.');
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
                        submission_id: document.getElementById('submission_id') ? document.getElementById('submission_id').value : '',
                        title: document.getElementById('title').value.trim(),
                        author: document.getElementById('author').value.trim(),
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
                    <span>Submit Manuscript for Review</span>
                `;
            }
        });
    </script>
</body>

</html>
