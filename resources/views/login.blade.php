<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SAC Thesis System - Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex items-center justify-center p-4 font-sans">

  <div class="max-w-md w-full bg-gray-800 border border-gray-700 rounded-2xl p-8 shadow-xl space-y-6">
    
    <!-- Branding Header -->
    <div class="text-center space-y-2">
      <div class="h-12 w-12 rounded-xl bg-indigo-600 mx-auto flex items-center justify-center font-bold text-white text-xl">S</div>
      <h1 class="text-2xl font-bold text-gray-100">Welcome Back</h1>
      <p class="text-sm text-gray-400">Sign in to access research papers and AI search</p>
    </div>

    <!-- Alert Container for Errors/Messages -->
    <div id="alertBox" class="hidden p-3 rounded-lg text-sm"></div>

    <!-- Login Form -->
    <form id="loginForm" class="space-y-4">
      <!-- CSRF Token (Laravel Requirement) -->
      @csrf

      <div>
        <label for="email" class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">Institutional Email</label>
        <input 
          type="email" 
          id="email" 
          name="email" 
          placeholder="student@sac.edu.ph" 
          required 
          class="w-full bg-gray-900 border border-gray-700 rounded-xl px-4 py-3 text-sm text-gray-100 focus:outline-none focus:border-indigo-500 transition"
        />
      </div>

      <div>
        <div class="flex justify-between items-center mb-2">
          <label for="password" class="block text-xs font-semibold text-gray-300 uppercase tracking-wider">Password</label>
          <a href="#" class="text-xs text-indigo-400 hover:underline">Forgot password?</a>
        </div>
        <input 
          type="password" 
          id="password" 
          name="password" 
          placeholder="••••••••" 
          required 
          class="w-full bg-gray-900 border border-gray-700 rounded-xl px-4 py-3 text-sm text-gray-100 focus:outline-none focus:border-indigo-500 transition"
        />
      </div>

      <button 
        type="submit" 
        id="submitBtn"
        class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-medium py-3 rounded-xl text-sm transition shadow-lg shadow-indigo-600/30 flex items-center justify-center gap-2">
        <span>Sign In</span>
      </button>
    </form>

    <!-- Switch to Register -->
    <div class="text-center text-sm text-gray-400">
      Don't have an account? 
      <a href="/register" class="text-indigo-400 font-medium hover:underline">Register here</a>
    </div>

  </div>

  <!-- Frontend Logic for Login Request -->
  <script>
    document.getElementById('loginForm').addEventListener('submit', async function (e) {
      e.preventDefault();

      const alertBox = document.getElementById('alertBox');
      const submitBtn = document.getElementById('submitBtn');
      const email = document.getElementById('email').value;
      const password = document.getElementById('password').value;

      // Disable button during submission
      submitBtn.disabled = true;
      submitBtn.innerText = 'Signing in...';
      alertBox.classList.add('hidden');

      try {
        const response = await fetch('/api/login', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify({ email, password })
        });

        const data = await response.json();

        if (response.ok) {
          // Save authentication token to browser storage
          localStorage.setItem('auth_token', data.token);

          alertBox.className = 'p-3 rounded-lg text-sm bg-emerald-900/50 border border-emerald-700 text-emerald-200';
          alertBox.innerText = 'Login successful! Redirecting...';
          alertBox.classList.remove('hidden');

          setTimeout(() => {
            window.location.href = '/documents';
          }, 1000);
        } else {
          alertBox.className = 'p-3 rounded-lg text-sm bg-rose-900/50 border border-rose-700 text-rose-200';
          alertBox.innerText = data.message || 'Invalid email or password.';
          alertBox.classList.remove('hidden');
          submitBtn.disabled = false;
          submitBtn.innerText = 'Sign In';
        }
      } catch (error) {
        alertBox.className = 'p-3 rounded-lg text-sm bg-rose-900/50 border border-rose-700 text-rose-200';
        alertBox.innerText = 'Server connection error. Please try again.';
        alertBox.classList.remove('hidden');
        submitBtn.disabled = false;
        submitBtn.innerText = 'Sign In';
      }
    });
  </script>

</body>
</html>