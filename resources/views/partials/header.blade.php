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
        $isSubmitPage = request()->routeIs('student.submit') || request()->is('student/submit*');
        if (!session()->has('revision_notice_dismissed') && !$isSubmitPage) {
            $studentRevisionDoc = \App\Models\Document::where('submitted_by_email', strtolower($userEmail))
                ->where('status', 'resubmit')
                ->latest('updated_at')
                ->first();
            if ($studentRevisionDoc) {
                $hasRevisionNotice = true;
                $revDeadline = $studentRevisionDoc->updated_at ? $studentRevisionDoc->updated_at->copy()->addDays(90) : now()->addDays(90);
                $revDaysRemaining = max(0, (int) ceil(now()->floatDiffInDays($revDeadline, false)));
                $maxFreeScans = 3;
                $currentAttempts = (int) ($studentRevisionDoc->scan_attempts ?: 1);
                $freeScansRemaining = max(0, $maxFreeScans - $currentAttempts);
                $isExceededFree = ($currentAttempts >= $maxFreeScans);
            }
        }
    }
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .font-inter, .font-date-time {
        font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
        font-weight: 400 !important;
    }
</style>

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
                            class="hidden absolute right-0 top-full mt-2 w-56 rounded-2xl bg-white p-2 shadow-2xl z-50 text-left transition-all">
                            <div class="px-3 py-2 bg-slate-50/80 rounded-xl mb-1">
                                <p class="text-xs font-bold text-gray-900 truncate">{{ $userName }}</p>
                                <p class="text-[11px] text-gray-500 truncate">{{ $userEmail }}</p>
                            </div>

                            <button
                                type="button"
                                onclick="openHeaderAddStudentModal(event)"
                                class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-[#700000] transition cursor-pointer text-left group">
                                <svg class="w-4 h-4 text-slate-500 group-hover:text-[#700000]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                                </svg>
                                <span>Add Student</span>
                            </button>

                            <button
                                type="button"
                                onclick="openHeaderStudentDirectoryModal(event)"
                                class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-[#700000] transition cursor-pointer text-left group">
                                <svg class="w-4 h-4 text-slate-500 group-hover:text-[#700000]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                </svg>
                                <span>Student Directory</span>
                            </button>

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
                @else
                    <!-- Student Profile User SVG Button & Dropdown Menu -->
                    <div class="relative">
                        <button
                            type="button"
                            id="studentProfileMenuBtn"
                            onclick="toggleStudentProfileDropdown(event)"
                            title="Student Profile & Options"
                            aria-label="Student Profile & Options"
                            class="p-1 sm:p-1.5 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition cursor-pointer flex items-center justify-center focus:outline-none">
                            <svg class="w-5 h-5 text-slate-600 hover:text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                        </button>

                        <!-- Student Profile Dropdown -->
                        <div
                            id="studentProfileDropdown"
                            class="hidden absolute right-0 top-full mt-2 w-56 rounded-2xl bg-white p-2 shadow-2xl z-50 text-left transition-all">
                            <div class="px-3 py-2 bg-slate-50/80 rounded-xl mb-1">
                                <p class="text-xs font-bold text-gray-900 truncate">{{ $userName }}</p>
                                <p class="text-[11px] text-gray-500 truncate">{{ $userEmail }}</p>
                                @if(session('sac_user_lrn'))
                                    <p class="text-[10.5px] text-[#700000] font-mono font-semibold mt-0.5">LRN: {{ session('sac_user_lrn') }}</p>
                                @endif
                            </div>

                            <button
                                type="button"
                                id="studentAccountChangePasswordBtn"
                                onclick="openStudentChangePasswordModal(event)"
                                class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-[#700000] transition cursor-pointer text-left group">
                                <svg class="w-4 h-4 text-slate-500 group-hover:text-[#700000]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                                </svg>
                                <span>Account</span>
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
    <!-- Student Revision Notice Pop-up Modal (Responsive & Viewport-Safe) -->
    <div id="revisionNoticeModal" class="fixed inset-0 z-[10000] overflow-y-auto flex items-center justify-center p-3 sm:p-5 bg-slate-900/60 backdrop-blur-xs transition-all duration-300">
        <script>
            if (sessionStorage.getItem('sac_revision_notice_dismissed') === '1') {
                const _rm = document.getElementById('revisionNoticeModal');
                if (_rm) _rm.remove();
            }
        </script>
        <div class="w-full max-w-lg max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-3.5rem)] my-auto rounded-3xl bg-white shadow-2xl overflow-hidden flex flex-col transform transition-all animate-in fade-in zoom-in-95 duration-200">
            
            <!-- Pinned Modal Header (AIRIS Maroon Style matching Picture 2) -->
            <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-[#600000] bg-[#700000] text-white shrink-0">
                <div class="flex items-center gap-3 min-w-0 pr-2">
                    <div class="w-8 h-8 rounded-xl bg-white p-1 shrink-0 shadow-sm border border-[#D4AF37]/40 flex items-center justify-center">
                        <img src="{{ asset('images/airis-logo.webp') }}" class="w-full h-full object-contain" alt="AIRIS Logo">
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-white tracking-wide truncate flex items-center gap-1.5">
                            <span class="text-[#FFD700] font-black tracking-wide font-['Outfit',sans-serif]">AIRIS</span>
                            <span>Manuscript Revision Required</span>
                        </h3>
                        <p class="text-[11px] sm:text-xs text-amber-100/90 truncate mt-0.5">
                            Your submitted research manuscript requires revisions.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    onclick="dismissRevisionNoticeModal()"
                    aria-label="Close Revision Notice"
                    class="p-1.5 rounded-lg text-amber-100/80 hover:text-white hover:bg-white/10 transition cursor-pointer shrink-0"
                    title="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Scrollable Modal Body (Smoothly scrolls if viewport height is compact) -->
            <div class="flex-1 overflow-y-auto px-5 sm:px-6 py-4 space-y-3.5 overscroll-contain">
                <!-- Manuscript Info Card -->
                <div class="rounded-2xl bg-slate-50/90 p-4 space-y-2.5 shadow-2xs">
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Research Title</p>
                        <h4 class="text-xs sm:text-sm font-bold text-gray-900 mt-0.5 leading-snug">
                            {{ $studentRevisionDoc->title }}
                        </h4>
                    </div>

                    @if($studentRevisionDoc->turnitin_similarity)
                        <div class="flex items-center gap-1.5 text-xs">
                            <span class="text-gray-500 font-medium">Turnitin Similarity:</span>
                            <span class="font-inter font-semibold text-rose-700">
                                {{ $studentRevisionDoc->turnitin_similarity }}
                            </span>
                        </div>
                    @endif

                    @if(!empty($studentRevisionDoc->admin_notes))
                        <div class="pt-1.5 space-y-1">
                            <p class="text-[10px] uppercase font-bold text-rose-800 tracking-wider">Admin Feedback</p>
                            <p class="text-xs text-gray-800 whitespace-pre-line leading-relaxed">
                                {{ $studentRevisionDoc->admin_notes }}
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Turnitin & Grammarly Free Scanning Quota Notice Box (100% Borderless Design) -->
                <div class="rounded-2xl {{ $isExceededFree ? 'bg-rose-50/80' : 'bg-slate-50/90' }} p-4 space-y-2.5 shadow-2xs">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <svg class="w-4 h-4 {{ $isExceededFree ? 'text-rose-700' : 'text-[#700000]' }} shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-2.182 7.027a9 9 0 118.232-4.492" />
                            </svg>
                            <h4 class="text-xs sm:text-sm font-bold text-gray-900 tracking-tight truncate">
                                Turnitin &amp; Grammarly Scanning Quota
                            </h4>
                        </div>
                        <span class="inline-flex items-center text-[10.5px] font-bold px-2.5 py-0.5 rounded-full {{ $isExceededFree ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-900' }} shrink-0">
                            {{ $isExceededFree ? 'Free Tries Exhausted' : "{$freeScansRemaining} of {$maxFreeScans} Free Tries Left" }}
                        </span>
                    </div>

                    <!-- 3-Slot Visual Try Progress Indicator (Borderless) -->
                    <div class="grid grid-cols-3 gap-2 pt-0.5">
                        <div class="rounded-xl px-2.5 py-1.5 text-center {{ $currentAttempts >= 1 ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800' }}">
                            <p class="text-[9px] uppercase font-bold tracking-wider opacity-75">Scan 1</p>
                            <p class="text-[11px] font-extrabold leading-tight mt-0.5">{{ $currentAttempts >= 1 ? 'Used' : 'Available' }}</p>
                        </div>
                        <div class="rounded-xl px-2.5 py-1.5 text-center {{ $currentAttempts >= 2 ? 'bg-rose-100 text-rose-800' : ($currentAttempts === 1 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200/60 text-slate-500') }}">
                            <p class="text-[9px] uppercase font-bold tracking-wider opacity-75">Scan 2</p>
                            <p class="text-[11px] font-extrabold leading-tight mt-0.5">{{ $currentAttempts >= 2 ? 'Used' : ($currentAttempts === 1 ? 'Next' : 'Available') }}</p>
                        </div>
                        <div class="rounded-xl px-2.5 py-1.5 text-center {{ $currentAttempts >= 3 ? 'bg-rose-100 text-rose-800' : ($currentAttempts === 2 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200/60 text-slate-500') }}">
                            <p class="text-[9px] uppercase font-bold tracking-wider opacity-75">Scan 3</p>
                            <p class="text-[11px] font-extrabold leading-tight mt-0.5">{{ $currentAttempts >= 3 ? 'Used' : ($currentAttempts === 2 ? 'Next' : 'Available') }}</p>
                        </div>
                    </div>

                    <!-- Policy Explanation -->
                    @if(!$isExceededFree)
                        <p class="text-xs text-gray-700 leading-relaxed">
                            <span class="font-bold text-gray-900">Scanning Quota Notice:</span> Each research group receives <strong>three (3) free Turnitin and Grammarly scans</strong>. You have used <span class="font-bold text-rose-700">{{ $currentAttempts }}</span> of 3 scans (<span class="font-bold text-amber-900">{{ $freeScansRemaining }} {{ Str::plural('try', $freeScansRemaining) }} remaining</span>). If your manuscript still does not pass after 3 scans, you must pay for the 4th scan.
                        </p>
                    @else
                        <div class="rounded-xl bg-rose-100/70 p-3 text-xs text-rose-950 leading-relaxed font-medium">
                            <span class="font-bold text-rose-900">Payment Required for 4th Scan:</span> Your group has exhausted all 3 free Turnitin and Grammarly screening attempts. In accordance with RESO regulations, you must settle the re-screening fee at the Cashier / RESO office for your 4th manuscript scan before clearance review can proceed.
                        </div>
                    @endif
                </div>

                <!-- Revision Deadline Box (matching option 1 borderless design) -->
                <div class="rounded-2xl bg-amber-50/80 p-4 space-y-2 shadow-2xs">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#b45309] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
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
                        Please upload your revised manuscript before <span class="font-semibold font-inter">{{ $revDeadline->format('F d, Y') }}</span>. Unresubmitted manuscripts will be automatically deleted after the 90-day revision period.
                    </p>
                </div>
            </div>

            <!-- Pinned Modal Footer (Always Visible) -->
            <div class="shrink-0 bg-slate-50/90 px-5 sm:px-6 py-3.5 border-t border-gray-100 flex flex-col sm:flex-row items-center gap-2.5">
                <button
                    type="button"
                    onclick="dismissRevisionNoticeModal('{{ route('student.submit') }}')"
                    class="w-full sm:flex-1 py-2.5 sm:py-3 px-6 rounded-xl bg-[#700000] text-[#FFD700] hover:bg-[#850000] font-bold text-sm shadow-md hover:shadow-lg transition cursor-pointer flex items-center justify-center gap-2">
                    Go to Upload
                </button>
                <button
                    type="button"
                    onclick="dismissRevisionNoticeModal()"
                    class="w-full sm:w-auto text-center py-2 px-4 rounded-xl text-xs font-semibold text-gray-600 hover:text-gray-900 hover:bg-gray-200/60 transition whitespace-nowrap cursor-pointer">
                    Dismiss
                </button>
            </div>
        </div>
    </div>

    <script>
        function dismissRevisionNoticeModal(targetUrl = null) {
            const modal = document.getElementById('revisionNoticeModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            try {
                sessionStorage.setItem('sac_revision_notice_dismissed', '1');
                document.cookie = "sac_rev_dismissed=1; path=/; max-age=86400; SameSite=Lax";
            } catch (e) {}

            try {
                const token = document.getElementById('sacTopHeader')?.dataset.csrf ||
                    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                fetch('/backend/student/dismiss-revision-notice', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    keepalive: true
                }).catch(() => {});
            } catch (err) {
                console.warn('Failed to dismiss revision notice on server:', err);
            }

            if (targetUrl) {
                window.location.href = targetUrl;
            }
        }
    </script>
@endif

@if(in_array($userRole, ['admin', 'librarian', 'coordinator'], true))
    <!-- Admin Activity Logs Modal -->
    <div id="adminActivityLogsModal" class="fixed inset-0 z-[10000] hidden items-center justify-center bg-slate-900/60 backdrop-blur-xs p-3 sm:p-6 transition-all duration-300">
        <div class="w-full max-w-4xl max-h-[90vh] flex flex-col rounded-3xl bg-white shadow-2xl overflow-hidden transform transition-all animate-in fade-in zoom-in-95 duration-200">
            <!-- Modal Header (AIRIS Maroon Style matching Picture 2) -->
            <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-[#600000] bg-[#700000] text-white shrink-0">
                <div class="flex items-center gap-3 min-w-0 pr-2">
                    <div class="w-8 h-8 rounded-xl bg-white p-1 shrink-0 shadow-sm border border-[#D4AF37]/40 flex items-center justify-center">
                        <img src="{{ asset('images/airis-logo.webp') }}" class="w-full h-full object-contain" alt="AIRIS Logo">
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-white tracking-wide truncate flex items-center gap-1.5">
                            <span class="text-[#FFD700] font-black tracking-wide font-['Outfit',sans-serif]">AIRIS</span>
                            <span>Admin Activity Logs</span>
                        </h3>
                        <p class="text-[11px] sm:text-xs text-amber-100/90 truncate mt-0.5">
                            History of administrative actions: approving, requesting revisions, archiving, editing, and restoring.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    onclick="closeActivityLogsModal()"
                    aria-label="Close Activity Logs"
                    class="p-1.5 rounded-lg text-amber-100/80 hover:text-white hover:bg-white/10 transition cursor-pointer shrink-0"
                    title="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Filter Tabs & Search Bar -->
            <div class="p-4 sm:p-5 bg-white flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 shrink-0">
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
                        class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl bg-slate-100 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#700000]/20 transition" />
                </div>
            </div>

            <!-- Logs List Body (Scrollable) -->
            <div id="activityLogsBody" class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-3 min-h-[260px] max-h-[60vh] bg-slate-50/30">
                <div class="py-12 text-center text-xs text-slate-400 font-medium">
                    Loading activity history...
                </div>
            </div>


        </div>
    </div>

    <!-- Admin: Add Student Modal (Global Header) -->
    <div id="adminAddStudentModal" class="fixed inset-0 z-[10000] hidden items-center justify-center bg-slate-900/60 backdrop-blur-xs p-3 sm:p-6 transition-all duration-300">
        <div class="w-full max-w-lg rounded-3xl bg-white shadow-2xl overflow-hidden transform transition-all animate-in fade-in zoom-in-95 duration-200">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-[#600000] bg-[#700000] text-white shrink-0">
                <div class="flex items-center gap-3 min-w-0 pr-2">
                    <div class="w-8 h-8 rounded-xl bg-white p-1 shrink-0 shadow-sm border border-[#D4AF37]/40 flex items-center justify-center">
                        <img src="{{ asset('images/airis-logo.webp') }}" class="w-full h-full object-contain" alt="AIRIS Logo">
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-white tracking-wide truncate flex items-center gap-1.5">
                            <span class="text-[#FFD700] font-black tracking-wide font-['Outfit',sans-serif]">AIRIS</span>
                            <span>Register Student Account</span>
                        </h3>
                    </div>
                </div>

                <button
                    type="button"
                    onclick="closeHeaderAddStudentModal()"
                    aria-label="Close"
                    class="p-1.5 rounded-lg text-amber-100/80 hover:text-white hover:bg-white/10 transition cursor-pointer shrink-0"
                    title="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Form Body -->
            <div class="p-6 space-y-4">
                <div id="headerAddStudentAlert" class="hidden"></div>

                <form id="headerAddStudentForm" onsubmit="submitHeaderAddStudent(event)" class="space-y-4">
                    @csrf
                    <div>
                        <label for="hdr_student_name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Full Name <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="hdr_student_name"
                            name="name"
                            required
                            placeholder="Enter Fullname"
                            class="w-full rounded-2xl border border-gray-200 bg-slate-50/70 px-4 py-2.5 text-xs sm:text-sm text-gray-900 outline-none focus:border-[#700000] focus:bg-white focus:ring-2 focus:ring-[#700000]/10 transition shadow-2xs">
                    </div>

                    <div>
                        <label for="hdr_student_email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            School Email <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="email"
                            id="hdr_student_email"
                            name="email"
                            required
                            placeholder="you@sac.edu.ph"
                            class="w-full rounded-2xl border border-gray-200 bg-slate-50/70 px-4 py-2.5 text-xs sm:text-sm text-gray-900 outline-none focus:border-[#700000] focus:bg-white focus:ring-2 focus:ring-[#700000]/10 transition shadow-2xs">
                    </div>

                    <div>
                        <label for="hdr_student_lrn" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            12-Digit Learner Reference Number (LRN) <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="hdr_student_lrn"
                            name="lrn"
                            required
                            maxlength="12"
                            pattern="[0-9]{12}"
                            inputmode="numeric"
                            placeholder="Enter LRN"
                            class="w-full rounded-2xl border border-gray-200 bg-slate-50/70 px-4 py-2.5 text-xs sm:text-sm text-gray-900 outline-none focus:border-[#700000] focus:bg-white focus:ring-2 focus:ring-[#700000]/10 transition shadow-2xs font-mono font-semibold">
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100">
                        <button
                            type="button"
                            onclick="closeHeaderAddStudentModal()"
                            class="rounded-xl border border-gray-200 px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-slate-100 transition cursor-pointer">
                            Cancel
                        </button>
                        <button
                            type="submit"
                            id="submitHeaderAddStudentBtn"
                            class="rounded-xl bg-[#700000] hover:bg-[#850000] px-5 py-2.5 text-xs font-bold text-[#FFD700] transition shadow-md shadow-red-950/20 border border-[#FFD700]/30 cursor-pointer flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            <span>Register Student</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Admin: Student Directory Modal (Global Header) -->
    <div id="adminStudentDirectoryModal" class="fixed inset-0 z-[10000] hidden items-center justify-center bg-slate-900/60 backdrop-blur-xs p-3 sm:p-6 transition-all duration-300">
        <div class="w-full max-w-3xl max-h-[90vh] flex flex-col rounded-3xl bg-white shadow-2xl overflow-hidden transform transition-all animate-in fade-in zoom-in-95 duration-200">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-[#600000] bg-[#700000] text-white shrink-0">
                <div class="flex items-center gap-3 min-w-0 pr-2">
                    <div class="w-8 h-8 rounded-xl bg-white p-1 shrink-0 shadow-sm border border-[#D4AF37]/40 flex items-center justify-center">
                        <img src="{{ asset('images/airis-logo.webp') }}" class="w-full h-full object-contain" alt="AIRIS Logo">
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-white tracking-wide truncate flex items-center gap-1.5">
                            <span class="text-[#FFD700] font-black tracking-wide font-['Outfit',sans-serif]">AIRIS</span>
                            <span>Registered Student Directory</span>
                        </h3>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        onclick="closeHeaderStudentDirectoryModal(); openHeaderAddStudentModal();"
                        class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-[#FFD700] text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span>Add New</span>
                    </button>
                    <button
                        type="button"
                        onclick="closeHeaderStudentDirectoryModal()"
                        class="p-1.5 rounded-lg text-amber-100/80 hover:text-white hover:bg-white/10 transition cursor-pointer"
                        title="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="p-4 bg-slate-50 border-b border-gray-100 shrink-0">
                <input
                    type="text"
                    id="headerStudentSearchInput"
                    oninput="debounceHeaderStudentSearch(this.value)"
                    placeholder="Search by student name, institutional email, or 12-digit LRN..."
                    class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-2 text-xs sm:text-sm text-gray-900 outline-none focus:border-[#700000] focus:ring-2 focus:ring-[#700000]/10 transition shadow-2xs">
            </div>

            <!-- Table Body -->
            <div class="flex-1 overflow-y-auto p-2">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 text-[10.5px] font-bold text-gray-600 uppercase tracking-wider sticky top-0 rounded-xl">
                        <tr>
                            <th class="py-2.5 px-3">Student Name</th>
                            <th class="py-2.5 px-3">Institutional Email</th>
                            <th class="py-2.5 px-3">12-Digit LRN</th>
                            <th class="py-2.5 px-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="headerStudentTableBody" class="divide-y divide-gray-100 bg-white">
                        <tr>
                            <td colspan="4" class="py-8 text-center text-gray-400 text-xs">Loading directory...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Admin: Edit/Reset Student Password Modal -->
    <div id="adminEditStudentPasswordModal" class="fixed inset-0 z-[10001] hidden items-center justify-center bg-slate-900/60 backdrop-blur-xs p-3 sm:p-6 transition-all duration-300">
        <div class="w-full max-w-md rounded-3xl bg-white shadow-2xl overflow-hidden transform transition-all animate-in fade-in zoom-in-95 duration-200">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-[#600000] bg-[#700000] text-white shrink-0">
                <div class="flex items-center gap-3 min-w-0 pr-2">
                    <div class="w-8 h-8 rounded-xl bg-white p-1 shrink-0 shadow-sm border border-[#D4AF37]/40 flex items-center justify-center">
                        <img src="{{ asset('images/airis-logo.webp') }}" class="w-full h-full object-contain" alt="AIRIS Logo">
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-white tracking-wide truncate flex items-center gap-1.5">
                            <span class="text-[#FFD700] font-black tracking-wide font-['Outfit',sans-serif]">AIRIS</span>
                            <span>Edit Student Password</span>
                        </h3>
                    </div>
                </div>

                <button
                    type="button"
                    onclick="closeAdminEditStudentPasswordModal()"
                    aria-label="Close"
                    class="p-1.5 rounded-lg text-amber-100/80 hover:text-white hover:bg-white/10 transition cursor-pointer shrink-0"
                    title="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-4">
                <div id="adminEditStudentPasswordAlert" class="hidden"></div>

                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-2xl">
                    <p id="editStudentModalName" class="text-xs font-bold text-gray-900"></p>
                    <p id="editStudentModalEmail" class="text-[11px] text-gray-500 font-mono"></p>
                    <p id="editStudentModalLrn" class="text-[11px] text-[#700000] font-mono font-semibold mt-0.5"></p>
                </div>

                <form id="adminEditStudentPasswordForm" onsubmit="submitAdminEditStudentPassword(event)" class="space-y-4">
                    @csrf
                    <input type="hidden" id="edit_student_id">
                    <input type="hidden" id="edit_student_lrn_hidden">

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="edit_student_new_password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                New Password <span class="text-rose-500">*</span>
                            </label>
                            <button
                                type="button"
                                onclick="fillStudentLrnAsPassword()"
                                class="text-[11px] text-[#700000] hover:text-[#900000] font-semibold hover:underline cursor-pointer">
                                Reset to 12-Digit LRN
                            </button>
                        </div>
                        <div class="relative">
                            <input
                                type="password"
                                id="edit_student_new_password"
                                required
                                minlength="6"
                                placeholder="Enter new password (min. 6 characters)"
                                class="w-full rounded-2xl border border-gray-200 bg-slate-50/70 px-4 py-2.5 text-xs sm:text-sm text-gray-900 outline-none focus:border-[#700000] focus:bg-white focus:ring-2 focus:ring-[#700000]/10 transition shadow-2xs pr-10">
                            <button
                                type="button"
                                onclick="togglePasswordVisibility('edit_student_new_password', this)"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100">
                        <button
                            type="button"
                            onclick="closeAdminEditStudentPasswordModal()"
                            class="rounded-xl border border-gray-200 px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-slate-100 transition cursor-pointer">
                            Cancel
                        </button>
                        <button
                            type="submit"
                            id="submitAdminEditStudentPasswordBtn"
                            class="rounded-xl bg-[#700000] hover:bg-[#850000] px-5 py-2.5 text-xs font-bold text-[#FFD700] transition shadow-md shadow-red-950/20 border border-[#FFD700]/30 cursor-pointer flex items-center gap-1.5">
                            <span>Update Password</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<!-- Student: Change Password Modal (Global Header) -->
<div id="studentChangePasswordModal" class="fixed inset-0 z-[10000] hidden items-center justify-center bg-slate-900/60 backdrop-blur-xs p-3 sm:p-6 transition-all duration-300">
    <div class="w-full max-w-md rounded-3xl bg-white shadow-2xl overflow-hidden transform transition-all animate-in fade-in zoom-in-95 duration-200">
        <!-- Modal Header (AIRIS Maroon Style) -->
        <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-[#600000] bg-[#700000] text-white shrink-0">
            <div class="flex items-center gap-3 min-w-0 pr-2">
                <div class="w-8 h-8 rounded-xl bg-white p-1 shrink-0 shadow-sm border border-[#D4AF37]/40 flex items-center justify-center">
                    <img src="{{ asset('images/airis-logo.webp') }}" class="w-full h-full object-contain" alt="AIRIS Logo">
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm sm:text-base font-bold text-white tracking-wide truncate flex items-center gap-1.5">
                        <span class="text-[#FFD700] font-black tracking-wide font-['Outfit',sans-serif]">AIRIS</span>
                        <span>Account Settings</span>
                    </h3>
                    <p class="text-[11px] sm:text-xs text-amber-100/90 truncate mt-0.5">
                        Change your institutional account password
                    </p>
                </div>
            </div>

            <button
                type="button"
                onclick="closeStudentChangePasswordModal()"
                aria-label="Close"
                class="p-1.5 rounded-lg text-amber-100/80 hover:text-white hover:bg-white/10 transition cursor-pointer shrink-0"
                title="Close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Form Body -->
        <div class="p-6 space-y-4">
            <div id="studentChangePasswordAlert" class="hidden"></div>

            <form id="studentChangePasswordForm" onsubmit="submitStudentChangePassword(event)" class="space-y-4">
                @csrf
                <div>
                    <label for="current_password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Current Password <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            id="current_password"
                            name="current_password"
                            required
                            placeholder="Enter current password or initial LRN"
                            class="w-full rounded-2xl border border-gray-200 bg-slate-50/70 px-4 py-2.5 text-xs sm:text-sm text-gray-900 outline-none focus:border-[#700000] focus:bg-white focus:ring-2 focus:ring-[#700000]/10 transition shadow-2xs pr-10">
                        <button
                            type="button"
                            onclick="togglePasswordVisibility('current_password', this)"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="new_password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        New Password <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            id="new_password"
                            name="new_password"
                            required
                            minlength="6"
                            placeholder="Enter new password (minimum 6 characters)"
                            class="w-full rounded-2xl border border-gray-200 bg-slate-50/70 px-4 py-2.5 text-xs sm:text-sm text-gray-900 outline-none focus:border-[#700000] focus:bg-white focus:ring-2 focus:ring-[#700000]/10 transition shadow-2xs pr-10">
                        <button
                            type="button"
                            onclick="togglePasswordVisibility('new_password', this)"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="new_password_confirmation" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Confirm New Password <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            id="new_password_confirmation"
                            name="new_password_confirmation"
                            required
                            minlength="6"
                            placeholder="Re-enter your new password"
                            class="w-full rounded-2xl border border-gray-200 bg-slate-50/70 px-4 py-2.5 text-xs sm:text-sm text-gray-900 outline-none focus:border-[#700000] focus:bg-white focus:ring-2 focus:ring-[#700000]/10 transition shadow-2xs pr-10">
                        <button
                            type="button"
                            onclick="togglePasswordVisibility('new_password_confirmation', this)"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-end gap-3 border-t border-gray-100">
                    <button
                        type="button"
                        onclick="closeStudentChangePasswordModal()"
                        class="px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition cursor-pointer">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        id="studentChangePasswordSubmitBtn"
                        class="px-5 py-2.5 rounded-xl bg-[#700000] text-amber-300 hover:bg-[#850000] hover:text-amber-200 text-xs font-bold transition shadow-sm cursor-pointer flex items-center gap-2">
                        <span>Save Password</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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
                                <span class="text-[9px] text-gray-400 shrink-0 font-inter font-normal">${timeAgo}</span>
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

        const studentProfileDd = document.getElementById('studentProfileDropdown');
        const studentProfileBtn = document.getElementById('studentProfileMenuBtn');
        if (studentProfileDd && studentProfileBtn && !studentProfileDd.contains(e.target) && !studentProfileBtn.contains(e.target)) {
            studentProfileDd.classList.add('hidden');
        }
    });

    // Student Profile & Password Change Logic
    function toggleStudentProfileDropdown(e) {
        if (e) e.stopPropagation();
        const dd = document.getElementById('studentProfileDropdown');
        if (!dd) return;
        dd.classList.toggle('hidden');
    }

    function openStudentChangePasswordModal(e) {
        if (e) e.stopPropagation();
        const dd = document.getElementById('studentProfileDropdown');
        if (dd) dd.classList.add('hidden');

        const alertBox = document.getElementById('studentChangePasswordAlert');
        if (alertBox) {
            alertBox.innerHTML = '';
            alertBox.classList.add('hidden');
        }
        const form = document.getElementById('studentChangePasswordForm');
        if (form) form.reset();

        const modal = document.getElementById('studentChangePasswordModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                const currentInput = document.getElementById('current_password');
                if (currentInput) currentInput.focus();
            }, 100);
        }
    }

    function closeStudentChangePasswordModal() {
        const modal = document.getElementById('studentChangePasswordModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function togglePasswordVisibility(fieldId, btn) {
        const input = document.getElementById(fieldId);
        if (!input) return;
        if (input.type === 'password') {
            input.type = 'text';
            btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>`;
        } else {
            input.type = 'password';
            btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>`;
        }
    }

    async function submitStudentChangePassword(e) {
        e.preventDefault();
        const btn = document.getElementById('studentChangePasswordSubmitBtn');
        const alertBox = document.getElementById('studentChangePasswordAlert');
        if (alertBox) {
            alertBox.innerHTML = '';
            alertBox.classList.add('hidden');
        }

        const currentPassword = document.getElementById('current_password').value;
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('new_password_confirmation').value;

        if (newPassword.length < 6) {
            alertBox.innerHTML = `<div class="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">New password must be at least 6 characters.</div>`;
            alertBox.classList.remove('hidden');
            return;
        }

        if (newPassword !== confirmPassword) {
            alertBox.innerHTML = `<div class="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">New password and confirmation do not match.</div>`;
            alertBox.classList.remove('hidden');
            return;
        }

        btn.disabled = true;
        const originalBtnHtml = btn.innerHTML;
        btn.innerHTML = `<span>Saving...</span>`;

        try {
            const token = document.getElementById('sacTopHeader')?.dataset.csrf ||
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
            const response = await fetch('{{ route('student.changePassword') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    current_password: currentPassword,
                    new_password: newPassword,
                    new_password_confirmation: confirmPassword
                })
            });

            const data = await response.json();

            if (!response.ok || data.error) {
                const errorMsg = data.message || Object.values(data.errors || {}).flat().join('<br>') || 'Failed to change password.';
                alertBox.innerHTML = `<div class="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">${errorMsg}</div>`;
                alertBox.classList.remove('hidden');
            } else {
                alertBox.innerHTML = `<div class="p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold">${data.message || 'Password successfully updated!'}</div>`;
                alertBox.classList.remove('hidden');
                document.getElementById('studentChangePasswordForm').reset();
                setTimeout(() => {
                    closeStudentChangePasswordModal();
                }, 1500);
            }
        } catch (err) {
            alertBox.innerHTML = `<div class="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">A network error occurred. Please try again.</div>`;
            alertBox.classList.remove('hidden');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalBtnHtml;
        }
    }

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

                if (cAll) cAll.textContent = data.counts.all || 0;
                if (cApp) cApp.textContent = data.counts.approve || 0;
                if (cRev) cRev.textContent = data.counts.revision || 0;
                if (cArc) cArc.textContent = data.counts.archive || 0;
                if (cEd) cEd.textContent = data.counts.edit || 0;
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
        if (!body) return;

        if (!logs || logs.length === 0) {
            body.innerHTML = `
                <div class="py-12 text-center text-xs text-slate-400 font-medium space-y-1">
                    <p class="font-bold text-slate-600">No activity logs recorded yet.</p>
                    <p class="text-slate-400">Actions like approving submissions, requesting revisions, and editing metadata will appear here.</p>
                </div>
            `;
            return;
        }

        body.innerHTML = logs.map(log => {
            const act = log.action ? log.action.toLowerCase() : '';
            let badgeHtml = '';

            if (act === 'approve' || act === 'approved') {
                badgeHtml = `
                    <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-800">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 shrink-0"></span>
                        <span>Approved</span>
                    </div>
                `;
            } else if (act === 'revision_request' || act === 'revision' || act === 'reject') {
                badgeHtml = `
                    <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-rose-800">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-600 shrink-0"></span>
                        <span>Needs Revision</span>
                    </div>
                `;
            } else if (act === 'archive' || act === 'archived') {
                badgeHtml = `
                    <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-800">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-600 shrink-0"></span>
                        <span>Archived</span>
                    </div>
                `;
            } else if (act === 'restore') {
                badgeHtml = `
                    <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-sky-800">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-600 shrink-0"></span>
                        <span>Restored</span>
                    </div>
                `;
            } else if (act === 'edit' || act === 'edited' || act === 'update') {
                badgeHtml = `
                    <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-800">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600 shrink-0"></span>
                        <span>Edited</span>
                    </div>
                `;
            } else if (act === 'upload') {
                badgeHtml = `
                    <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-purple-800">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-600 shrink-0"></span>
                        <span>Uploaded</span>
                    </div>
                `;
            } else {
                badgeHtml = `
                    <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-500 shrink-0"></span>
                        <span>${escapeHtml(log.action || 'Activity')}</span>
                    </div>
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
                <div class="p-4 rounded-2xl bg-white shadow-2xs hover:shadow-xs transition space-y-2">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2 flex-wrap">
                            ${badgeHtml}
                            <span class="text-xs font-bold text-slate-900 leading-snug">
                                ${docLink}
                            </span>
                        </div>
                        <span class="text-[11px] text-slate-400 font-inter font-normal shrink-0 whitespace-nowrap">
                            ${formattedDate}
                        </span>
                    </div>

                    ${log.details ? `
                        <p class="text-xs text-slate-600 leading-relaxed pl-1">
                            ${escapeHtml(log.details)}
                        </p>
                    ` : ''}

                    <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1">
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

    // Close modals on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeActivityLogsModal();
            closeHeaderAddStudentModal();
            closeHeaderStudentDirectoryModal();
            closeAdminEditStudentPasswordModal();
            closeStudentChangePasswordModal();
        }
    });

    // Admin Student Management (Header Modal)
    function openHeaderAddStudentModal(e) {
        if (e) e.stopPropagation();
        const dd = document.getElementById('adminProfileDropdown');
        if (dd) dd.classList.add('hidden');

        const alertBox = document.getElementById('headerAddStudentAlert');
        if (alertBox) {
            alertBox.innerHTML = '';
            alertBox.classList.add('hidden');
        }
        const form = document.getElementById('headerAddStudentForm');
        if (form) form.reset();

        const modal = document.getElementById('adminAddStudentModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                const nameInput = document.getElementById('hdr_student_name');
                if (nameInput) nameInput.focus();
            }, 100);
        }
    }

    function closeHeaderAddStudentModal() {
        const modal = document.getElementById('adminAddStudentModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    async function submitHeaderAddStudent(e) {
        e.preventDefault();
        const btn = document.getElementById('submitHeaderAddStudentBtn');
        const alertBox = document.getElementById('headerAddStudentAlert');
        alertBox.innerHTML = '';
        alertBox.classList.add('hidden');

        const name = document.getElementById('hdr_student_name').value.trim();
        const email = document.getElementById('hdr_student_email').value.trim().toLowerCase();
        const lrn = document.getElementById('hdr_student_lrn').value.trim();

        if (!email.endsWith('@sac.edu.ph')) {
            alertBox.innerHTML = `<div class="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">Institutional email must end with @sac.edu.ph</div>`;
            alertBox.classList.remove('hidden');
            return;
        }

        if (!/^\d{12}$/.test(lrn)) {
            alertBox.innerHTML = `<div class="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">The LRN must be exactly 12 numeric digits.</div>`;
            alertBox.classList.remove('hidden');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = `<span>Saving...</span>`;

        try {
            const response = await fetch('/backend/admin/students', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ name, email, lrn })
            });

            const data = await response.json();

            if (!response.ok || data.error) {
                const errorMsg = data.message || Object.values(data.errors || {}).flat().join('<br>') || 'Failed to register student.';
                alertBox.innerHTML = `<div class="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">${errorMsg}</div>`;
                alertBox.classList.remove('hidden');
            } else {
                alertBox.innerHTML = `<div class="p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold">${data.message}</div>`;
                alertBox.classList.remove('hidden');
                document.getElementById('headerAddStudentForm').reset();
                setTimeout(() => {
                    closeHeaderAddStudentModal();
                    const dirModal = document.getElementById('adminStudentDirectoryModal');
                    if (dirModal && !dirModal.classList.contains('hidden')) {
                        fetchHeaderStudents();
                    }
                }, 1200);
            }
        } catch (err) {
            alertBox.innerHTML = `<div class="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">A network error occurred. Please try again.</div>`;
            alertBox.classList.remove('hidden');
        } finally {
            btn.disabled = false;
            btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg><span>Register Student</span>`;
        }
    }

    let headerStudentSearchTimer = null;
    function debounceHeaderStudentSearch(val) {
        clearTimeout(headerStudentSearchTimer);
        headerStudentSearchTimer = setTimeout(() => {
            fetchHeaderStudents(val);
        }, 300);
    }

    function openHeaderStudentDirectoryModal(e) {
        if (e) e.stopPropagation();
        const dd = document.getElementById('adminProfileDropdown');
        if (dd) dd.classList.add('hidden');

        const modal = document.getElementById('adminStudentDirectoryModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            fetchHeaderStudents();
        }
    }

    function closeHeaderStudentDirectoryModal() {
        const modal = document.getElementById('adminStudentDirectoryModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    async function fetchHeaderStudents(search = '') {
        const tbody = document.getElementById('headerStudentTableBody');
        const countBadge = document.getElementById('headerStudentsCount');
        if (!tbody) return;
        const url = search ? `/backend/admin/students?search=${encodeURIComponent(search)}` : '/backend/admin/students';

        try {
            const res = await fetch(url, { credentials: 'same-origin' });
            const data = await res.json();
            const students = data.students || [];
            if (countBadge) countBadge.textContent = data.total ?? students.length;

            if (students.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="py-8 text-center text-gray-400 text-xs">No registered students found.</td></tr>`;
                return;
            }

            tbody.innerHTML = students.map(s => `
                <tr class="hover:bg-slate-50 transition border-b border-gray-100">
                    <td class="py-2.5 px-3 font-bold text-gray-900">${escapeHtml(s.name)}</td>
                    <td class="py-2.5 px-3 font-mono text-[11px] text-gray-600">${escapeHtml(s.email)}</td>
                    <td class="py-2.5 px-3 font-mono text-[11px] text-[#700000] font-semibold">${escapeHtml(s.lrn || 'N/A')}</td>
                    <td class="py-2.5 px-3 text-right">
                        <button
                            type="button"
                            onclick="openAdminEditStudentPasswordModal(${s.id}, '${escapeHtml(s.name).replace(/'/g, "\\'")}', '${escapeHtml(s.email).replace(/'/g, "\\'")}', '${escapeHtml(s.lrn || '').replace(/'/g, "\\'")}')"
                            class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-[#700000] text-slate-700 hover:text-[#FFD700] text-[11px] font-semibold transition cursor-pointer inline-flex items-center gap-1 shadow-2xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                            <span>Edit</span>
                        </button>
                    </td>
                </tr>
            `).join('');
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="4" class="py-8 text-center text-rose-500 text-xs">Failed to load student directory.</td></tr>`;
        }
    }

    function openAdminEditStudentPasswordModal(id, name, email, lrn) {
        document.getElementById('edit_student_id').value = id;
        document.getElementById('edit_student_lrn_hidden').value = lrn || '';
        document.getElementById('editStudentModalName').textContent = name;
        document.getElementById('editStudentModalEmail').textContent = email;
        document.getElementById('editStudentModalLrn').textContent = lrn ? `LRN: ${lrn}` : '';

        const alertBox = document.getElementById('adminEditStudentPasswordAlert');
        if (alertBox) {
            alertBox.innerHTML = '';
            alertBox.classList.add('hidden');
        }
        const form = document.getElementById('adminEditStudentPasswordForm');
        if (form) form.reset();
        document.getElementById('edit_student_id').value = id;
        document.getElementById('edit_student_lrn_hidden').value = lrn || '';

        const modal = document.getElementById('adminEditStudentPasswordModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                const input = document.getElementById('edit_student_new_password');
                if (input) input.focus();
            }, 100);
        }
    }

    function closeAdminEditStudentPasswordModal() {
        const modal = document.getElementById('adminEditStudentPasswordModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function fillStudentLrnAsPassword() {
        const lrn = document.getElementById('edit_student_lrn_hidden').value;
        if (lrn) {
            const input = document.getElementById('edit_student_new_password');
            if (input) input.value = lrn;
        }
    }

    async function submitAdminEditStudentPassword(e) {
        e.preventDefault();
        const id = document.getElementById('edit_student_id').value;
        const newPassword = document.getElementById('edit_student_new_password').value.trim();
        const alertBox = document.getElementById('adminEditStudentPasswordAlert');
        const btn = document.getElementById('submitAdminEditStudentPasswordBtn');

        if (alertBox) {
            alertBox.innerHTML = '';
            alertBox.classList.add('hidden');
        }

        if (newPassword.length < 6) {
            alertBox.innerHTML = `<div class="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">Password must be at least 6 characters.</div>`;
            alertBox.classList.remove('hidden');
            return;
        }

        btn.disabled = true;
        const originalBtnHtml = btn.innerHTML;
        btn.innerHTML = `<span>Saving...</span>`;

        try {
            const token = document.getElementById('sacTopHeader')?.dataset.csrf ||
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
            const res = await fetch(`/backend/admin/students/${id}/reset-password`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ new_password: newPassword })
            });

            const data = await res.json();
            if (!res.ok || data.error) {
                alertBox.innerHTML = `<div class="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">${data.message || 'Failed to update password.'}</div>`;
                alertBox.classList.remove('hidden');
            } else {
                alertBox.innerHTML = `<div class="p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold">${data.message}</div>`;
                alertBox.classList.remove('hidden');
                setTimeout(() => {
                    closeAdminEditStudentPasswordModal();
                }, 1200);
            }
        } catch (err) {
            alertBox.innerHTML = `<div class="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">A network error occurred. Please try again.</div>`;
            alertBox.classList.remove('hidden');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalBtnHtml;
        }
    }

    async function deleteHeaderStudent(id, name) {
        if (!confirm(`Are you sure you want to remove ${name} from the repository authorization list?`)) return;

        try {
            const res = await fetch(`/backend/admin/students/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.error) {
                alert(data.message || 'Failed to remove student.');
            } else {
                const searchVal = document.getElementById('headerStudentSearchInput') ? document.getElementById('headerStudentSearchInput').value : '';
                fetchHeaderStudents(searchVal);
            }
        } catch (err) {
            alert('A network error occurred.');
        }
    }

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
