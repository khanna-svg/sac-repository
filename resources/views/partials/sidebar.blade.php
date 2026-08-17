<!-- Floating Mobile Hamburger Toggle Button -->
<button
    id="sidebarToggleBtn"
    type="button"
    aria-label="Toggle Sidebar"
    class="fixed top-3 left-3 z-50 rounded-lg bg-[#500000] p-2.5 text-[#FFD700] border border-[#800000] shadow-lg hover:bg-[#600000] focus:outline-none md:hidden"
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
    class="fixed inset-0 z-30 bg-black/70 opacity-0 pointer-events-none transition-opacity duration-300 ease-in-out md:hidden"
></div>

<!-- Sidebar Drawer -->
<aside
    id="sidebar"
    class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-[#600000] bg-[#700000] text-white transition-transform duration-300 ease-in-out -translate-x-full md:translate-x-0 shadow-2xl"
>
    <!-- Sidebar Header -->
    <div class="flex items-center justify-between border-b border-[#850000] px-5 py-5 bg-[#5b0000]">
        <div class="flex items-center gap-3">
            <img 
                src="https://sac.campus-erp.com/Student/images/sac.png"
                alt="St. Anthony's College Logo"
                class="h-[60px] w-[60px] object-contain"
            >
        </div>
            <div class="flex flex-col">
                <span class="font-bold text-white text-sm tracking-wide">St. Anthony's College</span>
                <span class="text-[10px] text-[#FFD700] font-medium tracking-wider uppercase">Thesis System</span>
            </div>
        </div>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 space-y-1.5 p-3">
        @if(session('sac_user_role') == 'admin')
            <!-- Visible ONLY to Admins -->
            <a
                href="/admin/upload"
                class="flex items-center rounded-lg px-4 py-3 text-sm font-semibold transition
                {{ request()->is('admin/upload') ? 'bg-[#D4AF37] text-[#700000] shadow-md' : 'text-amber-100 hover:bg-[#8d0000] hover:text-[#FFD700]' }}"
            >
                Upload Thesis (Admin)
            </a>
        @else
            <!-- Visible ONLY to Students -->
            <a
                href="/documents"
                class="flex items-center rounded-lg px-4 py-3 text-sm font-semibold transition
                {{ request()->is('documents') ? 'bg-[#D4AF37] text-[#700000] shadow-md' : 'text-amber-100 hover:bg-[#8d0000] hover:text-[#FFD700]' }}"
            >
                Documents & Search
            </a>
        @endif

        <!-- AI Assistant (Visible to Everyone) -->
        <a
            href="/chat"
            class="flex items-center rounded-lg px-4 py-3 text-sm font-semibold transition
            {{ request()->is('chat') ? 'bg-[#D4AF37] text-[#700000] shadow-md' : 'text-amber-100 hover:bg-[#8d0000] hover:text-[#FFD700]' }}"
        >
            AI Assistant
        </a>
    </nav>

    <!-- Sidebar Footer / Account Info -->
    <div class="border-t border-[#850000] bg-[#5b0000] p-3">
        <div class="mb-3 rounded-lg bg-[#4a0000] border border-[#7a0000] px-3 py-2.5">
            <p class="text-[10px] font-semibold text-[#FFD700] uppercase tracking-wider">Signed in as</p>
            <p class="mt-0.5 truncate text-sm font-medium text-white">
                {{ session('sac_user_email') }}
            </p>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button
                type="submit"
                class="w-full rounded-lg border border-[#D4AF37]/40 bg-[#700000] px-4 py-2.5 text-left text-sm font-semibold text-[#FFD700] transition hover:bg-[#D4AF37] hover:text-[#700000]"
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