<!-- Floating Mobile Hamburger Toggle Button -->
<button
    id="sidebarToggleBtn"
    type="button"
    aria-label="Toggle Sidebar"
    class="fixed top-3 left-3 z-50 rounded-lg bg-[#500000] p-2.5 text-[#FFD700] border border-[#800000] shadow-lg hover:bg-[#600000] focus:outline-none md:hidden">

    <!-- Hamburger Icon -->
    <svg
        id="hamburgerIcon"
        class="h-6 w-6"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
    </svg>

    <!-- Close Icon -->
    <svg
        id="closeIcon"
        class="hidden h-6 w-6"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2">
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
            <span class="font-bold text-white text-sm tracking-wide">
                St. Anthony's College
            </span>
            <span class="text-[10px] text-[#FFD700] font-medium tracking-wider uppercase">
                Thesis System
            </span>
        </div>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 space-y-1.5 p-3">
        @if(session('sac_user_role') == 'admin')
        <!-- Visible ONLY to Admins -->
        <a
            href="/admin/upload"
            class="flex items-center rounded-lg px-4 py-3 text-sm font-semibold transition
                {{ request()->is('admin/upload')
                    ? 'bg-[#D4AF37] text-[#700000] shadow-md'
                    : 'text-amber-100 hover:bg-[#8d0000] hover:text-[#FFD700]' }}">
            Upload Thesis (Admin)
        </a>
        @else
        <!-- Documents & Search -->
        <a
            href="/documents"
            class="flex items-center rounded-lg px-4 py-3 text-sm font-semibold transition
                {{ request()->is('documents')
                    ? 'bg-[#D4AF37] text-[#700000] shadow-md'
                    : 'text-amber-100 hover:bg-[#8d0000] hover:text-[#FFD700]' }}">
            Documents & Search
        </a>

        <!-- Saved / Bookmarks -->
        <a
            href="/bookmarks"
            class="flex items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold transition
                {{ request()->is('bookmarks')
                    ? 'bg-[#D4AF37] text-[#700000] shadow-md'
                    : 'text-amber-100 hover:bg-[#8d0000] hover:text-[#FFD700]' }}">
            <span>🔖</span>
            <span>Saved / Bookmarks</span>
        </a>
        @endif

        <!-- AI Assistant -->
        <a
            href="/chat"
            class="flex items-center rounded-lg px-4 py-3 text-sm font-semibold transition
            {{ request()->is('chat')
                ? 'bg-[#D4AF37] text-[#700000] shadow-md'
                : 'text-amber-100 hover:bg-[#8d0000] hover:text-[#FFD700]' }}">
            AI Assistant
        </a>
    </nav>

    <!-- Sidebar Footer / Account Info -->
    <div class="border-t border-[#850000] bg-[#5b0000] p-3">
        <div class="mb-3 rounded-lg bg-[#4a0000] border border-[#7a0000] px-3 py-2.5">
            <p class="text-[10px] font-semibold text-[#FFD700] uppercase tracking-wider">
                Signed in as
            </p>
            <p class="mt-0.5 truncate text-sm font-medium text-white">
                {{ session('sac_user_email') }}
            </p>
        </div>

        <!-- Logout Trigger Button (Opens Confirmation Modal) -->
        <button
            type="button"
            onclick="openLogoutModal()"
            class="w-full rounded-lg border border-[#D4AF37]/40 bg-[#700000] px-4 py-2.5 text-left text-sm font-semibold text-[#FFD700] transition hover:bg-[#D4AF37] hover:text-[#700000] flex items-center justify-between">
            <span>Sign Out</span>
            <span>🚪</span>
        </button>

        <!-- Hidden Secure Logout Form -->
        <form
            method="POST"
            action="/logout"
            id="logoutForm"
            class="hidden">
            @csrf
        </form>
    </div>

</aside>

<!-- =========================================================
     LOGOUT CONFIRMATION MODAL
========================================================= -->
<div id="logoutModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4">
    <div class="w-full max-w-sm rounded-3xl bg-white p-6 md:p-8 text-center shadow-2xl transition-all">
        <!-- Icon -->
        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-red-50 ring-8 ring-red-100/70">
            <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
        </div>

        <!-- Title & Subtitle -->
        <h3 class="text-xl font-bold text-gray-900">Sign Out</h3>
        <p class="mt-2 text-xs md:text-sm text-gray-500 leading-relaxed">
            Are you sure you want to sign out of the SAC Thesis System?
        </p>

        <!-- Actions -->
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

<!-- =========================================================
     SIDEBAR & LOGOUT JAVASCRIPT
========================================================= -->
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
            if (isOpen) {
                closeSidebar();
            } else {
                openSidebar();
            }
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