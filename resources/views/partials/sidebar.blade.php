<aside class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-gray-800 bg-gray-950">
    <div class="flex items-center gap-3 border-b border-gray-800 px-5 py-5">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 font-bold text-white">
            S
        </div>

        <span class="font-bold text-gray-100">SAC Thesis System</span>
    </div>

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