<script>
    (function() {
        try {
            if (localStorage.getItem('sac_sidebar_collapsed') === 'true' && window.innerWidth >= 768) {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (e) {}
    })();
</script>

<style>
    @media (min-width: 768px) {
        html.sidebar-collapsed #sidebar {
            transform: translateX(-100%) !important;
        }
        html.sidebar-collapsed #sacTopHeader {
            left: 0 !important;
        }
        html.sidebar-collapsed #headerSidebarToggle {
            display: flex !important;
        }
        html.sidebar-collapsed main,
        html.sidebar-collapsed #mainContent,
        html.sidebar-collapsed div[class*="md:ml-64"] {
            margin-left: 0 !important;
        }
    }
</style>

<!-- Mobile Dark Overlay Backdrop -->
<div
    id="sidebarBackdrop"
    onclick="toggleSidebarGlobal()"
    class="fixed inset-0 z-45 bg-black/60 opacity-0 pointer-events-none transition-opacity duration-300 ease-in-out md:hidden">
</div>

<!-- Sidebar Drawer (Whole Full-Height Sidebar) -->
<aside
    id="sidebar"
    class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-[#600000] bg-[#700000] text-white transition-all duration-300 ease-in-out -translate-x-full md:translate-x-0 shadow-2xl">

    <!-- Sidebar Brand & Collapse Header (Whole Sidebar Style) -->
    <div class="h-16 md:h-20 px-3 sm:px-4 flex items-center justify-between gap-2 shrink-0 select-none bg-[#700000]">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5 group min-w-0 flex-1">
            <img 
                src="https://sac.campus-erp.com/Student/images/sac.png" 
                alt="St. Anthony's College Logo" 
                class="h-9 w-9 sm:h-11 sm:w-11 object-contain drop-shadow-md shrink-0 transition-transform group-hover:scale-105">
            <div class="flex flex-col font-serif leading-none truncate">
                <span class="text-xs sm:text-sm font-bold text-[#FFD700] tracking-tight group-hover:text-white transition truncate">
                    St. Anthony's
                </span>
                <span class="text-[9px] sm:text-xs font-semibold text-amber-100/90 tracking-tight truncate">
                    College, Inc.
                </span>
            </div>
        </a>

        <!-- Hamburger Collapse Button (Right Side of Logo & Name) -->
        <button
            id="sidebarCollapseBtn"
            type="button"
            onclick="toggleSidebarGlobal()"
            title="Toggle Navigation Menu"
            aria-label="Toggle Navigation Menu"
            class="rounded-xl p-1.5 text-[#FFD700] hover:bg-[#8d0000] hover:text-white transition cursor-pointer flex items-center justify-center shrink-0">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 space-y-1.5 p-3 overflow-y-auto">
        @php
            $currentRole = session('sac_user_role');
        @endphp

        @if(in_array($currentRole, ['admin', 'coordinator']))
        <!-- Analytics Dashboard (Admin & Coordinator) -->
        <a
            href="/admin/analytics"
            class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition
                {{ request()->is('admin/analytics')
                    ? 'bg-[#D4AF37] text-[#700000] shadow-md'
                    : 'text-amber-100 hover:bg-[#8d0000] hover:text-[#FFD700]' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
            </svg>
            <span>Dashboard</span>
        </a>
        @endif

        @if(in_array($currentRole, ['admin', 'librarian', 'coordinator']))
        <!-- Manage Theses (Admin, Librarian, Coordinator) -->
        <a
            href="/admin/theses"
            class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition
                {{ request()->is('admin/theses*')
                    ? 'bg-[#D4AF37] text-[#700000] shadow-md'
                    : 'text-amber-100 hover:bg-[#8d0000] hover:text-[#FFD700]' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
            </svg>
            <span>Manage Theses</span>
        </a>

        <!-- Review Student Submissions (Admin, Librarian, Coordinator) -->
        <a
            href="/admin/submissions"
            class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition
                {{ request()->is('admin/submissions*')
                    ? 'bg-[#D4AF37] text-[#700000] shadow-md'
                    : 'text-amber-100 hover:bg-[#8d0000] hover:text-[#FFD700]' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span class="truncate">Review Submissions</span>
        </a>
        @endif

        @if(in_array($currentRole, ['admin', 'librarian']))
        <!-- Direct Upload Thesis (Admin & Librarian) -->
        <a
            href="/admin/upload"
            class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition
                {{ request()->is('admin/upload*')
                    ? 'bg-[#D4AF37] text-[#700000] shadow-md'
                    : 'text-amber-100 hover:bg-[#8d0000] hover:text-[#FFD700]' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            <span>Upload Documents</span>
        </a>
        @endif

        @if(!in_array($currentRole, ['admin', 'librarian', 'coordinator']))
        <!-- Student Navigation -->
        <!-- Documents & Search -->
        <a
            href="/documents"
            class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition
                {{ request()->is('documents*')
                    ? 'bg-[#D4AF37] text-[#700000] shadow-md'
                    : 'text-amber-100 hover:bg-[#8d0000] hover:text-[#FFD700]' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <span>Documents</span>
        </a>

        <!-- Upload a Thesis (Student Submission) -->
        <a
            href="/student/submit"
            class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition
                {{ request()->is('student/submit*')
                    ? 'bg-[#D4AF37] text-[#700000] shadow-md'
                    : 'text-amber-100 hover:bg-[#8d0000] hover:text-[#FFD700]' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
            </svg>
            <span>Upload a Thesis</span>
        </a>

        <!-- Saved / Bookmarks -->
        <a
            href="/bookmarks"
            class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition
                {{ request()->is('bookmarks*')
                    ? 'bg-[#D4AF37] text-[#700000] shadow-md'
                    : 'text-amber-100 hover:bg-[#8d0000] hover:text-[#FFD700]' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
            </svg>
            <span>Saved / Bookmarks</span>
        </a>

        <!-- Knowledge Graph -->
        <a
            href="/graph"
            class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition
                {{ request()->is('graph*')
                    ? 'bg-[#D4AF37] text-[#700000] shadow-md'
                    : 'text-amber-100 hover:bg-[#8d0000] hover:text-[#FFD700]' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
            </svg>
            <span>Knowledge Graph</span>
        </a>
        @endif
    </nav>

    <!-- Sidebar Footer / Sign Out -->
    <div class="border-t border-[#700000] bg-[#5b0000] p-3 shrink-0">
        <!-- Logout Trigger Button -->
        <button
            type="button"
            onclick="openLogoutModal()"
            class="w-full rounded-xl border border-[#D4AF37]/40 bg-[#700000] px-4 py-2.5 text-left text-xs md:text-sm font-semibold text-[#FFD700] transition hover:bg-[#D4AF37] hover:text-[#700000] flex items-center justify-between cursor-pointer">
            <span>Sign Out</span>
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
        </button>

        <form method="POST" action="/logout" id="logoutForm" class="hidden">
            @csrf
        </form>
    </div>

</aside>

<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4">
    <div class="w-full max-w-sm rounded-3xl bg-white p-6 md:p-8 text-center shadow-2xl transition-all">
        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-red-50 ring-8 ring-red-100/70">
            <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
        </div>
        <h3 class="text-xl font-bold text-gray-900">Sign Out</h3>
        <p class="mt-2 text-xs md:text-sm text-gray-500 leading-relaxed">
            Are you sure you want to sign out?
        </p>
        <div class="mt-6 flex items-center justify-center gap-3">
            <button
                type="button"
                onclick="closeLogoutModal()"
                class="w-1/2 rounded-xl border border-gray-300 py-2.5 text-xs md:text-sm font-semibold text-gray-700 hover:bg-gray-100 transition focus:outline-none cursor-pointer">
                Cancel
            </button>
            <button
                type="button"
                onclick="confirmLogout()"
                class="w-1/2 rounded-xl bg-[#700000] py-2.5 text-xs md:text-sm font-bold text-[#FFD700] shadow-md hover:bg-[#850000] transition focus:outline-none cursor-pointer">
                Yes, Sign Out
            </button>
        </div>
    </div>
</div>

<script>
    function toggleSidebarGlobal() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        if (!sidebar) return;

        if (window.innerWidth >= 768) {
            document.documentElement.classList.toggle('sidebar-collapsed');
            const isCollapsed = document.documentElement.classList.contains('sidebar-collapsed');
            localStorage.setItem('sac_sidebar_collapsed', isCollapsed ? 'true' : 'false');
        } else {
            const isHidden = sidebar.classList.contains('-translate-x-full');
            if (isHidden) {
                sidebar.classList.remove('-translate-x-full');
                if (backdrop) {
                    backdrop.classList.remove('opacity-0', 'pointer-events-none');
                    backdrop.classList.add('opacity-100');
                }
            } else {
                sidebar.classList.add('-translate-x-full');
                if (backdrop) {
                    backdrop.classList.remove('opacity-100');
                    backdrop.classList.add('opacity-0', 'pointer-events-none');
                }
            }
        }
    }

    function openLogoutModal() {
        const modal = document.getElementById('logoutModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeLogoutModal() {
        const modal = document.getElementById('logoutModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function confirmLogout() {
        document.getElementById('logoutForm').submit();
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const sidebar = document.getElementById('sidebar');
            if (sidebar && window.innerWidth < 768 && !sidebar.classList.contains('-translate-x-full')) {
                toggleSidebarGlobal();
            }
            closeLogoutModal();
        }
    });
</script>

{{-- Floating Meta Messenger-Style AI Assistant (Available across all student pages) --}}
@include('partials.floating_ai')