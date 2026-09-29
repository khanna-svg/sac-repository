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
                    class="hidden absolute right-0 top-full mt-2 w-80 sm:w-96 rounded-2xl bg-white p-4 shadow-2xl border border-gray-200 z-50 text-left transition-all">
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

                    <div id="headerNotifList" class="max-h-80 overflow-y-auto divide-y divide-gray-100 text-xs text-gray-700 space-y-1">
                        <p class="py-4 text-center text-gray-400">Loading notifications...</p>
                    </div>
                </div>
            </div>
        @else
            <div class="w-10 sm:w-14 shrink-0" aria-hidden="true"></div>
        @endif
    </div>

    <!-- Live Toast Notification Container -->
    <div id="sacLiveToastContainer" class="fixed bottom-5 right-5 z-50 flex flex-col gap-3 pointer-events-none max-w-sm sm:max-w-md w-full px-4 sm:px-0"></div>
</header>

<script>
    // Header Notifications Logic
    function showLiveToast(n) {
        const container = document.getElementById('sacLiveToastContainer');
        if (!container) return;

        const isCleared = (n.type === 'cleared' || n.type === 'approved');
        const isResubmit = (n.type === 'resubmit');
        const borderCol = isCleared ? 'border-emerald-300 bg-white/95' : (isResubmit ? 'border-rose-300 bg-white/95' : 'border-amber-300 bg-white/95');
        const titleCol = isCleared ? 'text-emerald-900' : (isResubmit ? 'text-rose-900' : 'text-blue-900');
        const badgeCol = isCleared ? 'bg-emerald-600 text-white' : (isResubmit ? 'bg-rose-600 text-white' : 'bg-blue-600 text-white');
        const linkUrl = n.link || '/student/submit';

        const toastId = 'toast_' + Math.random().toString(36).substring(2, 9);
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `pointer-events-auto rounded-3xl border ${borderCol} p-4 shadow-2xl backdrop-blur-md transition-all duration-500 transform translate-y-4 opacity-0 flex items-start gap-3.5`;
        toast.innerHTML = `
            <div class="w-10 h-10 rounded-2xl ${badgeCol} flex items-center justify-center shrink-0 shadow-md">
                ${isCleared ? `
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                ` : isResubmit ? `
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                ` : `
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                `}
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                    <h4 class="font-bold text-xs ${titleCol} truncate">${n.title}</h4>
                    <button type="button" onclick="document.getElementById('${toastId}')?.remove()" class="text-gray-400 hover:text-gray-600 p-0.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <p class="text-[11px] text-gray-700 mt-1 leading-snug line-clamp-2">${n.message}</p>
                <div class="mt-2.5 flex items-center gap-2">
                    <a href="${linkUrl}" onclick="handleHeaderNotifClick('${n.id}', '${linkUrl}', event)" class="inline-flex items-center gap-1 px-3 py-1 rounded-xl bg-[#700000] text-[#FFD700] hover:bg-[#8d0000] text-[11px] font-bold shadow-xs transition">
                        <span>View Details</span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>
        `;

        container.appendChild(toast);
        requestAnimationFrame(() => {
            toast.classList.remove('translate-y-4', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
        });

        setTimeout(() => {
            if (toast.parentNode) {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 500);
            }
        }, 10000);

        if ('Notification' in window && Notification.permission === 'granted') {
            try {
                new Notification(n.title, {
                    body: n.message.substring(0, 140),
                    icon: 'https://sac.campus-erp.com/Student/images/sac.png'
                });
            } catch (err) {}
        }
    }

    async function fetchHeaderNotifications() {
        try {
            const res = await fetch('/backend/notifications');
            if (!res.ok) return;
            const data = await res.json();
            const badge = document.getElementById('headerNotifBadge');
            const dropdownBadge = document.getElementById('headerNotifDropdownBadge');
            const list = document.getElementById('headerNotifList');

            const count = data.unread_count || 0;

            if (badge) {
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }

            if (dropdownBadge) {
                if (count > 0) {
                    dropdownBadge.textContent = `${count} new`;
                    dropdownBadge.classList.remove('hidden');
                } else {
                    dropdownBadge.classList.add('hidden');
                }
            }

            if (list) {
                if (!data.notifications || data.notifications.length === 0) {
                    list.innerHTML = '<p class="py-6 text-center text-xs text-gray-400 font-medium">No notifications yet.</p>';
                    return;
                }

                list.innerHTML = data.notifications.map(n => {
                    const isCleared = (n.type === 'cleared' || n.type === 'approved');
                    const iconColor = isCleared ? 'text-emerald-700' : (n.type === 'resubmit' ? 'text-rose-700' : 'text-blue-700');
                    const timeAgo = n.created_at ? new Date(n.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';
                    const linkUrl = n.link || '/student/submit';

                    return `
                        <a href="${linkUrl}" onclick="handleHeaderNotifClick('${n.id}', '${linkUrl}', event)" class="block py-2.5 px-3 rounded-xl transition hover:bg-slate-50 cursor-pointer ${!n.is_read ? 'bg-amber-50/70 border border-amber-200/70' : 'border border-transparent'}">
                            <div class="flex items-start justify-between gap-1.5">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    ${!n.is_read ? '<span class="w-2 h-2 rounded-full bg-[#700000] shrink-0"></span>' : ''}
                                    <h4 class="font-bold text-xs truncate ${iconColor}">${n.title}</h4>
                                </div>
                                <span class="text-[9px] text-gray-400 shrink-0 font-mono">${timeAgo}</span>
                            </div>
                            <p class="text-[11px] text-gray-600 mt-1 leading-relaxed whitespace-pre-line line-clamp-3">${n.message}</p>
                        </a>
                    `;
                }).join('');
            }

            // Check for new unread notifications and pop up live toast
            if (data.notifications && data.notifications.length > 0) {
                data.notifications.forEach(n => {
                    if (!n.is_read) {
                        const storageKey = 'sac_toast_shown_' + n.id;
                        if (!sessionStorage.getItem(storageKey)) {
                            sessionStorage.setItem(storageKey, '1');
                            showLiveToast(n);
                        }
                    }
                });
            }
        } catch (e) {
            console.error('Failed to fetch header notifications:', e);
        }
    }

    async function handleHeaderNotifClick(id, linkUrl, e) {
        if (e) e.preventDefault();
        try {
            const token = document.getElementById('sacTopHeader')?.dataset.csrf ||
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            await fetch(`/backend/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            });
        } catch (err) {
            console.error(err);
        }
        window.location.href = linkUrl;
    }

    function toggleHeaderNotifDropdown(e) {
        if (e) e.stopPropagation();
        const dd = document.getElementById('headerNotifDropdown');
        if (!dd) return;
        dd.classList.toggle('hidden');
        if (!dd.classList.contains('hidden')) {
            fetchHeaderNotifications();
        }

        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
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
            window.addEventListener('focus', fetchHeaderNotifications);
            setInterval(fetchHeaderNotifications, 20000);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHeaderNotifications);
    } else {
        initHeaderNotifications();
    }
</script>
