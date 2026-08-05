<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SAC Thesis System - Create Account</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex items-center justify-center p-4 font-sans">

  <div class="max-w-md w-full bg-gray-800 border border-gray-700 rounded-2xl p-8 shadow-xl space-y-6">
    
    <!-- Branding Header -->
    <div class="text-center space-y-2">
      <div class="h-12 w-12 rounded-xl bg-indigo-600 mx-auto flex items-center justify-center font-bold text-white text-xl">S</div>
      <h1 class="text-2xl font-bold text-gray-100">Create Account</h1>
      <p class="text-sm text-gray-400">Register your student or faculty credentials</p>
    </div>

    <!-- Alert Container -->
    <div id="alertBox" class="hidden p-3 rounded-lg text-sm"></div>

    <!-- Registration Form -->
    <form id="registerForm" class="space-y-4">
      @csrf

      <div>
        <label for="name" class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">Full Name</label>
        <input 
          type="text" 
          id="name" 
          name="name"
          placeholder="Juan Dela Cruz" 
          required 
          class="w-full bg-gray-900 border border-gray-700 rounded-xl px-4 py-3 text-sm text-gray-100 focus:outline-none focus:border-indigo-500 transition"
        />
      </div>

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
        <label for="password" class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">Password</label>
        <input 
          type="password" 
          id="password" 
          name="password"
          placeholder="••••••••" 
          required 
          class="w-full bg-gray-900 border border-gray-700 rounded-xl px-4 py-3 text-sm text-gray-100 focus:outline-none focus:border-indigo-500 transition"
        />
      </div>

      <div>
        <label for="password_confirmation" class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">Confirm Password</label>
        <input 
          type="password" 
          id="password_confirmation" 
          name="password_confirmation"
          placeholder="••••••••" 
          required 
          class="w-full bg-gray-900 border border-gray-700 rounded-xl px-4 py-3 text-sm text-gray-100 focus:outline-none focus:border-indigo-500 transition"
        />
      </div>

      <button 
        type="submit" 
        id="submitBtn"
        class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-medium py-3 rounded-xl text-sm transition shadow-lg shadow-indigo-600/30 flex items-center justify-center gap-2">
        <span>Register Account</span>
      </button>
    </form>

    <!-- Switch to Login -->
    <div class="text-center text-sm text-gray-400">
      Already have an account? 
      <a href="/login" class="text-indigo-400 font-medium hover:underline">Sign in</a>
    </div>

  </div>

  <script>
    document.getElementById('registerForm').addEventListener('submit', async function (e) {
      e.preventDefault();

      const alertBox = document.getElementById('alertBox');
      const submitBtn = document.getElementById('submitBtn');
      const name = document.getElementById('name').value;
      const email = document.getElementById('email').value;
      const password = document.getElementById('password').value;
      const password_confirmation = document.getElementById('password_confirmation').value;

      submitBtn.disabled = true;
      submitBtn.innerText = 'Creating account...';
      alertBox.classList.add('hidden');

      try {
        const response = await fetch('/api/register', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify({ name, email, password, password_confirmation })
        });

        const data = await response.json();

        if (response.ok) {
          localStorage.setItem('auth_token', data.token);
          alertBox.className = 'p-3 rounded-lg text-sm bg-emerald-900/50 border border-emerald-700 text-emerald-200';
          alertBox.innerText = 'Account created! Redirecting...';
          alertBox.classList.remove('hidden');

          setTimeout(() => {
            window.location.href = '/documents';
          }, 1000);
        } else {
          alertBox.className = 'p-3 rounded-lg text-sm bg-rose-900/50 border border-rose-700 text-rose-200';
          alertBox.innerText = data.message || 'Registration failed. Check your inputs.';
          alertBox.classList.remove('hidden');
          submitBtn.disabled = false;
          submitBtn.innerText = 'Register Account';
        }
      } catch (error) {
        alertBox.className = 'p-3 rounded-lg text-sm bg-rose-900/50 border border-rose-700 text-rose-200';
        alertBox.innerText = 'Server connection error.';
        alertBox.classList.remove('hidden');
        submitBtn.disabled = false;
        submitBtn.innerText = 'Register Account';
      }
    });
  </script>

</body>
</html>