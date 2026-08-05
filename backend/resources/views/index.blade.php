<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SAC Thesis System - Document Search</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex flex-col font-sans">

  <!-- Navigation Bar -->
  <header class="border-b border-gray-800 bg-gray-950 px-6 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <div class="h-8 w-8 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-white">S</div>
      <h1 class="text-xl font-bold tracking-tight">SAC Thesis System</h1>
    </div>
    <nav class="flex gap-4 items-center">
      <a href="/documents" class="text-sm font-medium text-indigo-400 border-b-2 border-indigo-500 pb-1">Documents & Search</a>
      <a href="/chat" class="text-sm font-medium text-gray-400 hover:text-gray-200 transition">AI Assistant</a>
      <button id="logoutBtn" class="text-xs bg-gray-800 hover:bg-gray-700 border border-gray-700 px-3 py-1.5 rounded-lg ml-4 text-gray-300">Sign Out</button>
    </nav>
  </header>

  <!-- Main Content Container -->
  <main class="flex-1 max-w-5xl w-full mx-auto p-6 space-y-8">
    
    <!-- Upload Section -->
    <section class="bg-gray-800 border border-gray-700 rounded-xl p-6 shadow-lg">
      <h2 class="text-lg font-semibold text-gray-200 mb-2">Upload Thesis PDF</h2>
      <p class="text-sm text-gray-400 mb-4">Upload research papers to automatically chunk text and store vector embeddings in Supabase.</p>
      
      <form id="uploadForm" class="border-2 border-dashed border-gray-600 hover:border-indigo-500 rounded-lg p-8 text-center transition cursor-pointer bg-gray-900/50">
        @csrf
        <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        <p class="text-sm text-gray-300 font-medium">Click to select or drag and drop a PDF file</p>
        <p class="text-xs text-gray-500 mt-1">PDF files up to 20MB</p>
        <input type="file" id="pdfInput" accept=".pdf" class="hidden" />
      </form>
      <div id="uploadStatus" class="mt-3 text-sm hidden"></div>
    </section>

    <!-- Semantic Search Section -->
    <section class="space-y-4">
      <div>
        <h2 class="text-lg font-semibold text-gray-200">Semantic Search</h2>
        <p class="text-sm text-gray-400">Search documents by meaning rather than exact keyword matches.</p>
      </div>

      <div class="flex gap-3">
        <input 
          type="text" 
          id="searchQuery" 
          placeholder="Search research topics (e.g., 'machine learning in agriculture')..." 
          class="flex-1 bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-sm text-gray-100 focus:outline-none focus:border-indigo-500"
        />
        <button id="searchBtn" class="bg-indigo-600 hover:bg-indigo-500 text-white font-medium px-6 py-3 rounded-lg text-sm transition">
          Search
        </button>
      </div>

      <!-- Search Results Area -->
      <div id="searchResults" class="space-y-3 pt-2">
        <div class="bg-gray-800/60 border border-gray-700 p-4 rounded-lg">
          <div class="flex justify-between items-start mb-2">
            <h3 class="font-semibold text-indigo-400">Sample Paper: Analysis of Crop Yield Predictions Using Neural Networks.pdf</h3>
            <span class="text-xs bg-indigo-950 text-indigo-300 border border-indigo-800 px-2 py-0.5 rounded">Match: 89%</span>
          </div>
          <p class="text-sm text-gray-300">"...the proposed deep learning architecture demonstrated high precision in detecting early-stage crop diseases using satellite images..."</p>
        </div>
      </div>
    </section>

  </main>

  <script>
    // File Input Click Handling
    const uploadForm = document.getElementById('uploadForm');
    const pdfInput = document.getElementById('pdfInput');

    uploadForm.addEventListener('click', () => pdfInput.click());

    // Basic Logout logic
    document.getElementById('logoutBtn').addEventListener('click', () => {
      localStorage.removeItem('auth_token');
      window.location.href = '/login';
    });
  </script>
</body>
</html>