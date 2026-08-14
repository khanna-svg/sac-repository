<!-- Floating Mobile Hamburger Toggle Button (Visible on mobile/tablet screens) -->
<button
    id="sidebarToggleBtn"
    type="button"
    aria-label="Toggle Sidebar"
    class="fixed top-3 left-3 z-50 rounded-lg bg-gray-900 p-2.5 text-gray-300 border border-gray-700 shadow-lg hover:bg-gray-800 focus:outline-none md:hidden"
>
    <!-- Hamburger Icon -->
    <svg id="hamburgerIcon" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
    </svg>
    <!-- Close Icon -->
    <svg id="closeIcon" class="hidden h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
    </svg>
</button>

<!-- Mobile Dark Overlay Backdrop -->
<div
    id="sidebarBackdrop"
    class="fixed inset-0 z-30 bg-black/60 opacity-0 pointer-events-none transition-opacity duration-300 ease-in-out md:hidden"
></div>

<!-- Sidebar Drawer -->
<aside
    id="sidebar"
    class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-gray-800 bg-gray-950 transition-transform duration-300 ease-in-out -translate-x-full md:translate-x-0"
>
    <!-- Sidebar Header -->
    <div class="flex items-center justify-between border-b border-gray-800 px-5 py-5">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 font-bold text-white">
                S
            </div>
            <span class="font-bold text-gray-100">SAC Thesis System</span>
        </div>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 space-y-1 p-3">
        <a
            href="/documents"
            class="flex items-center rounded-lg px-4 py-3 text-sm font-medium transition
            {{ request()->is('documents') ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-gray-100' }}"
        >
            Documents & Search
        </a>

        <a
            href="/chat"
            class="flex items-center rounded-lg px-4 py-3 text-sm font-medium transition
            {{ request()->is('chat') ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-gray-100' }}"
        >
            AI Assistant
        </a>
    </nav>

    <!-- Sidebar Footer / Account Info -->
    <div class="border-t border-gray-800 p-3">
        <div class="mb-3 rounded-lg bg-gray-900 px-3 py-2">
            <p class="text-xs text-gray-500">Signed in as</p>
            <p class="mt-1 truncate text-sm font-medium text-gray-200">
                {{ session('sac_user_email') }}
            </p>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button
                type="submit"
                class="w-full rounded-lg border border-gray-700 bg-gray-900 px-4 py-2.5 text-left text-sm font-medium text-gray-300 transition hover:bg-red-950 hover:text-red-300"
            >
                Sign Out
            </button>
        </form>
    </div>
</aside>

<!-- Sidebar Toggle Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    const backdrop = document.getElementById('sidebarBackdrop');
    const hamburgerIcon = document.getElementById('hamburgerIcon');
    const closeIcon = document.getElementById('closeIcon');

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

    toggleBtn.addEventListener('click', function () {
        if (isOpen) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    backdrop.addEventListener('click', closeSidebar);
});
</script>