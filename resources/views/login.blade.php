<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAC Thesis System - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>

</head>
<body class="min-h-screen bg-gray-950 text-gray-100 flex items-center justify-center p-6">
    
    <main class="w-full max-w-md rounded-2x1 border border-gray-700 bg-gray-900 p-8 shadow-xl"> 
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600 text-xl font-bold">
                S
            </div>
            <h1 class="text-2xl font-bold">SAC Thesis System</h1>
            <p class="mt-2 text-sm text-gray-400"></p>
        </div>

        @if (session('success'))
            <div class="mb-5 rounded-lg border border-green-700 bg-green-950/40 p-3 text-sm text-green-300">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 rounded-lg border-red-700 bg-red-950/40 p-3 text-sm text-red-300">
                {{ $errors->first() }}
            </div>
        @endif
        
        @if (!$pendingEmail)
            <form method="POST" action="/login/send-code" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-2 block text-sm font-medium">SAC email address</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        placeholder="name@sac.edu.ph"
                        required
                        class="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-sm outline-none focus:border-indigo-500"
                    >
                </div>

                <button class="w-full rounded-lg bg-indigo-600 py-3 text-sm font-semibold hover:bg-indigo-500">
                    Send login code
                </button>
            </form>

             @else
            <p class="mb-5 rounded-lg border border-indigo-700 bg-indigo-950/40 p-3 text-sm text-indigo-200">
                A login code was sent to <strong>{{ $pendingEmail }}</strong>.
            </p>

            <form method="POST" action=" {{  route('login.reset') }} " class="mt-4 text-center">
                @csrf
                <button type="submit" class="text-sm text-indigo-300 hover:text-indigo-200">
                    Use another email / request a new code
                </button>
            </form>

            <form method="POST" action="/login/verify-code" class="space-y-5">
                @csrf

                <div>
                    <label for="code" class="mb-2 block text-sm font-medium">Six-digit login code</label>
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
                        class="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-center text-xl tracking-[0.4em] outline-none focus:border-indigo-500"
                    >
                </div>

                <button class="w-full rounded-lg bg-indigo-600 py-3 text-sm font-semibold hover:bg-indigo-500">
                    Verify and sign in
                </button>
            </form>
        @endif
    </main>
</body>
</html>