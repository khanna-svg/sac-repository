<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SAC Thesis System - Admin Upload</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    @include('partials.sidebar')

    <main class="md:ml-64 min-h-screen p-4 sm:p-6 md:p-10 transition-all pt-16 md:pt-10">
        <div class="mx-auto max-w-4xl">
            <section class="mb-6 md:mb-10">
                <h1 class="text-2xl md:text-3xl font-bold text-[#700000]">Admin Management</h1>
                <p class="mt-1 md:mt-2 text-xs md:text-sm text-gray-500">
                    Upload and publish new thesis documents to the system.
                </p>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-6 shadow-sm">
                <h2 class="text-base md:text-lg font-bold text-[#700000]">Upload Thesis Document</h2>
                <p class="mt-1 text-xs md:text-sm text-gray-500">
                    Enter metadata and select the thesis PDF file.
                </p>

                <div id="uploadMessage" class="mt-4 hidden rounded-xl p-3 text-sm"></div>

                <form id="uploadForm" class="mt-6 space-y-4 md:space-y-5">
                    <div>
                        <label for="title" class="mb-2 block text-xs md:text-sm font-semibold text-gray-700">THESIS TITLE</label>
                        <input id="title" name="title" type="text" required class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-xs md:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000]">
                    </div>
                    <div>
                        <label for="author" class="mb-2 block text-xs md:text-sm font-semibold text-gray-700">AUTHOR(S)</label>
                        <input id="author" name="author" type="text" required class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-xs md:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000]">
                    </div>
                    <div>
                        <label for="abstract" class="mb-2 block text-xs md:text-sm font-semibold text-gray-700">ABSTRACT</label>
                        <textarea id="abstract" name="abstract" rows="4" required class="w-full resize-none rounded-xl border border-gray-300 bg-white px-4 py-3 text-xs md:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000]"></textarea>
                    </div>
                    <div>
                        <label for="pdf" class="mb-2 block text-xs md:text-sm font-semibold text-gray-700">UPLOAD PDF FILE</label>
                        <input id="pdf" name="pdf" type="file" accept=".pdf,application/pdf" required class="block w-full cursor-pointer rounded-xl border border-gray-300 bg-white text-xs md:text-sm text-gray-600 file:mr-4 file:border-0 file:bg-[#700000] file:px-4 file:py-3 file:text-[#FFD700] file:font-bold">
                    </div>
                    <button id="uploadButton" type="submit" class="w-full rounded-xl bg-[#700000] px-4 py-3 text-xs md:text-sm font-bold text-[#FFD700] hover:bg-[#800000] transition shadow-md">
                        Submit & Upload Thesis
                    </button>
                </form>
            </section>
        </div>
    </main>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const uploadForm = document.getElementById('uploadForm');
        const uploadButton = document.getElementById('uploadButton');
        const uploadMessage = document.getElementById('uploadMessage');

        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(uploadForm);
            uploadButton.disabled = true;
            uploadButton.textContent = 'Uploading...';

            try {
                const response = await fetch('/backend/documents/upload', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: formData
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Upload failed.');

                uploadMessage.textContent = 'Thesis uploaded successfully!';
                uploadMessage.className = 'mt-4 rounded-xl border border-green-300 bg-green-50 p-3 text-sm text-green-700';
                uploadForm.reset();
            } catch (err) {
                uploadMessage.textContent = err.message;
                uploadMessage.className = 'mt-4 rounded-xl border border-red-300 bg-red-50 p-3 text-sm text-red-700';
            } finally {
                uploadButton.disabled = false;
                uploadButton.textContent = 'Submit & Upload Thesis';
            }
        });
    </script>
</body>
</html>