<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SAC Thesis System - Admin Upload</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        #successCard {
            animation: popupIn 0.35s ease-out;
        }

        /* Circle animation */
        .check-circle {
            stroke-dasharray: 145;
            stroke-dashoffset: 145;
            animation: drawCircle 0.6s ease forwards;
        }

        /* Checkmark animation */
        .check-mark {
            stroke-dasharray: 40;
            stroke-dashoffset: 40;
            animation: drawCheck 0.4s 0.5s ease forwards;
        }

        /* Popup entrance */
        @keyframes popupIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Circle drawing */
        @keyframes drawCircle {
            to {
                stroke-dashoffset: 0;
            }
        }

        /* Checkmark drawing */
        @keyframes drawCheck {
            to {
                stroke-dashoffset: 0;
            }
        }

        /* Popup exit */
        .popup-hide {
            animation: popupOut 0.3s ease forwards;
        }

        @keyframes popupOut {
            from {
                opacity: 1;
                transform: scale(1);
            }

            to {
                opacity: 0;
                transform: scale(0.8);
            }
        }
    </style>
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

    <!-- Success Popup -->
    <div id="successPopup" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 backdrop-blur-sm">
        <div id="successCard" class="w-[320px] rounded-2xl bg-white p-8 text-center shadow-2xl">
            
            <!-- Animated Check Circle -->
            <div class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-green-100">
                <svg
                    class="h-14 w-14 text-green-600"
                    viewBox="0 0 52 52"
                    fill="none"
                >
                    <circle
                        class="check-circle"
                        cx="26"
                        cy="26"
                        r="23"
                        stroke="currentColor"
                        stroke-width="4"
                        fill="none"
                    />

                    <path
                        class="check-mark"
                        d="M14 27L22 35L39 17"
                        stroke="currentColor"
                        stroke-width="4"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        fill="none"
                    />
                </svg>
            </div>

            <h2 class="text-xl font-bold text-gray-800">
                Upload Successful!
            </h2>

            <p class="mt-2 text-sm text-gray-500">
                Your thesis has been uploaded successfully.
            </p>

        </div>
    </div>

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