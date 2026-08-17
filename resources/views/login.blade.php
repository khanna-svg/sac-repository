<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAC Thesis System - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 flex items-center justify-center p-4 sm:p-6 font-sans">
    <main class="w-full max-w-sm sm:max-w-md rounded-2xl border border-gray-200 bg-white p-6 sm:p-8 shadow-xl"> 
        <div class="mb-6 sm:mb-8 text-center">
            <div class="flex items-center justify-center gap-3">
                <img 
                    src="https://sac.campus-erp.com/Student/images/sac.png"
                    alt="St. Anthony's College Logo"
                    class="h-[120px] w-[120px] object-contain"
                >
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#700000]">St. Anthony's College</h1>
            <p class="mt-1 text-xs sm:text-sm text-gray-500 font-semibold uppercase tracking-wider">Thesis Repository System</p>
        </div>

        <!-- Role Toggle Buttons -->
        <div class="mb-6 flex rounded-xl bg-slate-100 p-1 border border-gray-200">
            <button id="studentTabBtn" type="button" onclick="switchLoginMode('student')" class="flex-1 py-2 text-xs sm:text-sm font-bold rounded-lg bg-[#700000] text-[#FFD700] shadow-sm transition">
                Student
            </button>
            <button id="adminTabBtn" type="button" onclick="switchLoginMode('admin')" class="flex-1 py-2 text-xs sm:text-sm font-bold rounded-lg text-gray-500 hover:text-gray-900 transition">
                Admin
            </button>
        </div>

        @if (session('success'))
            <div class="mb-5 rounded-xl border border-green-300 bg-green-50 p-3 text-xs sm:text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-red-300 bg-red-50 p-3 text-xs sm:text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Student Form -->
        <div id="studentFormSection">
            @if (!isset($pendingEmail) || !$pendingEmail)
                <form method="POST" action="/login/send-code" class="space-y-4 sm:space-y-5">
                    @csrf
                    <div>
                        <label for="student_email" class="mb-2 block text-xs sm:text-sm font-semibold text-gray-700">Student Email</label>
                        <input id="student_email" name="email" type="email" value="{{ old('email') }}" placeholder="student@sac.edu.ph" required class="w-full rounded-xl border border-gray-300 bg-white px-3.5 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000]">
                    </div>
                    <button class="w-full rounded-xl bg-[#700000] py-2.5 sm:py-3 text-xs sm:text-sm font-bold text-[#FFD700] hover:bg-[#800000] transition shadow-md">
                        Send Login Code
                    </button>
                </form>
            @else
                <form method="POST" action="/login/verify-code" class="space-y-4 sm:space-y-5">
                    @csrf
                    <div>
                        <label for="code" class="mb-2 block text-xs sm:text-sm font-semibold text-gray-700">Eight-Digit Code sent to {{ $pendingEmail }}</label>
                        <input id="code" name="code" type="text" maxlength="8" required autofocus class="w-full rounded-xl border border-gray-300 bg-white px-3 sm:px-4 py-2.5 sm:py-3 text-center text-lg sm:text-xl font-bold tracking-[0.3em] text-[#700000] outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000]">
                    </div>
                    <button class="w-full rounded-xl bg-[#700000] py-2.5 sm:py-3 text-xs sm:text-sm font-bold text-[#FFD700] hover:bg-[#800000] transition shadow-md">
                        Verify and Sign In
                    </button>
                </form>
            @endif
        </div>

        <!-- Admin Form -->
        <div id="adminFormSection" class="hidden">
            <form method="POST" action="/admin/login" class="space-y-4 sm:space-y-5">
                @csrf
                <div>
                    <label for="admin_email" class="mb-2 block text-xs sm:text-sm font-semibold text-gray-700">Admin Email</label>
                    <input id="admin_email" name="email" type="email" placeholder="admin@sac.edu.ph" required class="w-full rounded-xl border border-gray-300 bg-white px-3.5 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000]">
                </div>
                <div>
                    <label for="password" class="mb-2 block text-xs sm:text-sm font-semibold text-gray-700">Admin Password</label>
                    <input id="password" name="password" type="password" placeholder="••••••••" required class="w-full rounded-xl border border-gray-300 bg-white px-3.5 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000]">
                </div>
                <button class="w-full rounded-xl bg-[#700000] py-2.5 sm:py-3 text-xs sm:text-sm font-bold text-[#FFD700] hover:bg-[#800000] transition shadow-md">
                    Sign In as Admin
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
                adminBtn.className = "flex-1 py-2 text-xs sm:text-sm font-bold rounded-lg bg-[#700000] text-[#FFD700] shadow-sm transition";
                studentBtn.className = "flex-1 py-2 text-xs sm:text-sm font-bold rounded-lg text-gray-500 hover:text-gray-900 transition";
            } else {
                adminForm.classList.add('hidden');
                studentForm.classList.remove('hidden');
                studentBtn.className = "flex-1 py-2 text-xs sm:text-sm font-bold rounded-lg bg-[#700000] text-[#FFD700] shadow-sm transition";
                adminBtn.className = "flex-1 py-2 text-xs sm:text-sm font-bold rounded-lg text-gray-500 hover:text-gray-900 transition";
            }
        }
    </script>
</body>
</html>