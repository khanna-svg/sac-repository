<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAC Thesis System - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-950 text-gray-100 flex items-center justify-center p-4 sm:p-6">
    
    <!-- Responsive max width & dynamic padding -->
    <main class="w-full max-w-sm sm:max-w-md rounded-2xl border border-gray-800 bg-gray-900 p-6 sm:p-8 shadow-xl"> 
        <div class="mb-6 sm:mb-8 text-center">
            <div class="mx-auto mb-3 sm:mb-4 flex h-10 w-10 sm:h-12 sm:w-12 items-center justify-center rounded-xl bg-indigo-600 text-lg sm:text-xl font-bold">
                S
            </div>
            <h1 class="text-xl sm:text-2xl font-bold">SAC Thesis System</h1>
            <p class="mt-1 text-xs sm:text-sm text-gray-400">Sign in to access research documents</p>
        </div>

        @if (session('success'))
            <div class="mb-5 rounded-lg border border-green-700 bg-green-950/40 p-3 text-xs sm:text-sm text-green-300">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-700 bg-red-950/40 p-3 text-xs sm:text-sm text-red-300">
                {{ $errors->first() }}
            </div>
        @endif
        
        @if (!isset($pendingEmail) || !$pendingEmail)
            <form method="POST" action="/login/send-code" class="space-y-4 sm:space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-2 block text-xs sm:text-sm font-medium">SAC email address</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        placeholder="name@sac.edu.ph"
                        required
                        class="w-full rounded-lg border border-gray-700 bg-gray-950 px-3.5 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm outline-none focus:border-indigo-500"
                    >
                </div>

                <button class="w-full rounded-lg bg-indigo-600 py-2.5 sm:py-3 text-xs sm:text-sm font-semibold hover:bg-indigo-500 transition">
                    Send login code
                </button>
            </form>

        @else
            <p class="mb-5 rounded-lg border border-indigo-700 bg-indigo-950/40 p-3 text-xs sm:text-sm text-indigo-200">
                A login code was sent to <strong>{{ $pendingEmail }}</strong>.
            </p>

            <form method="POST" action="/login/verify-code" class="space-y-4 sm:space-y-5">
                @csrf

                <div>
                    <label for="code" class="mb-2 block text-xs sm:text-sm font-medium">
                        Eight-digit login code
                    </label>

                    <input
                        id="code"
                        name="code"
                        type="text"
                        inputmode="numeric"
                        maxlength="8"
                        pattern="[0-9]{8}"
                        placeholder="12345678"
                        required
                        autofocus
                        class="w-full rounded-lg border border-gray-700 bg-gray-950 px-3 sm:px-4 py-2.5 sm:py-3 text-center text-lg sm:text-xl tracking-[0.3em] sm:tracking-[0.4em] outline-none focus:border-indigo-500"
                    >
                </div>

                <button class="w-full rounded-lg bg-indigo-600 py-2.5 sm:py-3 text-xs sm:text-sm font-semibold hover:bg-indigo-500 transition">
                    Verify and sign in
                </button>
            </form>

            <form method="POST" action="/login/send-code" class="mt-4 text-center">
                @csrf
                <input type="hidden" name="email" value="{{ $pendingEmail }}">

                <button type="submit" class="text-xs sm:text-sm text-indigo-300 hover:text-indigo-200">
                    Send a new code
                </button>
            </form>

            <form method="POST" action="{{ route('login.reset') }}" class="mt-3 text-center">
                @csrf

                <button type="submit" class="text-xs sm:text-sm text-gray-400 hover:text-gray-200">
                    Use another email
                </button>
            </form>
        @endif
    </main>
</body>
</html>