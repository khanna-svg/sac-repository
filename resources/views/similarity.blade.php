<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thesis Proposal Similarity Checker - SAC Institutional Repository</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="min-h-screen bg-slate-50 text-slate-800 font-sans">

    @include('partials.sidebar')

    <main id="mainContent" class="md:ml-64 min-h-screen p-4 sm:p-6 md:p-10 transition-all duration-300 ease-in-out pt-16 md:pt-10">
        <div class="mx-auto max-w-5xl">

            <section class="mb-6 md:mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <div class="w-10 h-10 rounded-2xl bg-[#700000] text-[#FFD700] flex items-center justify-center shrink-0 shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-2xl md:text-3xl font-bold text-[#700000]">
                                    Thesis Proposal Similarity Checker
                                </h1>
                                <p class="mt-0.5 text-xs md:text-sm text-gray-500">
                                    Evaluate topic originality and detect prior work in the SAC repository before your thesis defense.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mb-8 rounded-3xl border border-gray-200 bg-white p-6 md:p-8 shadow-sm">
                <form id="proposalForm" onsubmit="handleSimilarityCheck(event)" class="space-y-5">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <div class="lg:col-span-2">
                            <label for="proposalTitle" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                                Proposed Thesis / Capstone Title <span class="text-rose-600">*</span>
                            </label>
                            <input
                                id="proposalTitle"
                                type="text"
                                required
                                placeholder="e.g., IoT-Based Automated Water Monitoring and Drowning Detection System"
                                class="w-full rounded-2xl border border-gray-300 bg-slate-50/50 px-4 py-3 text-xs sm:text-sm text-gray-800 placeholder-gray-400 outline-none focus:border-[#700000] focus:bg-white focus:ring-1 focus:ring-[#700000] transition">
                        </div>

                        <div>
                            <label for="proposalDepartment" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                                Target Department / Discipline
                            </label>
                            <select
                                id="proposalDepartment"
                                class="w-full rounded-2xl border border-gray-300 bg-slate-50/50 px-3.5 py-3 text-xs sm:text-sm font-medium text-gray-700 outline-none focus:border-[#700000] focus:bg-white focus:ring-1 focus:ring-[#700000] transition">
                                <option value="all">All Departments</option>
                                <option value="it">Information Technology (BSIT)</option>
                                <option value="marine">Marine Engineering (BSMarE)</option>
                                <option value="nursing">Nursing & Healthcare (BSN)</option>
                                <option value="hospitality">Hospitality Management (BSHM)</option>
                                <option value="business">Business & Accountancy (CBA)</option>
                                <option value="education">Teacher Education (CTE)</option>
                                <option value="criminology">Criminology / Arts & Sciences</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="proposalAbstract" class="block text-xs font-bold uppercase tracking-wider text-gray-700">
                                Proposed Abstract / Problem Statement / Scope
                            </label>
                            <span id="charCounter" class="text-[11px] text-gray-400 font-medium">0 characters</span>
                        </div>
                        <textarea
                            id="proposalAbstract"
                            rows="5"
                            placeholder="Paste your proposal summary, problem statement, key methodologies, or target scope here (minimum 20-30 words recommended for accurate semantic vector matching)..."
                            class="w-full rounded-2xl border border-gray-300 bg-slate-50/50 p-4 text-xs sm:text-sm text-gray-800 placeholder-gray-400 outline-none focus:border-[#700000] focus:bg-white focus:ring-1 focus:ring-[#700000] transition leading-relaxed"></textarea>
                    </div>

                        <button
                            id="submitBtn"
                            type="submit"
                            class="w-full sm:w-auto rounded-2xl bg-[#700000] px-8 py-3.5 text-xs sm:text-sm font-bold text-[#FFD700] hover:bg-[#850000] transition shadow-md flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                            <svg id="btnIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                            <span id="btnText">Check Proposal Similarity</span>
                        </button>
                    </div>
                </form>
            </section>

            <div id="loadingState" class="hidden mb-8 rounded-3xl border border-gray-200 bg-white p-10 text-center shadow-sm">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-[#700000] mb-4">
                    <svg class="w-7 h-7 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900">Comparing Proposal against SAC Repository</h3>
                <p class="mt-1 text-xs text-gray-500 max-w-md mx-auto leading-relaxed">
                    Loading.. Please wait while we analyze your proposed thesis title and abstract against the SAC Institutional Repository for potential overlaps, prior work, and originality assessment. This may take a few moments depending on the length of your abstract and the current server load.
                </p>
            </div>

            <section id="resultsSection" class="hidden space-y-6">

                <div id="verdictCard" class="rounded-3xl border p-6 md:p-7 shadow-sm transition">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-1.5">
                            <span id="verdictBadge" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold uppercase tracking-wider"></span>
                            <h2 id="verdictTitle" class="text-xl md:text-2xl font-bold text-gray-900"></h2>
                            <p id="verdictDesc" class="text-xs md:text-sm text-gray-600 max-w-2xl leading-relaxed"></p>
                        </div>
                        <div class="shrink-0 text-left md:text-right border-t md:border-t-0 md:border-l border-gray-200 pt-3 md:pt-0 md:pl-6">
                            <div class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Max Similarity</div>
                            <div id="verdictScore" class="text-3xl md:text-4xl font-extrabold text-[#700000]">0%</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-amber-200/70 bg-gradient-to-br from-amber-50/50 via-white to-amber-50/20 p-6 md:p-7 shadow-sm">
                    <div class="flex items-center gap-2.5 mb-3.5">
                        <div class="w-8 h-8 rounded-xl bg-[#700000] text-[#FFD700] flex items-center justify-center shrink-0 shadow-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">AI Defense Advisory</h3>
                            <p class="text-[11px] text-gray-500">Constructive recommendations to ensure project novelty</p>
                        </div>
                    </div>
                    <div id="aiAdvisoryContent" class="text-xs md:text-sm text-gray-700 leading-relaxed space-y-2 prose prose-sm max-w-none"></div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-[#700000]">
                            Closest Archived Theses in SAC Repository
                        </h3>
                        <span id="matchCountBadge" class="text-xs text-gray-500 font-medium"></span>
                    </div>

                    <div id="matchesContainer" class="space-y-4"></div>
                </div>

            </section>

        </div>
    </main>

    {{-- JAVASCRIPT LOGIC --}}
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const proposalForm = document.getElementById('proposalForm');
        const proposalTitle = document.getElementById('proposalTitle');
        const proposalAbstract = document.getElementById('proposalAbstract');
        const proposalDepartment = document.getElementById('proposalDepartment');
        const charCounter = document.getElementById('charCounter');
        const submitBtn = document.getElementById('submitBtn');
        const btnIcon = document.getElementById('btnIcon');
        const btnText = document.getElementById('btnText');
        const loadingState = document.getElementById('loadingState');
        const resultsSection = document.getElementById('resultsSection');
        const matchesContainer = document.getElementById('matchesContainer');

        // Abstract character counter
        proposalAbstract.addEventListener('input', () => {
            const count = proposalAbstract.value.length;
            charCounter.textContent = `${count} characters`;
        });

        function clearForm() {
            proposalTitle.value = '';
            proposalAbstract.value = '';
            proposalDepartment.value = 'all';
            charCounter.textContent = '0 characters';
            resultsSection.classList.add('hidden');
        }

        function loadSampleProposal() {
            proposalTitle.value = "Smart IoT Water Quality and Drowning Warning System";
            proposalDepartment.value = "it";
            proposalAbstract.value = "This capstone project introduces an automated Internet of Things (IoT) monitoring system for swimming pools and public waters using microcontrollers and video surveillance to detect swimmers in distress and send immediate alerts.";
            charCounter.textContent = `${proposalAbstract.value.length} characters`;
        }

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        }

        async function handleSimilarityCheck(event) {
            event.preventDefault();
            const title = proposalTitle.value.trim();
            const abstract = proposalAbstract.value.trim();
            const department = proposalDepartment.value;

            if (!title) {
                alert('Please enter a proposed thesis title.');
                return;
            }

            // Set loading state
            submitBtn.disabled = true;
            btnIcon.classList.add('animate-spin');
            btnText.textContent = 'Evaluating...';
            loadingState.classList.remove('hidden');
            resultsSection.classList.add('hidden');

            try {
                const response = await fetch('/backend/similarity/check', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        title: title,
                        abstract: abstract,
                        department: department
                    })
                });

                if (response.status === 401) {
                    window.location.href = '/login';
                    return;
                }

                if (!response.ok) {
                    const errData = await response.json().catch(() => ({}));
                    throw new Error(errData.error || `HTTP ${response.status}`);
                }

                const data = await response.json();
                renderResults(data);

            } catch (err) {
                console.error('Similarity check failed:', err);
                alert('Similarity analysis failed: ' + err.message);
            } finally {
                submitBtn.disabled = false;
                btnIcon.classList.remove('animate-spin');
                btnText.textContent = 'Check Proposal Similarity';
                loadingState.classList.add('hidden');
            }
        }

        function renderResults(data) {
            const score = data.overall_score || 0;
            const risk = data.risk_level || 'low';

            // 1. Verdict Card
            const verdictCard = document.getElementById('verdictCard');
            const verdictBadge = document.getElementById('verdictBadge');
            const verdictTitle = document.getElementById('verdictTitle');
            const verdictDesc = document.getElementById('verdictDesc');
            const verdictScore = document.getElementById('verdictScore');

            verdictScore.textContent = `${score}%`;
            verdictTitle.textContent = data.verdict || 'Evaluation Complete';
            verdictDesc.textContent = data.verdict_desc || '';

            if (risk === 'high') {
                verdictCard.className = 'rounded-3xl border border-rose-200 bg-rose-50/50 p-6 md:p-7 shadow-sm transition';
                verdictBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-200';
                verdictBadge.textContent = 'High Overlap Alert';
            } else if (risk === 'moderate') {
                verdictCard.className = 'rounded-3xl border border-amber-200 bg-amber-50/50 p-6 md:p-7 shadow-sm transition';
                verdictBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-200';
                verdictBadge.textContent = 'Moderate Similarity';
            } else {
                verdictCard.className = 'rounded-3xl border border-emerald-200 bg-emerald-50/50 p-6 md:p-7 shadow-sm transition';
                verdictBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-200';
                verdictBadge.textContent = 'High Originality';
            }

            // 2. AI Advisory
            const advisoryContent = document.getElementById('aiAdvisoryContent');
            if (typeof marked !== 'undefined' && data.ai_advisory) {
                advisoryContent.innerHTML = marked.parse(data.ai_advisory);
            } else {
                advisoryContent.textContent = data.ai_advisory || 'No advisory available.';
            }

            // 3. Top Matches List
            const matches = data.top_matches || [];
            document.getElementById('matchCountBadge').textContent = `${matches.length} matching theses reviewed`;

            if (matches.length === 0) {
                matchesContainer.innerHTML = `
                    <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center text-xs text-gray-500">
                        No archived theses found matching your criteria.
                    </div>
                `;
            } else {
                matchesContainer.innerHTML = matches.map((match) => {
                    const pubDateStr = match.publication_date || '';

                    return `
                        <article class="rounded-3xl border border-gray-200 bg-white p-5 md:p-6 shadow-sm hover:border-[#700000]/30 transition space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-500 font-semibold">
                                    <span class="font-bold text-[#700000]">${match.similarity_score}% Similarity</span>
                                    <span class="text-gray-300">•</span>
                                    <span class="text-gray-700 font-bold">${escapeHtml(match.department || 'Academic Research')}</span>
                                    ${pubDateStr ? `<span class="text-gray-300">•</span><span class="text-gray-500 font-medium">${escapeHtml(pubDateStr)}</span>` : ''}
                                </div>
                                <a
                                    href="/documents/${match.id}"
                                    target="_blank"
                                    class="text-xs font-bold text-[#700000] hover:underline flex items-center gap-1">
                                    <span>View Thesis</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                    </svg>
                                </a>
                            </div>

                            <h4 class="text-base font-bold text-gray-900 leading-snug">
                                <a href="/documents/${match.id}" target="_blank" class="hover:text-[#700000] hover:underline">
                                    ${escapeHtml(match.title)}
                                </a>
                            </h4>

                            <p class="text-xs font-medium text-[#700000]">
                                by ${escapeHtml(match.author || 'Unknown Author')}
                            </p>

                            ${match.matched_chunk ? `
                                <div class="mt-2 rounded-2xl bg-slate-50 border border-gray-100 p-3.5 text-xs text-gray-600 leading-relaxed">
                                    <div class="flex items-center gap-1 text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wider">
                                        <svg class="w-3 h-3 text-[#700000]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                                        </svg>
                                        <span>Closest Overlapping Excerpt in Archived Thesis (Page ${match.page_number || 1})</span>
                                    </div>
                                    <p class="italic text-gray-700 font-serif">"${escapeHtml(match.matched_chunk)}"</p>
                                </div>
                            ` : ''}
                        </article>
                    `;
                }).join('');
            }

            resultsSection.classList.remove('hidden');
            resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    </script>
</body>

</html>
