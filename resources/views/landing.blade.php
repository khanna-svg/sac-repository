<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Anthony's College - Institutional Research Repository</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="https://sac.campus-erp.com/Student/images/sac.png" type="image/png">
</head>

<body class="bg-slate-50 text-slate-800 font-sans antialiased selection:bg-[#700000] selection:text-[#FFD700]">

    <!-- =========================================================
         NAVIGATION NAVBAR
    ========================================================== -->
    <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-200 transition-all">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">

                <!-- Logo & Brand -->
                <a href="#home" class="flex items-center gap-3.5 group">
                    <img
                        src="https://sac.campus-erp.com/Student/images/sac.png"
                        alt="St. Anthony's College Logo"
                        class="h-14 w-14 object-contain transition group-hover:scale-105">
                    <div class="flex flex-col">
                        <span class="font-extrabold text-base sm:text-lg text-[#700000] tracking-tight leading-none">
                            St. Anthony's College
                        </span>
                        <span class="text-[11px] font-semibold text-amber-700 tracking-wider uppercase mt-1">
                            Institutional Research Repository
                        </span>
                    </div>
                </a>

                <!-- Desktop Nav Links -->
                <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-gray-600">
                    <a href="#home" class="hover:text-[#700000] transition">Home</a>
                    <a href="#features" class="hover:text-[#700000] transition">Features</a>
                    <a href="#about" class="hover:text-[#700000] transition">About</a>
                    <a href="#contact" class="hover:text-[#700000] transition">Contact Us</a>
                </nav>

                <!-- Action Button -->
                <div class="flex items-center gap-3">
                    @if(session()->has('sac_user_role'))
                    <a
                        href="{{ session('sac_user_role') === 'admin' ? '/admin/upload' : '/documents' }}"
                        class="inline-flex items-center gap-2 rounded-2xl bg-[#700000] px-5 py-2.5 text-xs md:text-sm font-bold text-[#FFD700] hover:bg-[#850000] shadow-md transition">
                        <span>Go to Portal</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                    @else
                    <a
                        href="/login"
                        class="inline-flex items-center gap-2 rounded-2xl bg-[#700000] px-6 py-2.5 text-xs md:text-sm font-bold text-[#FFD700] hover:bg-[#850000] shadow-md transition">
                        <span>Sign In</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                        </svg>
                    </a>
                    @endif
                </div>

            </div>
        </div>
    </header>

    <!-- =========================================================
         HERO SECTION (WITH CAMPUS.JPG BACKGROUND)
    ========================================================== -->
    <section id="home" class="relative pt-16 pb-24 md:pt-28 md:pb-32 bg-cover bg-center bg-no-repeat overflow-hidden"
        style="background-image: linear-gradient(rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0.55)), url('/images/campus.jpg');">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-3xl mx-auto space-y-6">

                <!-- Headline -->
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-white tracking-tight leading-tight drop-shadow-md">
                    Preserving & Discovering <br class="hidden sm:inline">
                    <span class="text-[#FFD700]">Academic Excellence at SAC</span>
                </h1>

                <!-- Description -->
                <p class="text-sm sm:text-base text-gray-100 leading-relaxed max-w-2xl mx-auto drop-shadow-xs">
                    A centralized, AI-powered digital repository dedicated to archiving, searching, and visualizing undergraduate theses, capstone projects, and faculty research across all academic departments.
                </p>

                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
                    <!-- Main Button (Maroon Background with Gold Text) -->
                    <a
                        href="/login"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 rounded-2xl bg-[#700000] border border-[#850000] px-8 py-3.5 text-sm font-bold text-[#FFD700] hover:bg-[#850000] shadow-xl hover:shadow-2xl transition transform hover:-translate-y-0.5">
                        <svg class="w-4 h-4 text-[#FFD700]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span>Access Thesis Repository</span>
                    </a>
                    <a
                        href="#about"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl border border-white/40 bg-black/30 backdrop-blur-md px-7 py-3.5 text-sm font-semibold text-white hover:bg-white/20 transition shadow-sm">
                        <span>Learn More</span>
                    </a>
                </div>

            </div>

            <!-- Glassmorphism Stats Bar -->
            <div class="mt-16 grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6 max-w-4xl mx-auto">
                <div class="rounded-3xl border border-white/20 bg-black/35 backdrop-blur-md p-5 text-center shadow-lg text-white">
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#FFD700]">100%</p>
                    <p class="text-xs font-semibold text-gray-200 mt-1 uppercase tracking-wider">Digital Preservation</p>
                </div>
                <div class="rounded-3xl border border-white/20 bg-black/35 backdrop-blur-md p-5 text-center shadow-lg text-white">
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#FFD700]">Semantic</p>
                    <p class="text-xs font-semibold text-gray-200 mt-1 uppercase tracking-wider">AI Vector Search</p>
                </div>
                <div class="rounded-3xl border border-white/20 bg-black/35 backdrop-blur-md p-5 text-center shadow-lg text-white">
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#FFD700]">RAG AI</p>
                    <p class="text-xs font-semibold text-gray-200 mt-1 uppercase tracking-wider">Research Assistant</p>
                </div>
                <div class="rounded-3xl border border-white/20 bg-black/35 backdrop-blur-md p-5 text-center shadow-lg text-white">
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#FFD700]">Interactive</p>
                    <p class="text-xs font-semibold text-gray-200 mt-1 uppercase tracking-wider">Knowledge Graph</p>
                </div>
            </div>

        </div>
    </section>

    <!-- =========================================================
         CORE FEATURES SECTION
    ========================================================== -->
    <section id="features" class="py-20 bg-white border-y border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center max-w-2xl mx-auto mb-16 space-y-3">
                <h2 class="text-xs font-bold text-[#700000] uppercase tracking-widest">Key Capabilities</h2>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
                    Smart AI Features for Scholarly Discovery
                </p>
                <p class="text-xs sm:text-sm text-gray-500">
                    Engineered with state-of-the-art Natural Language Processing and vector databases to streamline literature reviews and research exploration.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

                <!-- Feature 1: Semantic AI Search -->
                <div class="rounded-3xl border border-gray-200 bg-slate-50 p-7 hover:border-[#700000]/40 transition hover:shadow-md space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-[#700000]/10 text-[#700000] flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">Contextual Semantic Search</h3>
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        Search by conceptual meanings rather than exact keyword matches using high-dimensional vector embeddings and cosine similarity ranking.
                    </p>
                </div>

                <!-- Feature 2: Intelligent Research Assistant -->
                <div class="rounded-3xl border border-gray-200 bg-slate-50 p-7 hover:border-[#700000]/40 transition hover:shadow-md space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-[#700000]/10 text-[#700000] flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">RAG AI Research Assistant</h3>
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        Ask natural-language questions about any thesis. The AI retrieves grounded passages and provides instant synthesis with citations.
                    </p>
                </div>

                <!-- Feature 3: Interactive Knowledge Graph -->
                <div class="rounded-3xl border border-gray-200 bg-slate-50 p-7 hover:border-[#700000]/40 transition hover:shadow-md space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-[#700000]/10 text-[#700000] flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">Interactive Knowledge Graph</h3>
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        Visually browse connected networks of student researchers, academic departments, methodologies, and thesis topics in real-time.
                    </p>
                </div>

                <!-- Feature 4: Research Analytics -->
                <div class="rounded-3xl border border-gray-200 bg-slate-50 p-7 hover:border-[#700000]/40 transition hover:shadow-md space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-[#700000]/10 text-[#700000] flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">Research Analytics Dashboard</h3>
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        Track institutional research output, program breakdowns, and yearly publication trends through intuitive visual metrics.
                    </p>
                </div>

                <!-- Feature 5: IEEE Citation Generator -->
                <div class="rounded-3xl border border-gray-200 bg-slate-50 p-7 hover:border-[#700000]/40 transition hover:shadow-md space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-[#700000]/10 text-[#700000] flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">Instant IEEE Citation</h3>
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        One-click standardized IEEE citation generation for seamless integration into capstone and thesis bibliographies.
                    </p>
                </div>

                <!-- Feature 6: Role-Based Security -->
                <div class="rounded-3xl border border-gray-200 bg-slate-50 p-7 hover:border-[#700000]/40 transition hover:shadow-md space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-[#700000]/10 text-[#700000] flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">Role-Based Access Control</h3>
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        Dedicated interfaces for Students and Administrators to ensure repository security and prevent unauthorized document modifications.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- =========================================================
         ABOUT SECTION
    ========================================================== -->
    <section id="about" class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

                <!-- Left Column: Text -->
                <div class="space-y-6">
                    <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-gray-900 tracking-tight leading-tight">
                        Modernizing Institutional Research at <span class="text-[#700000]">St. Anthony's College</span>
                    </h2>

                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        In St. Anthony’s College in San Jose de Buenavista, Antique, student theses and capstone projects have traditionally been archived as physical copies within the college library. As research output expands each year, physical storage constraints and manual cataloging present challenges for accessibility.
                    </p>

                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        This AI-powered repository bridges that gap by providing centralized electronic storage, automated document indexing, semantic concept retrieval, and interactive knowledge mapping—ensuring that valuable academic research is preserved and readily available for succeeding batches of student researchers.
                    </p>

                    <!-- Bullet Points -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                        <div class="flex items-center gap-2 text-xs font-bold text-gray-700">
                            <span class="w-2 h-2 rounded-full bg-[#700000]"></span>
                            <span>San Jose, Antique, PH</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs font-bold text-gray-700">
                            <span class="w-2 h-2 rounded-full bg-[#700000]"></span>
                            <span>BSIT Capstone 2026</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs font-bold text-gray-700">
                            <span class="w-2 h-2 rounded-full bg-[#700000]"></span>
                            <span>ISO/IEC 25010 Evaluated</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs font-bold text-gray-700">
                            <span class="w-2 h-2 rounded-full bg-[#700000]"></span>
                            <span>Cross-Departmental Scope</span>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Card -->
                <div class="rounded-3xl border border-gray-200 bg-white p-8 shadow-xl space-y-6">
                    <div class="flex items-center gap-4 border-b border-gray-100 pb-6">
                        <img
                            src="https://sac.campus-erp.com/Student/images/sac.png"
                            alt="SAC Seal"
                            class="h-16 w-16 object-contain">
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Information Technology Department</h3>
                            <p class="text-xs text-[#700000] font-semibold">St. Anthony's College • San Jose, Antique</p>
                        </div>
                    </div>

                    <div class="space-y-3 text-xs text-gray-600 leading-relaxed">
                        <p class="font-semibold text-gray-800">Project Proponents:</p>
                        <ul class="list-disc list-inside space-y-1 text-gray-600 pl-1">
                            <li>Darlyn M. Estrellado</li>
                            <li>Avrail Ann G. Escarlan</li>
                            <li>Ryan Figueroa</li>
                            <li>Francis Dominic G. Tenorio</li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- =========================================================
         CONTACT US SECTION
    ========================================================== -->
    <section id="contact" class="py-20 bg-white border-t border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center max-w-2xl mx-auto mb-16 space-y-3">
                <h2 class="text-xs font-bold text-[#700000] uppercase tracking-widest">Get In Touch</h2>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
                    Library & Research Inquiries
                </p>
                <p class="text-xs sm:text-sm text-gray-500">
                    Have questions about institutional thesis submissions, archive access, or repository guidelines?
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 max-w-5xl mx-auto">

                <!-- Contact Card 1: Address -->
                <div class="rounded-3xl border border-gray-200 bg-slate-50 p-7 text-center space-y-3 shadow-xs">
                    <div class="w-12 h-12 rounded-2xl bg-[#700000]/10 text-[#700000] mx-auto flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900">Campus Location</h3>
                    <p class="text-xs text-gray-600 leading-relaxed">
                        St. Anthony's College<br>
                        San Jose de Buenavista<br>
                        5700 Antique, Philippines
                    </p>
                </div>

                <!-- Contact Card 2: Email -->
                <div class="rounded-3xl border border-gray-200 bg-slate-50 p-7 text-center space-y-3 shadow-xs">
                    <div class="w-12 h-12 rounded-2xl bg-[#700000]/10 text-[#700000] mx-auto flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900">Email</h3>
                    <p class="text-xs text-gray-600 leading-relaxed">
                        library@sac.edu.ph<br>
                        research@sac.edu.ph<br>
                        it.department@sac.edu.ph
                    </p>
                </div>

                <!-- Contact Card 3: Hours & Support -->
                <div class="rounded-3xl border border-gray-200 bg-slate-50 p-7 text-center space-y-3 shadow-xs">
                    <div class="w-12 h-12 rounded-2xl bg-[#700000]/10 text-[#700000] mx-auto flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900">Library Service Hours</h3>
                    <p class="text-xs text-gray-600 leading-relaxed">
                        Monday – Friday<br>
                        8:00 AM – 5:00 PM PHT<br>
                        SAC College Library
                    </p>
                </div>

            </div>

        </div>
    </section>

    <!-- =========================================================
         FOOTER
    ========================================================== -->
    <footer class="bg-[#5b0000] text-white border-t border-[#7a0000] py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 text-center md:text-left">

                <div class="flex items-center gap-3">
                    <img
                        src="https://sac.campus-erp.com/Student/images/sac.png"
                        alt="SAC Logo"
                        class="h-10 w-10 object-contain">
                    <div class="flex flex-col">
                        <span class="font-bold text-sm text-[#FFD700]">St. Anthony's College</span>
                        <span class="text-[10px] text-gray-300">San Jose de Buenavista, Antique</span>
                    </div>
                </div>

                <p class="text-xs text-amber-200/80">
                    &copy; 2026 St. Anthony's College Institutional Research Repository. All rights reserved.
                </p>

                <div class="flex items-center gap-4 text-xs font-semibold text-amber-300">
                    <a href="#home" class="hover:underline">Home</a>
                    <span>•</span>
                    <a href="#features" class="hover:underline">Features</a>
                    <span>•</span>
                    <a href="#about" class="hover:underline">About</a>
                    <span>•</span>
                    <a href="#contact" class="hover:underline">Contact</a>
                </div>

            </div>
        </div>
    </footer>

    <!-- =========================================================
         FLOATING "CHAT WITH US" DIRECT CONNECT WIDGET (OPTION 2)
    ========================================================== -->
    <div class="fixed bottom-5 right-5 sm:bottom-6 sm:right-6 z-50 flex flex-col items-end gap-3 font-sans">

        <!-- Direct Connect Quick Menu Popup -->
        <div
            id="chatSupportPopup"
            class="hidden w-72 sm:w-80 rounded-2xl border border-gray-200 bg-white p-4 shadow-2xl transition-all duration-300 transform origin-bottom-right">
            
            <!-- Popup Header -->
            <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-3">
                <div class="flex items-center gap-2.5">
                    <div class="relative">
                        <img
                            src="https://sac.campus-erp.com/Student/images/sac.png"
                            alt="SAC Support"
                            class="h-8 w-8 object-contain">
                        <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-gray-900">SAC Library & Admin Support</h4>
                        <p class="text-[10px] text-gray-500">Usually responds within office hours</p>
                    </div>
                </div>
                <button
                    type="button"
                    onclick="toggleChatSupport()"
                    class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Channels List -->
            <div class="space-y-2">
                <!-- Messenger Channel -->
                <a
                    href="https://www.facebook.com/sac.sanjose.antique"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex items-center gap-3 rounded-xl border border-blue-100 bg-blue-50/60 p-3 text-xs font-semibold text-blue-900 hover:bg-blue-100/70 hover:border-blue-200 transition group">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#0084FF] text-white shadow-2xs">
                        <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.03 2 11c0 2.87 1.48 5.43 3.8 7.03V22l3.8-2.09c.77.21 1.58.33 2.4.33 5.52 0 10-4.03 10-9s-4.48-9-10-9zm1.06 12.16l-2.61-2.79-5.1 2.79 5.61-5.96 2.68 2.79 5.03-2.79-5.61 5.96z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-bold text-gray-900 group-hover:text-blue-900">Facebook Messenger</p>
                        <p class="text-[10px] text-gray-500">Chat with SAC Official Page</p>
                    </div>
                    <svg class="h-4 w-4 text-gray-400 group-hover:text-blue-700 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>

                <!-- Email Channel -->
                <a
                    href="mailto:library@sac.edu.ph?subject=Inquiry%20Regarding%20Thesis%20Repository"
                    class="flex items-center gap-3 rounded-xl border border-amber-100 bg-amber-50/50 p-3 text-xs font-semibold text-amber-900 hover:bg-amber-100/60 hover:border-amber-200 transition group">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#700000] text-[#FFD700] shadow-2xs">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-bold text-gray-900 group-hover:text-[#700000]">Email Library Office</p>
                        <p class="text-[10px] text-gray-500">library@sac.edu.ph</p>
                    </div>
                    <svg class="h-4 w-4 text-gray-400 group-hover:text-amber-800 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            </div>

            <p class="mt-3 text-center text-[10px] text-gray-400 italic">
                St. Anthony's College • San Jose, Antique
            </p>
        </div>

        <!-- Floating Action Button (Matches User Design) -->
        <button
            id="chatSupportBtn"
            type="button"
            onclick="toggleChatSupport()"
            class="flex items-center gap-2.5 rounded-full p-1.5 transition-transform hover:scale-105 active:scale-95 focus:outline-none drop-shadow-xl">
            
            <!-- Right Circular Button: Yellow circle with Maroon speech icon -->
            <div class="flex h-12 w-12 sm:h-14 sm:w-14 items-center justify-center rounded-full bg-[#FFD700] text-[#700000] border-2 border-amber-300 shadow-lg transition hover:bg-amber-400">
                <svg class="h-6 w-6 sm:h-7 sm:w-7 fill-current" viewBox="0 0 24 24">
                    <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                </svg>
            </div>
        </button>

    </div>

    <script>
        function toggleChatSupport() {
            const popup = document.getElementById('chatSupportPopup');
            if (popup) {
                popup.classList.toggle('hidden');
            }
        }
    </script>
</body>

</html>