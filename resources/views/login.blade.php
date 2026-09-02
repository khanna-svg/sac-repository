<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAC Thesis Repository - Sign In</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="https://sac.campus-erp.com/Student/images/sac.png" type="image/png">
</head>
<body class="min-h-screen text-slate-800 flex items-center justify-center p-4 sm:p-6 font-sans relative overflow-x-hidden">

    <!-- Blurred Background Layer -->
    <div class="fixed inset-0 -z-10 bg-cover bg-center bg-no-repeat blur-sm scale-105"
         style="background-image: linear-gradient(rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0.55)), url('/images/campus.jpg');">
    </div>

    <!-- Login Container -->
    <main class="w-full max-w-sm sm:max-w-md rounded-3xl border border-white/20 bg-black/45 backdrop-blur-md p-6 sm:p-8 shadow-2xl text-white"> 
        <div class="mb-6 sm:mb-8 text-center">
            <div class="flex items-center justify-center gap-3 mb-2">
                <img 
                    src="https://sac.campus-erp.com/Student/images/sac.png"
                    alt="St. Anthony's College Logo"
                    class="h-[110px] w-[110px] object-contain drop-shadow-lg"
                >
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-[#FFD700] drop-shadow-md tracking-wide">St. Anthony's College</h1>
            <p class="mt-1 text-xs sm:text-sm text-gray-200 font-bold uppercase tracking-wider">Institutional Research Repository</p>
        </div>

        <!-- Role Toggle Tabs -->
        <div class="mb-6 flex rounded-2xl bg-black/40 p-1.5 border border-white/15">
            <button 
                id="studentTabBtn" 
                type="button" 
                onclick="switchLoginMode('student')" 
                class="flex-1 py-2.5 text-xs sm:text-sm font-bold rounded-xl bg-[#700000] text-[#FFD700] shadow-sm transition cursor-pointer">
                Student
            </button>
            <button 
                id="adminTabBtn" 
                type="button" 
                onclick="switchLoginMode('admin')" 
                class="flex-1 py-2.5 text-xs sm:text-sm font-bold rounded-xl text-gray-300 hover:text-white transition cursor-pointer">
                Admin
            </button>
        </div>

        @if (session('success'))
            <div class="mb-5 rounded-2xl border border-green-400/40 bg-green-950/70 p-3.5 text-xs sm:text-sm text-green-200 backdrop-blur-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 rounded-2xl border border-red-400/40 bg-red-950/70 p-3.5 text-xs sm:text-sm text-red-200 backdrop-blur-sm flex items-start gap-2">
                <svg class="w-4 h-4 text-red-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <!-- STUDENT FORM (EMAIL LOGIN CODE) -->
        <div id="studentFormSection">
            @if (!isset($pendingEmail) || !$pendingEmail)
                <form method="POST" action="/login/send-code" class="space-y-4 sm:space-y-5">
                    @csrf
                    <div>
                        <label for="student_email" class="mb-1.5 block text-xs sm:text-sm font-semibold text-gray-200">Institutional Student Email</label>
                        <input 
                            id="student_email" 
                            name="email" 
                            type="email" 
                            value="{{ old('email') }}" 
                            placeholder="student@sac.edu.ph" 
                            required 
                            class="w-full rounded-2xl border border-white/30 bg-white/90 px-4 py-2.5 sm:py-3 text-xs sm:text-sm text-gray-900 placeholder-gray-500 outline-none focus:border-[#FFD700] focus:ring-2 focus:ring-[#FFD700] transition shadow-inner">
                    </div>

                    <button 
                        type="submit" 
                        class="w-full rounded-2xl bg-[#700000] py-3 text-xs sm:text-sm font-bold text-[#FFD700] hover:bg-[#850000] transition shadow-xl border border-[#FFD700]/30 flex items-center justify-center gap-2 cursor-pointer">
                        <span>Send Login Code</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                        </svg>
                    </button>
                </form>
            @else
                <form method="POST" action="/login/verify-code" class="space-y-4 sm:space-y-5">
                    @csrf
                    <div>
                        <label for="code" class="mb-1.5 block text-xs sm:text-sm font-semibold text-gray-200 text-center">
                            Enter the Login Code sent to<br>
                            <span class="text-[#FFD700] font-bold">{{ $pendingEmail }}</span>
                        </label>
                        <input 
                            id="code" 
                            name="code" 
                            type="text" 
                            maxlength="8" 
                            pattern="[0-9]*" 
                            inputmode="numeric" 
                            required 
                            autofocus 
                            placeholder="••••••••" 
                            class="w-full rounded-2xl border border-white/30 bg-white/90 px-4 py-3 text-center text-xl sm:text-2xl font-black tracking-[0.35em] text-[#700000] outline-none focus:border-[#FFD700] focus:ring-2 focus:ring-[#FFD700] transition shadow-inner">
                    </div>

                    <button 
                        type="submit" 
                        class="w-full rounded-2xl bg-[#700000] py-3 text-xs sm:text-sm font-bold text-[#FFD700] hover:bg-[#850000] transition shadow-xl border border-[#FFD700]/30 flex items-center justify-center gap-2 cursor-pointer">
                        <span>Verify and Sign In</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                </form>

                <form method="POST" action="/login/reset" class="text-center pt-2">
                    @csrf
                    <button type="submit" class="text-xs text-gray-300 hover:text-[#FFD700] transition underline underline-offset-4 cursor-pointer">
                        Use a different email address
                    </button>
                </form>
            @endif
        </div>

        <!-- ADMIN FORM (EMAIL & PASSWORD) -->
        <div id="adminFormSection" class="hidden">
            <form method="POST" action="/admin/login" class="space-y-4 sm:space-y-5">
                @csrf
                <div>
                    <label for="admin_email" class="mb-1.5 block text-xs sm:text-sm font-semibold text-gray-200">Admin Email</label>
                    <input 
                        id="admin_email" 
                        name="email" 
                        type="email" 
                        placeholder="admin@sac.edu.ph" 
                        required 
                        class="w-full rounded-2xl border border-white/30 bg-white/90 px-4 py-2.5 sm:py-3 text-xs sm:text-sm text-gray-900 placeholder-gray-500 outline-none focus:border-[#FFD700] focus:ring-2 focus:ring-[#FFD700] transition shadow-inner">
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-xs sm:text-sm font-semibold text-gray-200">Admin Password</label>
                    <input 
                        id="password" 
                        name="password" 
                        type="password" 
                        placeholder="••••••••" 
                        required 
                        class="w-full rounded-2xl border border-white/30 bg-white/90 px-4 py-2.5 sm:py-3 text-xs sm:text-sm text-gray-900 placeholder-gray-500 outline-none focus:border-[#FFD700] focus:ring-2 focus:ring-[#FFD700] transition shadow-inner">
                </div>

                <button 
                    type="submit" 
                    class="w-full rounded-2xl bg-[#700000] py-3 text-xs sm:text-sm font-bold text-[#FFD700] hover:bg-[#850000] transition shadow-xl border border-[#FFD700]/30 flex items-center justify-center gap-2 cursor-pointer mt-2">
                    <span>Sign In as Admin</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </button>
            </form>
        </div>
    </main>

    <script>
        function switchLoginMode(mode) {
            const studentForm = document.getElementById('studentFormSection');
            const adminForm = document.getElementById('adminFormSection');
            const studentBtn = document.getElementById('studentTabBtn');
            const adminBtn = document.getElementById('adminTabBtn');

            if (mode === 'admin') {
                studentForm.classList.add('hidden');
                adminForm.classList.remove('hidden');
                adminBtn.className = "flex-1 py-2.5 text-xs sm:text-sm font-bold rounded-xl bg-[#700000] text-[#FFD700] shadow-sm transition cursor-pointer";
                studentBtn.className = "flex-1 py-2.5 text-xs sm:text-sm font-bold rounded-xl text-gray-300 hover:text-white transition cursor-pointer";
            } else {
                adminForm.classList.add('hidden');
                studentForm.classList.remove('hidden');
                studentBtn.className = "flex-1 py-2.5 text-xs sm:text-sm font-bold rounded-xl bg-[#700000] text-[#FFD700] shadow-sm transition cursor-pointer";
                adminBtn.className = "flex-1 py-2.5 text-xs sm:text-sm font-bold rounded-xl text-gray-300 hover:text-white transition cursor-pointer";
            }
        }
    </script>
</body>
</html>