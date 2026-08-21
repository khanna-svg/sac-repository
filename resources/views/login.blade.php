<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: #850000;
            background: linear-gradient(327deg, rgba(133, 0, 0, 1) 0%, rgba(255, 255, 255, 1) 50%, rgba(133, 0, 0, 1) 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }
    </style>
</head>
<body class="min-h-screen text-slate-800 flex items-center justify-center p-4 sm:p-6 font-sans relative overflow-x-hidden">

    <!-- Blurred Background Layer -->
    <div class="fixed inset-0 -z-10 bg-cover bg-center bg-no-repeat blur-sm scale-105"
         style="background-image: linear-gradient(rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.45)), url('https://scontent.fmnl4-5.fna.fbcdn.net/v/t39.30808-6/762656124_1358037226471496_1938876567405009188_n.jpg?stp=dst-jpg_tt6&cstp=mx2048x1153&ctp=s2048x1153&_nc_cat=104&_nc_map=urlgen_bucketless&ccb=1-7&_nc_sid=127cfc&_nc_eui2=AeG_TV-KWEtM7prV13mlyHfRljlMoJ_bU2iWOUygn9tTaOwxreFXHcSbYfOfT0s1s-yU-QCA-lXrziuqFZVrD1bz&_nc_ohc=WEziobIciVwQ7kNvwEUepfm&_nc_oc=AdqYq4bzI5DSVMHYhU7QBKgGNvieCahlMNWIScTMSUPeQFtoOW5Wi3jkHBuSL7kJQLs&_nc_zt=23&_nc_ht=scontent.fmnl4-5.fna&_nc_gid=QB391I4pfTEwiudu02W2MQ&_nc_ss=7e2a8&oh=00_AQGsTwGVg_FhUbHIV3UP4cXlLISgcyd-QW6qBTmAjArf_A&oe=6A8B670A');">
    </div>

    <!-- Login Container -->
    <main class="w-full max-w-sm sm:max-w-md rounded-2xl border border-white/20 bg-black/40 backdrop-blur-md p-6 sm:p-8 shadow-2xl text-white"> 
        <div class="mb-6 sm:mb-8 text-center">
            <div class="flex items-center justify-center gap-3">
                <img 
                    src="https://sac.campus-erp.com/Student/images/sac.png"
                    alt="St. Anthony's College Logo"
                    class="h-[120px] w-[120px] object-contain drop-shadow-md"
                >
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#FFD700] drop-shadow-md">St. Anthony's College</h1>
            <p class="mt-1 text-xs sm:text-sm text-gray-200 font-semibold uppercase tracking-wider">Thesis Repository System</p>
        </div>

        <!-- Role Toggle Buttons -->
        <div class="mb-6 flex rounded-xl bg-black/30 p-1 border border-white/10">
            <button id="studentTabBtn" type="button" onclick="switchLoginMode('student')" class="flex-1 py-2 text-xs sm:text-sm font-bold rounded-lg bg-[#700000] text-[#FFD700] shadow-sm transition">
                Student
            </button>
            <button id="adminTabBtn" type="button" onclick="switchLoginMode('admin')" class="flex-1 py-2 text-xs sm:text-sm font-bold rounded-lg text-gray-300 hover:text-white transition">
                Admin
            </button>
        </div>

        @if (session('success'))
            <div class="mb-5 rounded-xl border border-green-300/40 bg-green-900/60 p-3 text-xs sm:text-sm text-green-200 backdrop-blur-sm">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-red-300/40 bg-red-900/60 p-3 text-xs sm:text-sm text-red-200 backdrop-blur-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Student Form -->
        <div id="studentFormSection">
            @if (!isset($pendingEmail) || !$pendingEmail)
                <form method="POST" action="/login/send-code" class="space-y-4 sm:space-y-5">
                    @csrf
                    <div>
                        <label for="student_email" class="mb-2 block text-xs sm:text-sm font-semibold text-gray-200">Student Email</label>
                        <input id="student_email" name="email" type="email" value="{{ old('email') }}" placeholder="student@sac.edu.ph" required class="w-full rounded-xl border border-white/30 bg-white/90 px-3.5 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm text-gray-900 placeholder-gray-500 outline-none focus:border-[#FFD700] focus:ring-2 focus:ring-[#FFD700]">
                    </div>
                    <button class="w-full rounded-xl bg-[#700000] py-2.5 sm:py-3 text-xs sm:text-sm font-bold text-[#FFD700] hover:bg-[#800000] transition shadow-lg">
                        Send Login Code
                    </button>
                </form>
            @else
                <form method="POST" action="/login/verify-code" class="space-y-4 sm:space-y-5">
                    @csrf
                    <div>
                        <label for="code" class="mb-2 block text-xs sm:text-sm font-semibold text-gray-200">Eight-Digit Code sent to {{ $pendingEmail }}</label>
                        <input id="code" name="code" type="text" maxlength="8" required autofocus class="w-full rounded-xl border border-white/30 bg-white/90 px-3 sm:px-4 py-2.5 sm:py-3 text-center text-lg sm:text-xl font-bold tracking-[0.3em] text-[#700000] outline-none focus:border-[#FFD700] focus:ring-2 focus:ring-[#FFD700]">
                    </div>
                    <button class="w-full rounded-xl bg-[#700000] py-2.5 sm:py-3 text-xs sm:text-sm font-bold text-[#FFD700] hover:bg-[#800000] transition shadow-lg">
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
                    <label for="admin_email" class="mb-2 block text-xs sm:text-sm font-semibold text-gray-200">Admin Email</label>
                    <input id="admin_email" name="email" type="email" placeholder="admin@sac.edu.ph" required class="w-full rounded-xl border border-white/30 bg-white/90 px-3.5 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm text-gray-900 placeholder-gray-500 outline-none focus:border-[#FFD700] focus:ring-2 focus:ring-[#FFD700]">
                </div>
                <div>
                    <label for="password" class="mb-2 block text-xs sm:text-sm font-semibold text-gray-200">Admin Password</label>
                    <input id="password" name="password" type="password" placeholder="••••••••" required class="w-full rounded-xl border border-white/30 bg-white/90 px-3.5 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm text-gray-900 placeholder-gray-500 outline-none focus:border-[#FFD700] focus:ring-2 focus:ring-[#FFD700]">
                </div>
                <button class="w-full rounded-xl bg-[#700000] py-2.5 sm:py-3 text-xs sm:text-sm font-bold text-[#FFD700] hover:bg-[#800000] transition shadow-lg">
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
                studentBtn.className = "flex-1 py-2 text-xs sm:text-sm font-bold rounded-lg text-gray-300 hover:text-white transition";
            } else {
                adminForm.classList.add('hidden');
                studentForm.classList.remove('hidden');
                studentBtn.className = "flex-1 py-2 text-xs sm:text-sm font-bold rounded-lg bg-[#700000] text-[#FFD700] shadow-sm transition";
                adminBtn.className = "flex-1 py-2 text-xs sm:text-sm font-bold rounded-lg text-gray-300 hover:text-white transition";
            }
        }
    </script>
</body>
</html>