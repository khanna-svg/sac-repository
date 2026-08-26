<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Student Preferences & Settings - SAC Thesis Repository</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="https://sac.campus-erp.com/Student/images/sac.png" type="image/png">
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
                <span class="text-gray-400">Student Preferences & Settings</span>
            </nav>

            <!-- Page Title Header -->
            <div class="flex flex-col gap-1 border-b border-gray-200 pb-4">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#700000] tracking-tight">
                    Preferences & Settings
                </h1>
                <p class="text-xs sm:text-sm text-gray-600">
                    Customize your academic department defaults, preferred citation formatting, and repository settings.
                </p>
            </div>

            <!-- Section 1: Verified Student Profile Card -->
            <section class="rounded-3xl border border-gray-200 bg-white p-6 md:p-8 shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-[#700000]/10 border border-[#700000]/20 flex items-center justify-center text-[#700000]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-gray-900">Student Account Profile</h2>
                            <p class="text-xs text-gray-500">St. Anthony's College Institutional Access</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 font-bold text-xs text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-sm">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Verified Student
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div class="p-4 rounded-2xl bg-slate-50 border border-gray-200">
                        <p class="font-bold text-gray-400 uppercase tracking-wider text-[10px]">Institutional Email</p>
                        <p class="text-sm font-bold text-gray-800 mt-1 font-mono">
                            {{ session('sac_user_email') ?? 'student@sac.edu.ph' }}
                        </p>
                        <p class="text-[11px] text-gray-500 mt-1">Authenticated via SAC Email OTP (Passwordless)</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 border border-gray-200">
                        <p class="font-bold text-gray-400 uppercase tracking-wider text-[10px]">Institution</p>
                        <p class="text-sm font-bold text-gray-800 mt-1">
                            St. Anthony's College
                        </p>
                        <p class="text-[11px] text-gray-500 mt-1">San Jose, Antique, Philippines</p>
                    </div>
                </div>
            </section>

            <!-- Section 2: Research & Search Preferences Card -->
            <section class="rounded-3xl border border-gray-200 bg-white p-6 md:p-8 shadow-sm space-y-6">
                <div class="flex items-center gap-3 border-b border-gray-100 pb-4">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Research & Search Preferences</h2>
                        <p class="text-xs text-gray-500">Configure your default search view and citation preferences.</p>
                    </div>
                </div>

                <form id="preferencesForm" onsubmit="savePreferences(event)" class="space-y-6">

                    <!-- Default Academic Department Filter -->
                    <div class="space-y-2">
                        <label for="defaultDept" class="block text-xs font-bold uppercase tracking-wider text-gray-700">
                            Default Academic Department
                        </label>
                        <p class="text-xs text-gray-500">
                            The repository will automatically filter theses to this department when you visit Documents & Search.
                        </p>
                        <select
                            id="defaultDept"
                            class="w-full md:w-96 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-xs font-semibold text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000] shadow-2xs">
                            <option value="">All Departments (Default)</option>
                            <option value="it">Information Technology (BSIT)</option>
                            <option value="marine">Marine Engineering (BSMarE)</option>
                            <option value="nursing">Nursing & Healthcare (BSN)</option>
                            <option value="business">Business & Accountancy (CBA)</option>
                            <option value="education">Teacher Education (CTE)</option>
                            <option value="criminology">Criminology / Arts & Sciences (CAS)</option>
                        </select>
                    </div>

                    <!-- Preferred Citation Format -->
                    <div class="space-y-2 pt-2 border-t border-gray-100">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700">
                            Default Citation Format
                        </label>
                        <p class="text-xs text-gray-500">
                            Select your department's standard citation style for instant citation copying.
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                            <label class="flex items-start gap-3 p-3.5 rounded-2xl border border-gray-200 bg-slate-50 cursor-pointer hover:border-[#700000] transition">
                                <input type="radio" name="citation_style" value="ieee" class="mt-0.5 text-[#700000] focus:ring-[#700000]">
                                <div>
                                    <p class="text-xs font-bold text-gray-900">IEEE Format</p>
                                    <p class="text-[11px] text-gray-500 mt-0.5">Standard for BSIT & Marine Engineering</p>
                                </div>
                            </label>

                            <label class="flex items-start gap-3 p-3.5 rounded-2xl border border-gray-200 bg-slate-50 cursor-pointer hover:border-[#700000] transition">
                                <input type="radio" name="citation_style" value="apa" class="mt-0.5 text-[#700000] focus:ring-[#700000]">
                                <div>
                                    <p class="text-xs font-bold text-gray-900">APA 7th Edition</p>
                                    <p class="text-[11px] text-gray-500 mt-0.5">Standard for Nursing, CBA & Education</p>
                                </div>
                            </label>

                            <label class="flex items-start gap-3 p-3.5 rounded-2xl border border-gray-200 bg-slate-50 cursor-pointer hover:border-[#700000] transition">
                                <input type="radio" name="citation_style" value="mla" class="mt-0.5 text-[#700000] focus:ring-[#700000]">
                                <div>
                                    <p class="text-xs font-bold text-gray-900">MLA 9th Edition</p>
                                    <p class="text-[11px] text-gray-500 mt-0.5">Standard for Arts & Sciences</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Default Search Engine Mode -->
                    <div class="space-y-2 pt-2 border-t border-gray-100">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700">
                            Default Search Engine Mode
                        </label>
                        <p class="text-xs text-gray-500">
                            Choose which search engine activates when opening the thesis repository.
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                            <label class="flex items-start gap-3 p-3.5 rounded-2xl border border-gray-200 bg-slate-50 cursor-pointer hover:border-[#700000] transition">
                                <input type="radio" name="search_engine" value="keyword" class="mt-0.5 text-[#700000] focus:ring-[#700000]">
                                <div>
                                    <p class="text-xs font-bold text-gray-900">Standard Keyword Search</p>
                                    <p class="text-[11px] text-gray-500 mt-0.5">Searches exact title, author, and abstract text matches</p>
                                </div>
                            </label>

                            <label class="flex items-start gap-3 p-3.5 rounded-2xl border border-gray-200 bg-slate-50 cursor-pointer hover:border-[#700000] transition">
                                <input type="radio" name="search_engine" value="semantic" class="mt-0.5 text-[#700000] focus:ring-[#700000]">
                                <div>
                                    <p class="text-xs font-bold text-gray-900">Semantic AI Search (Vector)</p>
                                    <p class="text-[11px] text-gray-500 mt-0.5">Finds concepts and research intent using Google Gemini vectors</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Save Preferences Button -->
                    <div class="flex justify-end pt-4 border-t border-gray-100">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-[#700000] px-6 py-2.5 text-xs md:text-sm font-bold text-[#FFD700] hover:bg-[#850000] shadow-md transition">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span>Save Preferences</span>
                        </button>
                    </div>
                </form>
            </section>

            <!-- Section 3: Data & Local Session Management Card -->
            <section class="rounded-3xl border border-gray-200 bg-white p-6 md:p-8 shadow-sm space-y-5">
                <div class="flex items-center gap-3 border-b border-gray-100 pb-4">
                    <div class="w-10 h-10 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-[#700000]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Activity & Session Data</h2>
                        <p class="text-xs text-gray-500">Manage your local storage bookmarks and AI assistant conversations.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Manage Bookmarks Link -->
                    <div class="p-5 rounded-2xl bg-slate-50 border border-gray-200 flex flex-col justify-between">
                        <div>
                            <h3 class="text-xs font-bold text-gray-900">Saved Research Theses</h3>
                            <p class="text-[11px] text-gray-500 mt-1">Access your personal list of bookmarked capstones and theses.</p>
                        </div>
                        <a href="{{ route('bookmarks') }}" class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-[#700000] hover:underline">
                            <span>Open Bookmarks</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                    </div>

                    <!-- Clear AI Session -->
                    <div class="p-5 rounded-2xl bg-slate-50 border border-gray-200 flex flex-col justify-between">
                        <div>
                            <h3 class="text-xs font-bold text-gray-900">AI Assistant Session</h3>
                            <p class="text-[11px] text-gray-500 mt-1">Reset your current AI research assistant question history.</p>
                        </div>
                        <button
                            type="button"
                            onclick="clearAiChatSession()"
                            class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-rose-700 hover:text-rose-900 transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>
                            <span>Clear AI Chat History</span>
                        </button>
                    </div>
                </div>
            </section>

            <!-- Section 4: Academic Integrity Notice Card -->
            <section class="rounded-3xl border border-amber-200/80 bg-amber-50/50 p-6 md:p-8 shadow-2xs">
                <div class="flex items-start gap-3.5">
                    <svg class="w-5 h-5 text-amber-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.25-8.25-3.286zm0 13.036h.008v.008H12v-.008z" />
                    </svg>
                    <div class="space-y-1">
                        <h3 class="text-xs font-bold text-amber-900 uppercase tracking-wider">
                            St. Anthony's College Academic Integrity Policy
                        </h3>
                        <p class="text-xs text-amber-800 leading-relaxed">
                            All research materials, abstracts, and full texts indexed in this repository are protected under Philippine Copyright Law (RA 8293) and St. Anthony's College research ethics standards. Materials are for legitimate academic reference and citation only.
                        </p>
                    </div>
                </div>
            </section>

        </div>
    </main>

    <!-- Floating Toast Notification -->
    <div id="toastNotification" class="fixed bottom-6 right-6 z-50 transform transition-all duration-300 translate-y-20 opacity-0 pointer-events-none">
        <div class="flex items-center gap-3 rounded-2xl bg-white text-gray-900 px-5 py-3.5 shadow-xl border border-gray-200">
            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            <p id="toastMessage" class="text-xs md:text-sm font-semibold text-gray-800 tracking-wide">
                Preferences saved successfully!
            </p>
        </div>
    </div>

    <!-- JavaScript Preferences Logic -->
    <script>
        let toastTimeout = null;

        function showToast(message) {
            const toast = document.getElementById('toastNotification');
            const toastMsg = document.getElementById('toastMessage');
            if (!toast || !toastMsg) return;

            toastMsg.textContent = message;
            toast.classList.remove('translate-y-20', 'opacity-0', 'pointer-events-none');
            toast.classList.add('translate-y-0', 'opacity-100');

            if (toastTimeout) clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('translate-y-20', 'opacity-0', 'pointer-events-none');
            }, 2500);
        }

        // Load saved preferences on page load
        document.addEventListener('DOMContentLoaded', () => {
            const savedDept = localStorage.getItem('sac_preferred_dept') || '';
            const savedCitation = localStorage.getItem('sac_preferred_citation') || 'ieee';
            const savedEngine = localStorage.getItem('sac_preferred_engine') || 'keyword';

            const deptSelect = document.getElementById('defaultDept');
            if (deptSelect) deptSelect.value = savedDept;

            const citationRadio = document.querySelector(`input[name="citation_style"][value="${savedCitation}"]`);
            if (citationRadio) citationRadio.checked = true;

            const engineRadio = document.querySelector(`input[name="search_engine"][value="${savedEngine}"]`);
            if (engineRadio) engineRadio.checked = true;
        });

        // Save preferences to browser localStorage
        function savePreferences(e) {
            e.preventDefault();
            const dept = document.getElementById('defaultDept').value;
            const citation = document.querySelector('input[name="citation_style"]:checked')?.value || 'ieee';
            const engine = document.querySelector('input[name="search_engine"]:checked')?.value || 'keyword';

            localStorage.setItem('sac_preferred_dept', dept);
            localStorage.setItem('sac_preferred_citation', citation);
            localStorage.setItem('sac_preferred_engine', engine);

            showToast('Preferences updated successfully!');
        }

        // Clear local AI chat session
        function clearAiChatSession() {
            sessionStorage.removeItem('sac_chat_history');
            localStorage.removeItem('sac_chat_history');
            showToast('AI Assistant chat session cleared!');
        }
    </script>

</body>

</html>
