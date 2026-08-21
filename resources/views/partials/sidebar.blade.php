<!-- Floating Mobile Hamburger Toggle Button -->
<button
    id="sidebarToggleBtn"
    type="button"
    aria-label="Toggle Sidebar"
    class="fixed top-3 left-3 z-50 rounded-xl bg-[#500000] p-2.5 text-[#FFD700] border border-[#800000] shadow-lg hover:bg-[#600000] focus:outline-none md:hidden transition">
    <svg id="hamburgerIcon" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
    </svg>
    <svg id="closeIcon" class="hidden h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
    </svg>
</button>

<!-- Mobile Dark Overlay Backdrop -->
<div
    id="sidebarBackdrop"
    class="fixed inset-0 z-30 bg-black/70 opacity-0 pointer-events-none transition-opacity duration-300 ease-in-out md:hidden">
</div>

<!-- Sidebar Drawer -->
<aside
    id="sidebar"
    class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-[#600000] bg-[#700000] text-white transition-transform duration-300 ease-in-out -translate-x-full md:translate-x-0 shadow-2xl">

    <!-- Sidebar Header -->
    <div class="flex items-center justify-between border-b border-[#850000] px-5 py-5 bg-[#5b0000]">
        <div class="flex items-center gap-3">
            <img
                src="https://sac.campus-erp.com/Student/images/sac.png"
                alt="St. Anthony's College Logo"
                class="h-[60px] w-[60px] object-contain">
        </div>
        <div class="flex flex-col">
            <span class="font-bold text-[#FFC107] text-sm tracking-wide">
                St. Anthony's College
            </span>
            <span class="text-[10px] text-white font-medium tracking-wider uppercase">
                Spirituality, Academic Excellence and Community Service
            </span>
        </div>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 space-y-1.5 p-3">
        @if(session('sac_user_role') == 'admin')
        <!-- Admin: Upload Thesis -->
        <a
            href="/admin/upload"
            class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition
                {{ request()->is('admin/upload')
                    ? 'bg-[#D4AF37] text-[#700000] shadow-md'
                    : 'text-amber-100 hover:bg-[#8d0000] hover:text-[#FFD700]' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            <span>Upload Thesis</span>
        </a>

        <!-- Admin: Research Analytics -->
        <a
            href="/admin/analytics"
            class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition
                {{ request()->is('admin/analytics')
                    ? 'bg-[#D4AF37] text-[#700000] shadow-md'
                    : 'text-amber-100 hover:bg-[#8d0000] hover:text-[#FFD700]' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
            </svg>
            <span>Research Analytics</span>
        </a>
        @else
        <!-- Documents & Search -->
        <a
            href="/documents"
            class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition
                {{ request()->is('documents')
                    ? 'bg-[#D4AF37] text-[#700000] shadow-md'
                    : 'text-amber-100 hover:bg-[#8d0000] hover:text-[#FFD700]' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <span>Documents & Search</span>
        </a>

        <!-- Knowledge Graph -->
        <a
            href="/graph"
            class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition
                {{ request()->is('graph')
                    ? 'bg-[#D4AF37] text-[#700000] shadow-md'
                    : 'text-amber-100 hover:bg-[#8d0000] hover:text-[#FFD700]' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 16.875h3.375m0 0h3.375m-3.375 0V13.5m0 3.375v3.375M6 10.5h2.25a2.25 2.25 0 002.25-2.25V6a2.25 2.25 0 00-2.25-2.25H6A2.25 2.25 0 003.75 6v2.25A2.25 2.25 0 006 10.5zm0 9.75h2.25A2.25 2.25 0 0010.5 18v-2.25a2.25 2.25 0 00-2.25-2.25H6a2.25 2.25 0 00-2.25 2.25V18A2.25 2.25 0 006 20.25zm9.75-9.75H18a2.25 2.25 0 002.25-2.25V6A2.25 2.25 0 0018 3.75h-2.25A2.25 2.25 0 0013.5 6v2.25a2.25 2.25 0 002.25 2.25z" />
            </svg>
            <span>Knowledge Graph</span>
        </a>

        <!-- Saved / Bookmarks -->
        <a
            href="/bookmarks"
            class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition
                {{ request()->is('bookmarks')
                    ? 'bg-[#D4AF37] text-[#700000] shadow-md'
                    : 'text-amber-100 hover:bg-[#8d0000] hover:text-[#FFD700]' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
            </svg>
            <span>Saved / Bookmarks</span>
        </a>
        @endif

        <!-- AI Assistant -->
        <a
            href="/chat"
            class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition
            {{ request()->is('chat')
                ? 'bg-[#D4AF37] text-[#700000] shadow-md'
                : 'text-amber-100 hover:bg-[#8d0000] hover:text-[#FFD700]' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
            </svg>
            <span>AI Assistant</span>
        </a>
    </nav>

    <!-- Sidebar Footer / Account Info -->
    <div class="border-t border-[#850000] bg-[#5b0000] p-3">
        <div class="mb-3 rounded-xl bg-[#4a0000] border border-[#7a0000] px-3 py-2.5">
            <p class="text-[10px] font-semibold text-[#FFD700] uppercase tracking-wider">
                Signed in as
            </p>
            <p class="mt-0.5 truncate text-xs font-medium text-white">
                {{ session('sac_user_email') }}
            </p>
        </div>

        <!-- Logout Trigger Button -->
        <button
            type="button"
            onclick="openLogoutModal()"
            class="w-full rounded-xl border border-[#D4AF37]/40 bg-[#700000] px-4 py-2.5 text-left text-xs md:text-sm font-semibold text-[#FFD700] transition hover:bg-[#D4AF37] hover:text-[#700000] flex items-center justify-between">
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
                class="w-1/2 rounded-xl border border-gray-300 py-2.5 text-xs md:text-sm font-semibold text-gray-700 hover:bg-gray-100 transition focus:outline-none">
                Cancel
            </button>
            <button
                type="button"
                onclick="confirmLogout()"
                class="w-1/2 rounded-xl bg-[#700000] py-2.5 text-xs md:text-sm font-bold text-[#FFD700] shadow-md hover:bg-[#850000] transition focus:outline-none">
                Yes, Sign Out
            </button>
        </div>
    </div>
</div>

<script>
    function openLogoutModal() {
        const modal = document.getElementById('logoutModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeLogoutModal() {
        const modal = document.getElementById('logoutModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function confirmLogout() {
        document.getElementById('logoutForm').submit();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebarToggleBtn');
        const backdrop = document.getElementById('sidebarBackdrop');
        const hamburgerIcon = document.getElementById('hamburgerIcon');
        const closeIcon = document.getElementById('closeIcon');

        if (!sidebar || !toggleBtn || !backdrop || !hamburgerIcon || !closeIcon) return;

        let isOpen = false;

        function openSidebar() {
            isOpen = true;
            sidebar.classList.remove('-translate-x-full');
            backdrop.classList.remove('opacity-0', 'pointer-events-none');
            backdrop.classList.add('opacity-100');
            hamburgerIcon.classList.add('hidden');
            closeIcon.classList.remove('hidden');
        }

        function closeSidebar() {
            isOpen = false;
            sidebar.classList.add('-translate-x-full');
            backdrop.classList.remove('opacity-100');
            backdrop.classList.add('opacity-0', 'pointer-events-none');
            hamburgerIcon.classList.remove('hidden');
            closeIcon.classList.add('hidden');
        }

        toggleBtn.addEventListener('click', function() {
            if (isOpen) closeSidebar();
            else openSidebar();
        });

        backdrop.addEventListener('click', closeSidebar);

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                if (isOpen) closeSidebar();
                closeLogoutModal();
            }
        });

        if (window.innerWidth >= 768) {
            sidebar.classList.remove('-translate-x-full');
        } else {
            sidebar.classList.add('-translate-x-full');
        }
    });
</script>