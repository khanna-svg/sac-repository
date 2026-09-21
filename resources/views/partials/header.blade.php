@php
    $userEmail = session('sac_user_email');
    $userRole = session('sac_user_role');
    $userName = null;
    if ($userEmail) {
        $dbUser = \App\Models\User::where('email', $userEmail)->first();
        if ($dbUser && !empty($dbUser->name)) {
            $userName = $dbUser->name;
        } else {
            $userName = session('sac_user_name');
            if (!$userName) {
                $prefix = explode('@', $userEmail)[0];
                $userName = ucwords(preg_replace('/[._-]+/', ' ', $prefix));
            }
        }
        session(['sac_user_name' => $userName]);
    }
    $userName = $userName ?: (in_array($userRole, ['admin', 'librarian', 'coordinator']) ? 'Administrator' : 'Student');

    $initialNotifCount = 0;
    if (in_array($userRole, ['admin', 'librarian', 'coordinator'], true)) {
        $lastReadAt = session('admin_notifications_read_at');
        if ($lastReadAt) {
            $initialNotifCount = \App\Models\Document::whereNotNull('submitted_by_email')
                ->where('status', 'pending')
                ->where('created_at', '>', $lastReadAt)
                ->count();
        } else {
            $initialNotifCount = \App\Models\Document::whereNotNull('submitted_by_email')
                ->where('status', 'pending')
                ->count();
        }
    } elseif ($userEmail) {
        $initialNotifCount = \App\Models\ThesisNotification::where('user_email', $userEmail)
            ->where('is_read', false)
            ->count();
    }
@endphp

<header id="sacTopHeader" data-csrf="{{ csrf_token() }}" class="fixed top-0 right-0 left-0 md:left-64 h-16 md:h-20 z-40 bg-[#700000] border-b border-[#600000] shadow-md px-4 sm:px-6 flex items-center justify-between transition-all duration-300 select-none">
    
    <!-- LEFT: Mobile/Collapsed Toggle & Current Page Title -->
    <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
        <!-- Sidebar Toggle (Visible on Mobile & when Desktop Sidebar is Collapsed) -->
        <button
            id="headerSidebarToggle"
            type="button"
            onclick="toggleSidebarGlobal()"
            title="Toggle Navigation Menu"
            aria-label="Toggle Navigation Menu"
            class="rounded-xl p-1.5 sm:p-2 text-[#FFD700] hover:bg-[#8d0000] hover:text-white transition cursor-pointer flex items-center justify-center md:hidden shrink-0">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>

        <h1 class="font-serif text-base sm:text-xl md:text-2xl font-extrabold text-[#FFD700] tracking-wider uppercase drop-shadow-md truncate">
            {{ $title ?? 'THESIS' }}
        </h1>
    </div>

    <!-- RIGHT: User Greeting & Notifications -->
    <div class="flex items-center gap-2 sm:gap-4 shrink-0 justify-end">
        @if($userEmail)
            <div class="flex items-center">
                <span class="text-xs sm:text-sm md:text-base font-semibold text-white tracking-tight truncate max-w-[120px] sm:max-w-[220px] md:max-w-none">
                    Hi, <span class="font-bold text-[#FFD700]">{{ $userName }}</span>
                </span>
            </div>

            <!-- Header Notifications Bell & Dropdown -->
            <div class="relative">
                <button
                    type="button"
                    id="headerNotifBellBtn"
                    onclick="toggleHeaderNotifDropdown(event)"
                    title="Notifications"
                    aria-label="Notifications"
                    class="relative p-1.5 sm:p-2 rounded-xl text-[#FFD700] hover:text-white hover:bg-[#8d0000] transition cursor-pointer flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-[#FFD700]/50">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>

                    <!-- Unread Count Badge -->
                    <span
                        id="headerNotifBadge"
                        class="{{ $initialNotifCount > 0 ? '' : 'hidden' }} absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 bg-red-600 border border-white text-white text-[10px] font-extrabold rounded-full flex items-center justify-center shadow-md">
                        {{ $initialNotifCount > 99 ? '99+' : $initialNotifCount }}
                    </span>
                </button>

                <!-- Notifications Dropdown Popup -->
                <div
                    id="headerNotifDropdown"
                    class="hidden absolute right-0 mt-3 w-80 sm:w-96 rounded-2xl bg-white p-4 shadow-2xl border border-gray-200 z-50 text-left transition-all">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-2">
                        <div class="flex items-center gap-2">
                            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Notifications</h3>
                            <span id="headerNotifDropdownBadge" class="{{ $initialNotifCount > 0 ? '' : 'hidden' }} text-[10px] font-bold bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full">
                                {{ $initialNotifCount > 0 ? "{$initialNotifCount} new" : '' }}
                            </span>
                        </div>
                        <button
                            type="button"
                            onclick="markAllHeaderNotifsAsRead(event)"
                            class="text-[11px] font-semibold text-[#700000] hover:text-[#900000] hover:underline cursor-pointer">
                            Mark all as read
                        </button>
                    </div>

                    <div id="headerNotifList" class="max-h-72 overflow-y-auto divide-y divide-gray-100 text-xs text-gray-700">
                        <p class="py-4 text-center text-gray-400">Loading notifications...</p>
                    </div>
                </div>
            </div>
        @else
            <div class="w-10 sm:w-14 shrink-0" aria-hidden="true"></div>
        @endif
    </div>
</header>

<script>
    // Header Notifications Logic
    async function fetchHeaderNotifications() {
        try {
            const res = await fetch('/backend/notifications');
            if (!res.ok) return;
            const data = await res.json();
            const badge = document.getElementById('headerNotifBadge');
            const dropdownBadge = document.getElementById('headerNotifDropdownBadge');
            const list = document.getElementById('headerNotifList');

            if (badge) {
                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }

            if (dropdownBadge) {
                if (data.unread_count > 0) {
                    dropdownBadge.textContent = `${data.unread_count} new`;
                    dropdownBadge.classList.remove('hidden');
                } else {
                    dropdownBadge.classList.add('hidden');
                }
            }

            if (list) {
                if (!data.notifications || data.notifications.length === 0) {
                    list.innerHTML = '<p class="py-4 text-center text-gray-400">No notifications yet.</p>';
                    return;
                }

                list.innerHTML = data.notifications.map(n => {
                    const iconColor = n.type === 'approved' ? 'text-emerald-600' : (n.type === 'resubmit' ? 'text-rose-600' : 'text-amber-600');
                    const timeAgo = n.created_at ? new Date(n.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';
                    const linkUrl = n.link || (n.document_id ? `/documents/${n.document_id}` : null);
                    const tag = linkUrl ? 'a' : 'div';
                    const hrefAttr = linkUrl ? `href="${linkUrl}"` : '';
                    return `
                        <${tag} ${hrefAttr} class="block py-2.5 px-2.5 space-y-1 transition hover:bg-slate-50 ${!n.is_read ? 'bg-amber-50/70 rounded-xl' : ''}">
                            <div class="flex items-center justify-between gap-1">
                                <h4 class="font-bold text-gray-900 ${iconColor} flex items-center gap-1.5 text-xs">
                                    <span>${n.title}</span>
                                </h4>
                                <span class="text-[9px] text-gray-400 shrink-0 font-mono">${timeAgo}</span>
                            </div>
                            <p class="text-[11px] text-gray-600 leading-snug whitespace-pre-line">${n.message}</p>
                        </${tag}>
                    `;
                }).join('');
            }
        } catch (e) {
            console.error('Failed to fetch header notifications:', e);
        }
    }

    function toggleHeaderNotifDropdown(e) {
        if (e) e.stopPropagation();
        const dd = document.getElementById('headerNotifDropdown');
        if (!dd) return;
        dd.classList.toggle('hidden');
        if (!dd.classList.contains('hidden')) {
            fetchHeaderNotifications();
        }
    }

    async function markAllHeaderNotifsAsRead(e) {
        if (e) e.stopPropagation();
        try {
            const token = document.getElementById('sacTopHeader')?.dataset.csrf ||
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                '';
            await fetch('/backend/notifications/read-all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            });
            fetchHeaderNotifications();
        } catch (e) {
            console.error('Failed to mark notifications as read:', e);
        }
    }

    document.addEventListener('click', function(e) {
        const dd = document.getElementById('headerNotifDropdown');
        const btn = document.getElementById('headerNotifBellBtn');
        if (dd && btn && !dd.contains(e.target) && !btn.contains(e.target)) {
            dd.classList.add('hidden');
        }
    });

    function initHeaderNotifications() {
        if (document.getElementById('headerNotifBellBtn')) {
            fetchHeaderNotifications();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHeaderNotifications);
    } else {
        initHeaderNotifications();
    }
</script>
