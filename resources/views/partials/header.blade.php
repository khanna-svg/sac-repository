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

    $studentRevisionDoc = null;
    $hasRevisionNotice = false;
    $revDeadline = null;
    $revDaysRemaining = 0;
    if ($userEmail && !in_array($userRole, ['admin', 'librarian', 'coordinator'], true)) {
        if (!session()->has('revision_notice_dismissed')) {
            $studentRevisionDoc = \App\Models\Document::where('submitted_by_email', strtolower($userEmail))
                ->where('status', 'resubmit')
                ->latest('updated_at')
                ->first();
            if ($studentRevisionDoc) {
                $hasRevisionNotice = true;
                $revDeadline = $studentRevisionDoc->updated_at ? $studentRevisionDoc->updated_at->copy()->addMonths(3) : now()->addMonths(3);
                $revDaysRemaining = max(0, (int) now()->diffInDays($revDeadline, false));
            }
        }
    }
@endphp

<header id="sacTopHeader" data-csrf="{{ csrf_token() }}" class="fixed top-0 right-0 left-0 md:left-64 h-14 md:h-16 z-40 bg-white/90 backdrop-blur-md border-b border-slate-200/90 px-4 sm:px-6 flex items-center justify-between transition-all duration-300 select-none shadow-2xs">
    
    <!-- LEFT: Mobile/Collapsed Toggle & Current Page Title (Clean Minimalist without "AIRIS /") -->
    <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
        <!-- Sidebar Toggle (Visible on Mobile & when Desktop Sidebar is Collapsed) -->
        <button
            id="headerSidebarToggle"
            type="button"
            onclick="toggleSidebarGlobal()"
            title="Toggle Navigation Menu"
            aria-label="Toggle Navigation Menu"
            class="rounded-xl p-1.5 text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition cursor-pointer flex items-center justify-center md:hidden shrink-0">
            <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>

        <h1 class="font-sans text-sm sm:text-base md:text-lg font-bold text-slate-900 tracking-tight uppercase truncate">
            {{ $title ?? 'AIRIS' }}
        </h1>
    </div>

    <!-- RIGHT: User Profile & Notifications (Clean Frosted Light Minimalist) -->
    <div class="flex items-center gap-3 sm:gap-4 shrink-0 justify-end">
        @if($userEmail)
            <!-- User Name & Role in Crisp Slate -->
            <div class="flex items-center gap-2">
                <div class="flex flex-col text-right sm:text-left leading-tight">
                    <span class="text-xs sm:text-sm font-semibold text-slate-800 tracking-tight truncate max-w-[140px] sm:max-w-[220px]">
                        {{ $userName }}
                    </span>
                    <span class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">
                        {{ in_array($userRole, ['admin', 'librarian', 'coordinator'], true) ? ucfirst($userRole) : 'Student' }}
                    </span>
                </div>

                @if(in_array($userRole, ['admin', 'librarian', 'coordinator'], true))
                    <!-- Profile User SVG Button & Dropdown Menu -->
                    <div class="relative">
                        <button
                            type="button"
                            id="adminProfileMenuBtn"
                            onclick="toggleAdminProfileDropdown(event)"
                            title="Profile & Options"
                            aria-label="Profile & Options"
                            class="p-1 sm:p-1.5 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition cursor-pointer flex items-center justify-center focus:outline-none">
                            <svg class="w-5 h-5 text-slate-600 hover:text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                        </button>

                        <!-- Profile User Dropdown -->
                        <div
                            id="adminProfileDropdown"
                            class="hidden absolute right-0 top-full mt-2 w-56 rounded-2xl bg-white p-2 shadow-xl border border-slate-200 z-50 text-left transition-all">
                            <div class="px-3 py-2 border-b border-gray-100 mb-1">
                                <p class="text-xs font-bold text-gray-900 truncate">{{ $userName }}</p>
                                <p class="text-[11px] text-gray-500 truncate">{{ $userEmail }}</p>
                                <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-rose-50 text-[#700000] border border-rose-200">
                                    {{ ucfirst($userRole) }}
                                </span>
                            </div>

                            <button
                                type="button"
                                onclick="openActivityLogsModal(event)"
                                class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-[#700000] transition cursor-pointer text-left group">
                                <svg class="w-4 h-4 text-slate-500 group-hover:text-[#700000]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0zM3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                </svg>
                                <span>View Logs</span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Hairline Divider -->
            <div class="h-4 sm:h-5 w-px bg-slate-200 block"></div>

            <!-- Header Notifications Bell & Dropdown -->
            <div class="relative">
                <button
                    type="button"
                    id="headerNotifBellBtn"
                    onclick="toggleHeaderNotifDropdown(event)"
                    title="Notifications"
                    aria-label="Notifications"
                    class="relative p-1.5 sm:p-2 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition cursor-pointer flex items-center justify-center focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>

                    <!-- Unread Count Badge -->
                    <span
                        id="headerNotifBadge"
                        class="{{ $initialNotifCount > 0 ? '' : 'hidden' }} absolute -top-0.5 -right-0.5 min-w-[17px] h-[17px] px-1 bg-red-600 border-2 border-white text-white text-[9px] font-extrabold rounded-full flex items-center justify-center shadow-xs">
                        {{ $initialNotifCount > 99 ? '99+' : $initialNotifCount }}
                    </span>
                </button>

                <!-- Notifications Dropdown Popup -->
                <div
                    id="headerNotifDropdown"
                    class="hidden absolute right-0 top-full mt-2 w-80 sm:w-96 rounded-2xl bg-white p-4 shadow-xl border border-slate-200 z-50 text-left transition-all">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-2">
                        <div class="flex items-center gap-2">
                            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Notifications</h3>
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
            <div class="w-6 sm:w-8 shrink-0" aria-hidden="true"></div>
        @endif
    </div>
</header>

<!-- Live Toast Notification Container (Fixed to Viewport, Top Right below Header) -->
<div id="sacLiveToastContainer" class="fixed top-20 right-4 sm:right-6 z-[9999] flex flex-col gap-3 pointer-events-none max-w-sm sm:max-w-md w-full px-4 sm:px-0"></div>

@if($hasRevisionNotice && $studentRevisionDoc)
    <!-- Student Revision Notice Pop-up Modal -->
    <div id="revisionNoticeModal" class="fixed inset-0 z-[10000] flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6 transition-all duration-300">
        <div class="w-full max-w-lg rounded-3xl bg-white shadow-2xl border border-gray-100 overflow-hidden transform transition-all animate-in fade-in zoom-in-95 duration-200">
            
            <!-- Top Color Accent Bar -->
            <div class="h-2 bg-gradient-to-r from-[#700000] via-rose-600 to-amber-500"></div>

            <div class="p-6 sm:p-7 space-y-5">
                <!-- Icon & Header -->
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-200 flex items-center justify-center shrink-0 shadow-xs">
                        <svg class="w-6 h-6 text-[#700000]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-base sm:text-lg font-bold text-gray-900 leading-tight">
                            Manuscript Revision Required
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Your submitted research manuscript requires revisions.
                        </p>
                    </div>
                </div>

                <!-- Manuscript Info Card -->
                <div class="rounded-2xl border border-gray-200 bg-slate-50/70 p-4 space-y-3">
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Research Title</p>
                        <h4 class="text-xs sm:text-sm font-bold text-gray-900 mt-0.5 leading-snug">
                            {{ $studentRevisionDoc->title }}
                        </h4>
                    </div>

                    @if($studentRevisionDoc->turnitin_similarity)
                        <div class="flex items-center gap-1.5 text-xs">
                            <span class="text-gray-500 font-medium">Turnitin Similarity:</span>
                            <span class="font-mono font-bold text-rose-700">
                                {{ $studentRevisionDoc->turnitin_similarity }}
                            </span>
                        </div>
                    @endif

                    @if(!empty($studentRevisionDoc->admin_notes))
                        <div class="pt-2.5 border-t border-gray-200/80 space-y-1">
                            <p class="text-[10px] uppercase font-bold text-rose-800 tracking-wider">Admin Feedback</p>
                            <p class="text-xs text-gray-800 whitespace-pre-line leading-relaxed">
                                {{ $studentRevisionDoc->admin_notes }}
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Revision Deadline Box (matching fifth photo) -->
                <div class="rounded-2xl border border-amber-200/80 bg-amber-50/70 p-4 sm:p-5 space-y-2.5">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-[#b45309] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h4 class="text-xs sm:text-sm font-bold text-gray-900 tracking-tight">
                            Revision Deadline
                        </h4>
                    </div>

                    <div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-[#b45309] tracking-tight">
                            {{ $revDaysRemaining }} days
                        </div>
                        <div class="text-xs text-amber-900/90 font-medium mt-0.5">
                            Remaining to resubmit your manuscript
                        </div>
                    </div>

                    <p class="text-xs text-gray-700 leading-relaxed">
                        Please upload your revised manuscript before <span class="font-semibold">{{ $revDeadline->format('F d, Y') }}</span>. Unresubmitted manuscripts will be automatically deleted after the 90-day revision period.
                    </p>
                </div>

                <!-- Action Buttons: "Go to Upload" (Red Button) & "Dismiss" (Beside it) -->
                <div class="pt-1 flex flex-col sm:flex-row items-center gap-2.5">
                    <a
                        href="{{ route('student.submit') }}"
                        onclick="dismissRevisionNoticeModal()"
                        class="w-full sm:flex-1 py-3 px-6 rounded-xl bg-[#700000] text-[#FFD700] hover:bg-[#850000] font-bold text-sm shadow-md hover:shadow-lg transition cursor-pointer flex items-center justify-center gap-2">
                        Go to Upload
                    </a>
                    <button
                        type="button"
                        onclick="dismissRevisionNoticeModal()"
                        class="w-full sm:w-auto text-center py-2.5 px-4 rounded-xl text-xs font-semibold text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition whitespace-nowrap cursor-pointer">
                        Dismiss
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function dismissRevisionNoticeModal() {
            const modal = document.getElementById('revisionNoticeModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
            try {
                const token = document.getElementById('sacTopHeader')?.dataset.csrf ||
                    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                fetch('/backend/student/dismiss-revision-notice', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    }
                }).catch(() => {});
            } catch (err) {
                console.warn('Failed to dismiss revision notice on server:', err);
            }
        }
    </script>
@endif

@if(in_array($userRole, ['admin', 'librarian', 'coordinator'], true))
    <!-- Admin Activity Logs Modal -->
    <div id="adminActivityLogsModal" class="fixed inset-0 z-[10000] hidden items-center justify-center bg-slate-900/60 backdrop-blur-xs p-3 sm:p-6 transition-all duration-300">
        <div class="w-full max-w-4xl max-h-[90vh] flex flex-col rounded-3xl bg-white shadow-2xl border border-gray-100 overflow-hidden transform transition-all animate-in fade-in zoom-in-95 duration-200">
            <!-- Top Gradient Bar -->
            <div class="h-2 bg-gradient-to-r from-[#700000] via-rose-600 to-amber-500 shrink-0"></div>

            <!-- Modal Header -->
            <div class="p-5 sm:p-6 border-b border-gray-100 flex items-start justify-between gap-4 shrink-0 bg-slate-50/50">
                <div class="flex items-center gap-3.5 min-w-0">
                    <div class="w-10 h-10 rounded-2xl bg-rose-50 border border-rose-200 flex items-center justify-center shrink-0 shadow-xs">
                        <svg class="w-5 h-5 text-[#700000]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-gray-900 tracking-tight flex items-center gap-2">
                            <span>Admin Activity Logs</span>
                            <span id="logHeaderBadge" class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-semibold font-mono">0 total</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            History of administrative actions: approving, requesting revisions, archiving, editing, and restoring.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    onclick="closeActivityLogsModal()"
                    class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                    title="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Filter Tabs & Search Bar -->
            <div class="p-4 sm:p-5 border-b border-gray-100 bg-white flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 shrink-0">
                <!-- Action Tabs -->
                <div class="flex items-center gap-1 overflow-x-auto pb-1 sm:pb-0" id="activityLogTabs">
                    <button
                        type="button"
                        onclick="setActivityLogFilter('all')"
                        id="logTab_all"
                        class="activity-log-tab px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap bg-[#700000] text-[#FFD700] shadow-xs cursor-pointer">
                        All (<span id="logCountAll">0</span>)
                    </button>
                    <button
                        type="button"
                        onclick="setActivityLogFilter('approve')"
                        id="logTab_approve"
                        class="activity-log-tab px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition whitespace-nowrap cursor-pointer">
                        Approved (<span id="logCountApprove">0</span>)
                    </button>
                    <button
                        type="button"
                        onclick="setActivityLogFilter('revision')"
                        id="logTab_revision"
                        class="activity-log-tab px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition whitespace-nowrap cursor-pointer">
                        Needs Revision (<span id="logCountRevision">0</span>)
                    </button>
                    <button
                        type="button"
                        onclick="setActivityLogFilter('archive')"
                        id="logTab_archive"
                        class="activity-log-tab px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition whitespace-nowrap cursor-pointer">
                        Archived (<span id="logCountArchive">0</span>)
                    </button>
                    <button
                        type="button"
                        onclick="setActivityLogFilter('edit')"
                        id="logTab_edit"
                        class="activity-log-tab px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition whitespace-nowrap cursor-pointer">
                        Edited (<span id="logCountEdit">0</span>)
                    </button>
                </div>

                <!-- Search Input -->
                <div class="relative w-full sm:w-64">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <input
                        type="text"
                        id="activityLogSearchInput"
                        oninput="handleActivityLogSearch(event)"
                        placeholder="Search logs..."
                        class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#700000]/20 focus:border-[#700000] transition" />
                </div>
            </div>

            <!-- Logs List Body (Scrollable) -->
            <div id="activityLogsBody" class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-3 min-h-[260px] max-h-[55vh] bg-slate-50/30">
                <div class="py-12 text-center text-xs text-slate-400 font-medium">
                    Loading activity history...
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-4 sm:p-5 border-t border-gray-100 bg-white flex items-center justify-between shrink-0">
                <span class="text-xs text-slate-500 font-medium" id="activityLogsFooterCount">
                    Showing 0 activities
                </span>
                <button
                    type="button"
                    onclick="closeActivityLogsModal()"
                    class="py-2 px-5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>
@endif

<script>
    // Header Notifications Logic
    function dismissLiveToast(toastId) {
        const toast = document.getElementById(toastId);
        if (!toast) return;
        toast.classList.remove('translate-y-0', 'opacity-100');
        toast.classList.add('-translate-y-2', 'opacity-0');
        setTimeout(() => {
            if (toast && toast.parentNode) {
                toast.remove();
            }
        }, 350);
    }

    function showLiveToast(n) {
        const container = document.getElementById('sacLiveToastContainer');
        if (!container) return;

        const isCleared = (n.type === 'cleared' || n.type === 'approved');
        const isResubmit = (n.type === 'resubmit');
        const borderCol = isCleared ? 'border-emerald-300' : (isResubmit ? 'border-rose-300' : 'border-amber-300');
        const titleCol = isCleared ? 'text-emerald-900' : (isResubmit ? 'text-rose-900' : 'text-blue-900');
        const badgeCol = isCleared ? 'bg-emerald-600 text-white' : (isResubmit ? 'bg-rose-600 text-white' : 'bg-blue-600 text-white');
        const linkUrl = n.link || '/student/submit';

        const toastId = 'toast_' + Math.random().toString(36).substring(2, 9);
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `pointer-events-auto rounded-2xl border ${borderCol} p-4 shadow-2xl bg-white/95 backdrop-blur-md transition-all duration-300 transform -translate-y-4 opacity-0 flex items-start gap-3.5`;
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
                    <button type="button" onclick="dismissLiveToast('${toastId}')" class="text-gray-400 hover:text-gray-700 p-1 rounded-lg hover:bg-gray-100 transition cursor-pointer" title="Dismiss notification">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <p class="text-[11px] text-gray-700 mt-1 leading-snug line-clamp-2">${n.message}</p>
                <div class="mt-2.5 flex items-center gap-2">
                    <a href="${linkUrl}" onclick="handleHeaderNotifClick('${n.id}', '${linkUrl}', event)" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#700000] text-[#FFD700] hover:bg-[#850000] text-[11px] font-bold shadow-xs transition cursor-pointer">
                        <span>View Details</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>
        `;

        container.appendChild(toast);
        requestAnimationFrame(() => {
            toast.classList.remove('-translate-y-4', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
        });

        setTimeout(() => {
            dismissLiveToast(toastId);
        }, 8000);

        if ('Notification' in window && Notification.permission === 'granted') {
            try {
                new Notification(n.title, {
                    body: n.message.substring(0, 140),
                    icon: '/images/airis-logo.webp'
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

            // Check for new unread notifications and pop up live toast (limit to newest unread only)
            if (data.notifications && data.notifications.length > 0) {
                const unreadNotifs = data.notifications.filter(n => !n.is_read);
                if (unreadNotifs.length > 0) {
                    const newest = unreadNotifs[0];
                    const storageKey = 'sac_toast_shown_' + newest.id;
                    if (!sessionStorage.getItem(storageKey)) {
                        sessionStorage.setItem(storageKey, '1');
                        showLiveToast(newest);
                    }
                }
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

        const profileDd = document.getElementById('adminProfileDropdown');
        const profileBtn = document.getElementById('adminProfileMenuBtn');
        if (profileDd && profileBtn && !profileDd.contains(e.target) && !profileBtn.contains(e.target)) {
            profileDd.classList.add('hidden');
        }
    });

    // Admin Profile & Activity Logs Logic
    function toggleAdminProfileDropdown(e) {
        if (e) e.stopPropagation();
        const dd = document.getElementById('adminProfileDropdown');
        if (!dd) return;
        dd.classList.toggle('hidden');
    }

    let currentActivityLogFilter = 'all';
    let currentActivityLogSearch = '';
    let activityLogSearchTimeout = null;

    function openActivityLogsModal(e) {
        if (e) e.stopPropagation();
        const dd = document.getElementById('adminProfileDropdown');
        if (dd) dd.classList.add('hidden');

        const modal = document.getElementById('adminActivityLogsModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            fetchActivityLogs();
        }
    }

    function closeActivityLogsModal() {
        const modal = document.getElementById('adminActivityLogsModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function setActivityLogFilter(filter) {
        currentActivityLogFilter = filter;
        document.querySelectorAll('.activity-log-tab').forEach(tab => {
            tab.className = 'activity-log-tab px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition whitespace-nowrap cursor-pointer';
        });
        const activeTab = document.getElementById(`logTab_${filter}`);
        if (activeTab) {
            activeTab.className = 'activity-log-tab px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap bg-[#700000] text-[#FFD700] shadow-xs cursor-pointer';
        }
        fetchActivityLogs();
    }

    function handleActivityLogSearch(e) {
        clearTimeout(activityLogSearchTimeout);
        currentActivityLogSearch = e.target.value.trim();
        activityLogSearchTimeout = setTimeout(() => {
            fetchActivityLogs();
        }, 300);
    }

    async function fetchActivityLogs() {
        const body = document.getElementById('activityLogsBody');
        if (!body) return;

        body.innerHTML = `
            <div class="py-12 text-center text-xs text-slate-400 font-medium flex flex-col items-center justify-center gap-2">
                <svg class="w-6 h-6 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Loading activity history...</span>
            </div>
        `;

        try {
            const url = `/backend/admin/activity-logs?action=${encodeURIComponent(currentActivityLogFilter)}&search=${encodeURIComponent(currentActivityLogSearch)}`;
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) throw new Error('Failed to fetch activity logs');
            const data = await res.json();

            // Update badge counts
            if (data.counts) {
                const cAll = document.getElementById('logCountAll');
                const cApp = document.getElementById('logCountApprove');
                const cRev = document.getElementById('logCountRevision');
                const cArc = document.getElementById('logCountArchive');
                const cEd = document.getElementById('logCountEdit');
                const hBadge = document.getElementById('logHeaderBadge');

                if (cAll) cAll.textContent = data.counts.all || 0;
                if (cApp) cApp.textContent = data.counts.approve || 0;
                if (cRev) cRev.textContent = data.counts.revision || 0;
                if (cArc) cArc.textContent = data.counts.archive || 0;
                if (cEd) cEd.textContent = data.counts.edit || 0;
                if (hBadge) hBadge.textContent = `${data.counts.all || 0} total`;
            }

            renderActivityLogs(data.logs || []);
        } catch (err) {
            console.error('Error loading activity logs:', err);
            body.innerHTML = `
                <div class="py-12 text-center text-xs text-rose-500 font-medium">
                    Failed to load activity logs. Please try again.
                </div>
            `;
        }
    }

    function renderActivityLogs(logs) {
        const body = document.getElementById('activityLogsBody');
        const footerCount = document.getElementById('activityLogsFooterCount');
        if (!body) return;

        if (footerCount) {
            footerCount.textContent = `Showing ${logs.length} ${logs.length === 1 ? 'activity' : 'activities'}`;
        }

        if (!logs || logs.length === 0) {
            body.innerHTML = `
                <div class="py-12 text-center text-xs text-slate-400 font-medium space-y-1">
                    <p class="font-bold text-slate-600">No activity logs recorded yet.</p>
                    <p class="text-slate-400">Actions like approving, requesting revisions, archiving, and editing will appear here.</p>
                </div>
            `;
            return;
        }

        body.innerHTML = logs.map(log => {
            const act = log.action ? log.action.toLowerCase() : '';
            let badgeHtml = '';
            let borderStyle = 'border-slate-200';

            if (act === 'approve' || act === 'approved') {
                borderStyle = 'border-emerald-200 hover:border-emerald-300';
                badgeHtml = `
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Approved
                    </span>
                `;
            } else if (act === 'revision_request' || act === 'revision' || act === 'reject') {
                borderStyle = 'border-rose-200 hover:border-rose-300';
                badgeHtml = `
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-800 border border-rose-200">
                        <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                        Needs Revision
                    </span>
                `;
            } else if (act === 'archive' || act === 'archived') {
                borderStyle = 'border-amber-200 hover:border-amber-300';
                badgeHtml = `
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                        </svg>
                        Archived
                    </span>
                `;
            } else if (act === 'restore') {
                borderStyle = 'border-sky-200 hover:border-sky-300';
                badgeHtml = `
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
                        <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Restored
                    </span>
                `;
            } else if (act === 'edit' || act === 'edited' || act === 'update') {
                borderStyle = 'border-blue-200 hover:border-blue-300';
                badgeHtml = `
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                        </svg>
                        Edited
                    </span>
                `;
            } else if (act === 'upload') {
                borderStyle = 'border-purple-200 hover:border-purple-300';
                badgeHtml = `
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-50 text-purple-800 border border-purple-200">
                        <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                        Uploaded
                    </span>
                `;
            } else {
                borderStyle = 'border-slate-200 hover:border-slate-300';
                badgeHtml = `
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-800 border border-slate-200">
                        ${escapeHtml(log.action || 'Activity')}
                    </span>
                `;
            }

            const formattedDate = log.created_at ? new Date(log.created_at).toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }) : '';

            const docLink = log.document_id ? `<a href="/documents/${log.document_id}" target="_blank" class="hover:text-[#700000] hover:underline">${escapeHtml(log.document_title || 'Untitled Manuscript')}</a>` : `<span>${escapeHtml(log.document_title || 'Untitled Manuscript')}</span>`;

            return `
                <div class="p-4 rounded-2xl bg-white border ${borderStyle} shadow-2xs hover:shadow-xs transition space-y-2">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2 flex-wrap">
                            ${badgeHtml}
                            <span class="text-xs font-bold text-slate-900 leading-snug">
                                ${docLink}
                            </span>
                        </div>
                        <span class="text-[11px] text-slate-400 font-mono shrink-0 whitespace-nowrap">
                            ${formattedDate}
                        </span>
                    </div>

                    ${log.details ? `
                        <p class="text-xs text-slate-600 leading-relaxed pl-2 border-l-2 border-slate-200">
                            ${escapeHtml(log.details)}
                        </p>
                    ` : ''}

                    <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1 border-t border-slate-100">
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            <span>${escapeHtml(log.admin_name || 'Administrator')}</span>
                            <span class="text-slate-300">&bull;</span>
                            <span class="text-slate-400">${escapeHtml(log.admin_email || '')}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Close activity logs modal on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeActivityLogsModal();
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
