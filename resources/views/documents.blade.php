<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SAC Thesis System - Document Search</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- PDF.js Core Library -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>

  <style>
    /* Prevent text highlight and user selection */
    .no-select {
      -webkit-touch-callout: none;
      -webkit-user-select: none;
      -khtml-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
    }

    /* Print Masking */
    @media print {
      body {
        display: none !important;
      }
    }
  </style>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex flex-col font-sans no-select">

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
      <h2 class="text-lg font-semibold text-gray-200 mb-2">Upload Thesis Document</h2>
      <p class="text-sm text-gray-400 mb-6">Enter metadata and select your thesis PDF file to publish to the repository.</p>
      
      <form id="uploadForm" class="space-y-4">
        <div>
          <label class="block text-xs font-medium text-gray-300 uppercase tracking-wider mb-1">Thesis Title</label>
          <input type="text" id="titleInput" required placeholder="e.g., A Mobile-Based Medication Adherence System" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-sm text-gray-100 focus:outline-none focus:border-indigo-500">
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-300 uppercase tracking-wider mb-1">Author(s)</label>
          <input type="text" id="authorInput" required placeholder="e.g., Juan Dela Cruz, Maria Santos" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-sm text-gray-100 focus:outline-none focus:border-indigo-500">
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-300 uppercase tracking-wider mb-1">Abstract</label>
          <textarea id="abstractInput" rows="4" required placeholder="Paste thesis abstract here..." class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-sm text-gray-100 focus:outline-none focus:border-indigo-500 resize-none"></textarea>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-300 uppercase tracking-wider mb-1">Upload PDF File</label>
          <input type="file" id="pdfInput" accept=".pdf" required class="block w-full text-sm text-gray-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 cursor-pointer bg-gray-900 border border-gray-700 rounded-lg">
        </div>

        <button type="submit" id="submitBtn" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-medium py-3 rounded-lg text-sm transition mt-2">
          Submit & Upload Thesis
        </button>
      </form>

      <div id="uploadStatus" class="mt-4 text-sm hidden"></div>
    </section>

    <!-- Search Section -->
    <section class="space-y-4">
      <div>
        <h2 class="text-lg font-semibold text-gray-200">Repository Search</h2>
        <p class="text-sm text-gray-400">Search stored theses by title, author, or content keywords.</p>
      </div>

      <div class="flex gap-3">
        <input 
          type="text" 
          id="searchQuery" 
          placeholder="Search topics, authors, or keywords..." 
          class="flex-1 bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-sm text-gray-100 focus:outline-none focus:border-indigo-500"
        />
        <button id="searchBtn" class="bg-indigo-600 hover:bg-indigo-500 text-white font-medium px-6 py-3 rounded-lg text-sm transition">
          Search
        </button>
      </div>

      <!-- Search Results Area -->
      <div id="searchResults" class="space-y-4 pt-2">
        <div class="text-gray-400 text-sm text-center py-4">Loading documents...</div>
      </div>
    </section>

  </main>

  <!-- Success Modal -->
  <div id="successModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl p-8 max-w-sm w-full text-center transform transition-all scale-100">
      
      <!-- Circle Check Icon -->
      <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-50 border-2 border-green-200 mb-6">
        <svg class="h-10 w-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
        </svg>
      </div>

      <h3 class="text-2xl font-bold text-gray-800 mb-3">Success</h3>
      <p class="text-gray-600 text-sm mb-6">Thesis uploaded successfully!</p>

      <button id="closeModalBtn" class="w-24 bg-[#0284c7] hover:bg-[#0369a1] text-white font-medium py-2.5 rounded-lg text-sm transition focus:outline-none shadow-md">
        OK
      </button>
    </div>
  </div>

  <!-- PDF Viewer Modal (Read-Only Canvas Viewer) -->
  <div id="pdfViewerModal" class="fixed inset-0 bg-black/80 backdrop-blur-md flex flex-col hidden z-50">
    <div class="bg-gray-950 border-b border-gray-800 px-6 py-3 flex items-center justify-between">
      <h3 id="pdfViewerTitle" class="text-sm font-semibold text-gray-200 truncate max-w-xl">Thesis Viewer</h3>
      <button onclick="closePdfViewer()" class="bg-gray-800 hover:bg-gray-700 text-gray-300 px-4 py-1.5 rounded-lg text-xs font-medium transition">
        Close Viewer
      </button>
    </div>
    
    <div id="pdfViewerContainer" class="flex-1 overflow-y-auto p-6 flex flex-col items-center space-y-6">
      <!-- Canvases will render dynamically here -->
    </div>
  </div>

  <script>
    // Setup PDF.js Worker
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

    const uploadForm = document.getElementById('uploadForm');
    const uploadStatus = document.getElementById('uploadStatus');
    const searchResults = document.getElementById('searchResults');
    const searchQuery = document.getElementById('searchQuery');
    const searchBtn = document.getElementById('searchBtn');

    const successModal = document.getElementById('successModal');
    const closeModalBtn = document.getElementById('closeModalBtn');

    const pdfViewerModal = document.getElementById('pdfViewerModal');
    const pdfViewerContainer = document.getElementById('pdfViewerContainer');
    const pdfViewerTitle = document.getElementById('pdfViewerTitle');

    // Handle Form Submission
    uploadForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const title = document.getElementById('titleInput').value.trim();
      const author = document.getElementById('authorInput').value.trim();
      const abstract = document.getElementById('abstractInput').value.trim();
      const pdfFile = document.getElementById('pdfInput').files[0];

      if (!pdfFile) {
        showStatus('Please select a PDF file.', 'text-red-400');
        return;
      }

      const formData = new FormData();
      formData.append('title', title);
      formData.append('author', author);
      formData.append('abstract', abstract);
      formData.append('pdf', pdfFile);

      showStatus('Uploading document and vectorizing chunks...', 'text-indigo-400');

      try {
        const response = await fetch('/backend/documents/upload', {
          method: 'POST',
          headers: { 'Accept': 'application/json' },
          body: formData
        });

        const result = await response.json();

        if (response.ok) {
          uploadStatus.classList.add('hidden');
          uploadForm.reset();
          fetchDocuments();
          showSuccessModal();
        } else {
          showStatus(result.message || 'Upload failed. Check input values.', 'text-red-400');
        }
      } catch (error) {
        console.error(error);
        showStatus('An error occurred during submission.', 'text-red-400');
      }
    });

    // Fetch and display documents
    async function fetchDocuments(query = '') {
      try {
        const url = query
          ? `/backend/documents?query=${encodeURIComponent(query)}`
          : '/backend/documents';

        const response = await fetch(url, {
          headers: { Accept: 'application/json' }
        });

        const responseText = await response.text();

        if (!response.ok) {
          throw new Error(`Request failed (${response.status}): ${responseText.slice(0, 250)}`);
        }

        const contentType = response.headers.get('content-type') || '';

        if (!contentType.includes('application/json')) {
          throw new Error(
            `Expected JSON but received ${contentType}: ${responseText.slice(0, 300)}`
          );
        }

        const documents = JSON.parse(responseText);

        if (!Array.isArray(documents) || documents.length === 0) {
          searchResults.innerHTML = `
            <div class="bg-gray-800/60 border border-gray-700 p-6 rounded-lg text-gray-400 text-sm text-center">
              ${query ? 'No matching research papers found.' : 'No documents in repository yet.'}
            </div>`;
          return;
        }

        searchResults.innerHTML = documents.map(doc => `
          <div class="bg-gray-800/60 border border-gray-700 p-5 rounded-xl space-y-3">
            <div class="flex justify-between items-start gap-4">
              <div>
                <h3 class="font-semibold text-lg text-indigo-400">${escapeHtml(doc.title)}</h3>
                <p class="text-xs text-indigo-300/80 mt-1">
                  Author(s): <span class="text-gray-300 font-medium">${escapeHtml(doc.author)}</span>
                </p>
              </div>
              <button onclick="openPdfViewer('/backend/documents/${doc.id}/view', 'Thesis Viewer')"
                 class="text-xs bg-indigo-600 hover:bg-indigo-500 text-white font-medium px-4 py-2 rounded-lg transition shrink-0">
                View Thesis
              </button>
            </div>
            <div>
              <p class="text-xs font-medium uppercase tracking-wider text-gray-400 mb-1">Abstract</p>
              <p class="text-sm text-gray-300 leading-relaxed">${escapeHtml(doc.abstract)}</p>
            </div>
          </div>
        `).join('');

      } catch (error) {
        console.error('Error loading documents:', error);

        searchResults.innerHTML = `
          <div class="bg-red-950/40 border border-red-800 p-6 rounded-lg text-red-300 text-sm text-center">
            Could not load documents: ${escapeHtml(error.message)}
          </div>`;
      }
    }

    // Canvas PDF Rendering Logic
    async function openPdfViewer(fileUrl, title) {
      pdfViewerTitle.textContent = title;
      pdfViewerContainer.innerHTML = '<div class="text-gray-400 text-sm py-12">Rendering document security view...</div>';
      pdfViewerModal.classList.remove('hidden');

      try {
        // Pass url string directly to avoid CORS credential handling errors
        const loadingTask = pdfjsLib.getDocument(fileUrl);
        const pdf = await loadingTask.promise;

        pdfViewerContainer.innerHTML = ''; // Clear loader

        for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
          const page = await pdf.getPage(pageNum);
          const viewport = page.getViewport({ scale: 1.5 });

          const canvas = document.createElement('canvas');
          canvas.className = 'rounded-lg shadow-2xl bg-white max-w-full';
          const context = canvas.getContext('2d');

          canvas.height = viewport.height;
          canvas.width = viewport.width;

          pdfViewerContainer.appendChild(canvas);

          await page.render({
            canvasContext: context,
            viewport: viewport
          }).promise;
        }
      } catch (error) {
        console.error('Detailed PDF rendering error:', error);
        pdfViewerContainer.innerHTML = `
          <div class="text-red-400 text-sm py-12 text-center">
            Failed to render PDF document.<br>
            <span class="text-xs text-gray-400 mt-2 block">${escapeHtml(error.message || 'Unknown error')}</span>
          </div>`;
      }
    }

    function closePdfViewer() {
      pdfViewerModal.classList.add('hidden');
      pdfViewerContainer.innerHTML = '';
    }

    // Security Rules: Block Keyboard Shortcuts (Ctrl+S, Ctrl+P, Ctrl+C, Ctrl+U, F12)
    document.addEventListener('keydown', (e) => {
      if (
        (e.ctrlKey && (e.key === 's' || e.key === 'p' || e.key === 'c' || e.key === 'u' || e.key === 'a')) ||
        e.key === 'F12'
      ) {
        e.preventDefault();
        e.stopPropagation();
        return false;
      }
    });

    // Disable Right Click Menu globally
    document.addEventListener('contextmenu', (e) => e.preventDefault());

    // Modal Control Functions
    function showSuccessModal() {
      successModal.classList.remove('hidden');
    }

    closeModalBtn.addEventListener('click', () => {
      successModal.classList.add('hidden');
    });

    // Search Triggering
    searchBtn.addEventListener('click', () => fetchDocuments(searchQuery.value.trim()));
    searchQuery.addEventListener('keyup', (e) => {
      if (e.key === 'Enter') fetchDocuments(searchQuery.value.trim());
    });

    function showStatus(message, colorClass) {
      uploadStatus.className = `mt-4 text-sm ${colorClass}`;
      uploadStatus.textContent = message;
      uploadStatus.classList.remove('hidden');
    }

    function escapeHtml(text) {
      return text ? text.replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m])) : '';
    }

    // Load documents on startup
    document.addEventListener('DOMContentLoaded', () => fetchDocuments());
  </script>
</body>
</html>