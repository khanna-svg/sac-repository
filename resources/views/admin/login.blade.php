<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>AIRIS - Administrator Sign In</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="{{ asset('images/airis-logo.webp') }}" type="image/webp">

    <style>
        .floating-badge-group {
            position: relative;
            margin-top: 0.65rem;
        }
        .floating-badge-label {
            position: absolute;
            left: 1rem;
            top: 0.85rem;
            font-size: 0.875rem;
            color: #64748b;
            pointer-events: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 10;
        }
        .floating-badge-input:focus ~ .floating-badge-label,
        .floating-badge-input:not(:placeholder-shown) ~ .floating-badge-label,
        .floating-badge-input:-webkit-autofill ~ .floating-badge-label {
            top: -1.35rem;
            left: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #700000;
        }
    </style>
</head>
<body class="min-h-screen bg-white font-sans flex flex-col md:flex-row overflow-x-hidden">

    <!-- Left Section: Picture (Clean Straight Split) -->
    <div class="relative w-full md:w-1/2 lg:w-7/12 h-64 sm:h-80 md:h-auto md:min-h-screen overflow-hidden bg-slate-900 shrink-0 border-b md:border-b-0 md:border-r border-slate-200">
        <img 
            src="{{ asset('images/admin-bg.webp') }}" 
            alt="St. Anthony's College Administration" 
            class="w-full h-full object-cover object-center filter contrast-[1.05] brightness-[0.95]"
        >
        <!-- Soft Tint Overlay: smooths pixelation and provides depth -->
        <div class="absolute inset-0 bg-gradient-to-t md:bg-gradient-to-r from-black/40 via-slate-900/10 to-transparent"></div>

        <!-- Institutional watermark caption (desktop only) -->
        <div class="absolute bottom-8 left-8 hidden md:block text-white drop-shadow-md z-10">
            <p class="text-sm font-semibold text-white/95">Institutional Research Repository • Administration</p>
        </div>
    </div>

    <!-- Right Section: White Background Login Credentials -->
    <div class="w-full md:w-1/2 lg:w-5/12 min-h-screen flex flex-col justify-between items-center p-6 sm:p-10 lg:p-14 bg-white text-slate-800 relative z-20">
        
        <!-- Top Spacer -->
        <div class="hidden md:block w-full h-2"></div>

        <!-- Main Login Form Container -->
        <main class="w-full max-w-sm sm:max-w-md my-auto"> 
            <div class="mb-6 sm:mb-8 text-center">
                <div class="flex items-center justify-center mb-3">
                    <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-white p-2 shadow-md border-2 border-[#D4AF37]/40 flex items-center justify-center">
                        <img 
                            src="{{ asset('images/airis-logo.webp') }}"
                            alt="AIRIS Logo"
                            class="w-full h-full object-contain"
                        >
                    </div>
                </div>
                <h1 class="text-3xl sm:text-4xl font-black text-[#700000] drop-shadow-xs tracking-wider font-['Outfit',sans-serif]">
                    AIRIS
                </h1>
                <p class="mt-1 text-xs sm:text-sm text-slate-500 font-semibold tracking-wide">AI-Powered Institutional Research Repository</p>
            </div>

            @if (session('success'))
                <div class="mb-5 rounded-2xl border border-green-300 bg-green-50 p-3.5 text-xs sm:text-sm text-green-800 flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 rounded-2xl border border-red-300 bg-red-50 p-3.5 text-xs sm:text-sm text-red-800 flex items-start gap-2">
                    <svg class="w-4 h-4 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <!-- ADMIN FORM (EMAIL & PASSWORD) -->
            <form method="POST" action="{{ route('admin.login') }}" class="space-y-4 sm:space-y-5">
                @csrf
                <div class="floating-badge-group">
                    <input 
                        id="admin_email" 
                        name="email" 
                        type="email" 
                        value="{{ old('email') }}" 
                        placeholder=" "
                        required 
                        autocomplete="email"
                        class="floating-badge-input block w-full rounded-2xl border border-slate-300 bg-slate-50/70 px-4 py-3 text-xs sm:text-sm text-slate-900 outline-none focus:bg-white focus:border-[#700000] focus:ring-2 focus:ring-[#700000]/15 transition-all shadow-2xs">
                    <label 
                        for="admin_email" 
                        class="floating-badge-label">
                        Email
                    </label>
                </div>

                <div class="floating-badge-group">
                    <input 
                        id="admin_password" 
                        name="password" 
                        type="password" 
                        placeholder=" " 
                        required 
                        autocomplete="current-password"
                        class="floating-badge-input block w-full rounded-2xl border border-slate-300 bg-slate-50/70 px-4 pr-11 py-3 text-xs sm:text-sm text-slate-900 outline-none focus:bg-white focus:border-[#700000] focus:ring-2 focus:ring-[#700000]/15 transition-all shadow-2xs">
                    <label 
                        for="admin_password" 
                        class="floating-badge-label">
                        Password
                    </label>
                    <button 
                        type="button" 
                        onclick="toggleAdminPasswordVisibility()" 
                        title="Show/Hide Password"
                        class="absolute right-3 top-2.5 sm:top-3 text-slate-400 hover:text-[#700000] transition p-1 cursor-pointer z-10">
                        <svg id="adminPasswordEyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>
                </div>

                <button 
                    type="submit" 
                    class="w-full rounded-2xl bg-[#700000] hover:bg-[#850000] py-3 text-xs sm:text-sm font-bold text-[#FFD700] transition shadow-md shadow-red-950/20 border border-[#FFD700]/30 flex items-center justify-center gap-2 cursor-pointer mt-2">
                    <span>Sign in</span>
                </button>
            </form>
        </main>

        <!-- Footer -->
        <footer class="w-full text-center text-xs text-slate-400 py-4">
            <p>© 2026 • AIRIS</p>
        </footer>
    </div>

    <script>
        function toggleAdminPasswordVisibility() {
            const passwordInput = document.getElementById('admin_password');
            const eyeIcon = document.getElementById('adminPasswordEyeIcon');
            if (!passwordInput || !eyeIcon) return;

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                `;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                `;
            }
        }
    </script>
</body>
</html>
