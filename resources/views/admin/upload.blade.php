<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>SAC Thesis System - Admin Upload</title>

    <script src="https://cdn.tailwindcss.com"></script>


    <style>

        /* ========================================
           SUCCESS POPUP
        ======================================== */

        #successCard {
            animation: popupIn 0.35s ease-out;
        }


        .check-circle {

            stroke-dasharray: 145;

            stroke-dashoffset: 145;

            animation:
                drawCircle
                0.6s
                ease
                forwards;
        }


        .check-mark {

            stroke-dasharray: 40;

            stroke-dashoffset: 40;

            animation:
                drawCheck
                0.4s
                0.5s
                ease
                forwards;
        }


        @keyframes drawCircle {

            to {
                stroke-dashoffset: 0;
            }

        }


        @keyframes drawCheck {

            to {
                stroke-dashoffset: 0;
            }

        }


        @keyframes popupIn {

            from {

                opacity: 0;

                transform:
                    scale(0.8);

            }

            to {

                opacity: 1;

                transform:
                    scale(1);

            }

        }


        .popup-hide {

            animation:
                popupOut
                0.3s
                ease
                forwards;

        }


        @keyframes popupOut {

            from {

                opacity: 1;

                transform:
                    scale(1);

            }

            to {

                opacity: 0;

                transform:
                    scale(0.8);

            }

        }


        /* ========================================
           UPLOAD PROGRESS
        ======================================== */

        #progressContainer {
            display: none;
        }

    </style>

</head>


<body class="min-h-screen bg-slate-50 text-slate-800">


    @include('partials.sidebar')


    <!-- ========================================
         MAIN
    ======================================== -->

    <main
        class="md:ml-64 min-h-screen p-4 sm:p-6 md:p-10 transition-all pt-16 md:pt-10"
    >

        <div class="mx-auto max-w-4xl">


            <!-- ========================================
                 HEADER
            ======================================== -->

            <section class="mb-6 md:mb-10">

                <h1
                    class="text-2xl md:text-3xl font-bold text-[#700000]"
                >
                    Admin Management
                </h1>

                <p
                    class="mt-1 md:mt-2 text-xs md:text-sm text-gray-500"
                >
                    Upload and publish new thesis documents
                    to the system.
                </p>

            </section>


            <!-- ========================================
                 UPLOAD CARD
            ======================================== -->

            <section
                class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-6 shadow-sm"
            >

                <h2
                    class="text-base md:text-lg font-bold text-[#700000]"
                >
                    Upload Thesis Document
                </h2>

                <p
                    class="mt-1 text-xs md:text-sm text-gray-500"
                >
                    Enter metadata and select the thesis PDF file.
                </p>


                <!-- ========================================
                     MESSAGE
                ======================================== -->

                <div
                    id="uploadMessage"
                    class="mt-4 hidden rounded-xl p-3 text-sm"
                ></div>


                <!-- ========================================
                     FORM
                ======================================== -->

                <form
                    id="uploadForm"
                    class="mt-6 space-y-4 md:space-y-5"
                >


                    <!-- TITLE -->

                    <div>

                        <label
                            for="title"
                            class="mb-2 block text-xs md:text-sm font-semibold text-gray-700"
                        >
                            THESIS TITLE
                        </label>

                        <input
                            id="title"
                            name="title"
                            type="text"
                            required
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-xs md:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000]"
                        >

                    </div>


                    <!-- AUTHOR -->

                    <div>

                        <label
                            for="author"
                            class="mb-2 block text-xs md:text-sm font-semibold text-gray-700"
                        >
                            AUTHOR(S)
                        </label>

                        <input
                            id="author"
                            name="author"
                            type="text"
                            required
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-xs md:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000]"
                        >

                    </div>


                    <!-- ABSTRACT -->

                    <div>

                        <label
                            for="abstract"
                            class="mb-2 block text-xs md:text-sm font-semibold text-gray-700"
                        >
                            ABSTRACT
                        </label>

                        <textarea
                            id="abstract"
                            name="abstract"
                            rows="4"
                            required
                            class="w-full resize-none rounded-xl border border-gray-300 bg-white px-4 py-3 text-xs md:text-sm text-gray-800 outline-none focus:border-[#700000] focus:ring-1 focus:ring-[#700000]"
                        ></textarea>

                    </div>


                    <!-- PDF -->

                    <div>

                        <label
                            for="pdf"
                            class="mb-2 block text-xs md:text-sm font-semibold text-gray-700"
                        >
                            UPLOAD PDF FILE
                        </label>

                        <input
                            id="pdf"
                            name="pdf"
                            type="file"
                            accept=".pdf,application/pdf"
                            required
                            class="block w-full cursor-pointer rounded-xl border border-gray-300 bg-white text-xs md:text-sm text-gray-600 file:mr-4 file:border-0 file:bg-[#700000] file:px-4 file:py-3 file:text-[#FFD700] file:font-bold"
                        >

                        <p
                            class="mt-2 text-xs text-gray-400"
                        >
                            Maximum file size: 50 MB
                        </p>

                    </div>


                    <!-- ========================================
                         PROGRESS
                    ======================================== -->

                    <div
                        id="progressContainer"
                        class="rounded-xl border border-gray-200 bg-gray-50 p-4"
                    >

                        <div
                            class="mb-2 flex justify-between text-xs text-gray-500"
                        >

                            <span id="progressText">
                                Uploading PDF...
                            </span>

                            <span id="progressPercent">
                                0%
                            </span>

                        </div>


                        <div
                            class="h-2 w-full overflow-hidden rounded-full bg-gray-200"
                        >

                            <div
                                id="progressBar"
                                class="h-full w-0 rounded-full bg-[#700000] transition-all duration-200"
                            ></div>

                        </div>

                    </div>


                    <!-- ========================================
                         BUTTON
                    ======================================== -->

                    <button
                        id="uploadButton"
                        type="submit"
                        class="w-full rounded-xl bg-[#700000] px-4 py-3 text-xs md:text-sm font-bold text-[#FFD700] shadow-md transition hover:bg-[#800000] disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        Submit & Upload Thesis
                    </button>

                </form>

            </section>

        </div>

    </main>


    <!-- ========================================
         SUCCESS POPUP
    ======================================== -->

    <div
        id="successPopup"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 backdrop-blur-sm"
    >

        <div
            id="successCard"
            class="w-[320px] rounded-2xl bg-white p-8 text-center shadow-2xl"
        >

            <!-- CHECK -->

            <div
                class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-green-100"
            >

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


            <h2
                class="text-xl font-bold text-gray-800"
            >
                Upload Successful!
            </h2>


            <p
                class="mt-2 text-sm text-gray-500"
            >
                Your thesis has been uploaded successfully.
            </p>

        </div>

    </div>


    <!-- ========================================
         JAVASCRIPT
    ======================================== -->

    <script>

        const csrfToken =
            document
                .querySelector(
                    'meta[name="csrf-token"]'
                )
                .getAttribute('content');


        const uploadForm =
            document.getElementById(
                'uploadForm'
            );


        const uploadButton =
            document.getElementById(
                'uploadButton'
            );


        const uploadMessage =
            document.getElementById(
                'uploadMessage'
            );


        const successPopup =
            document.getElementById(
                'successPopup'
            );


        const successCard =
            document.getElementById(
                'successCard'
            );


        const progressContainer =
            document.getElementById(
                'progressContainer'
            );


        const progressBar =
            document.getElementById(
                'progressBar'
            );


        const progressText =
            document.getElementById(
                'progressText'
            );


        const progressPercent =
            document.getElementById(
                'progressPercent'
            );


        // ========================================
        // SHOW ERROR
        // ========================================

        function showError(message)
        {

            uploadMessage.textContent =
                message;

            uploadMessage.className =
                'mt-4 rounded-xl border border-red-300 bg-red-50 p-3 text-sm text-red-700';

        }


        // ========================================
        // SHOW SUCCESS POPUP
        // ========================================

        function showSuccessPopup()
        {

            successPopup.classList.remove(
                'hidden'
            );

            successPopup.classList.add(
                'flex'
            );


            successCard.classList.remove(
                'popup-hide'
            );


            /*
             * Keep popup visible for 3 seconds.
             */

            setTimeout(() => {

                successCard.classList.add(
                    'popup-hide'
                );


                setTimeout(() => {

                    successPopup.classList.add(
                        'hidden'
                    );

                    successPopup.classList.remove(
                        'flex'
                    );

                    successCard.classList.remove(
                        'popup-hide'
                    );

                }, 300);

            }, 3000);

        }


        // ========================================
        // UPDATE PROGRESS
        // ========================================

        function updateProgress(percent)
        {

            const rounded =
                Math.round(percent);


            progressBar.style.width =
                `${rounded}%`;


            progressPercent.textContent =
                `${rounded}%`;

        }


        // ========================================
        // UPLOAD FILE DIRECTLY TO SUPABASE
        // ========================================

        function uploadToSupabase(
            signedUrl,
            file
        )
        {

            return new Promise(
                (
                    resolve,
                    reject
                ) => {

                    const xhr =
                        new XMLHttpRequest();


                    xhr.open(
                        'PUT',
                        signedUrl,
                        true
                    );


                    xhr.setRequestHeader(
                        'Content-Type',
                        'application/pdf'
                    );


                    /*
                     * Track upload progress.
                     */

                    xhr.upload.onprogress =
                        function(event)
                    {

                        if (
                            event.lengthComputable
                        ) {

                            const percent =
                                (
                                    event.loaded /
                                    event.total
                                ) * 100;


                            updateProgress(
                                percent
                            );

                        }

                    };


                    xhr.onload =
                        function()
                    {

                        if (
                            xhr.status >= 200 &&
                            xhr.status < 300
                        ) {

                            updateProgress(
                                100
                            );

                            resolve();

                        } else {

                            reject(
                                new Error(
                                    'Supabase upload failed (' +
                                    xhr.status +
                                    ').'
                                )
                            );

                        }

                    };


                    xhr.onerror =
                        function()
                    {

                        reject(
                            new Error(
                                'Network error while uploading the PDF to Supabase.'
                            )
                        );

                    };


                    xhr.onabort =
                        function()
                    {

                        reject(
                            new Error(
                                'The PDF upload was cancelled.'
                            )
                        );

                    };


                    xhr.send(file);

                }
            );

        }


        // ========================================
        // FORM SUBMIT
        // ========================================

        uploadForm.addEventListener(
            'submit',
            async function(e)
            {

                e.preventDefault();


                const fileInput =
                    document.getElementById(
                        'pdf'
                    );


                const file =
                    fileInput.files[0];


                // ========================================
                // BASIC VALIDATION
                // ========================================

                if (!file) {

                    showError(
                        'Please select a PDF file.'
                    );

                    return;

                }


                if (
                    file.type !==
                    'application/pdf'
                ) {

                    showError(
                        'Only PDF files are allowed.'
                    );

                    return;

                }


                /*
                 * 50 MB maximum.
                 *
                 * 50 * 1024 * 1024
                 */

                const maxSize =
                    50 * 1024 * 1024;


                if (
                    file.size > maxSize
                ) {

                    showError(
                        'The PDF must be 50 MB or smaller.'
                    );

                    return;

                }


                // ========================================
                // CLEAR OLD MESSAGE
                // ========================================

                uploadMessage.className =
                    'hidden';


                progressContainer.style.display =
                    'block';


                updateProgress(0);


                uploadButton.disabled =
                    true;


                uploadButton.textContent =
                    'Preparing upload...';


                try {

                    // ========================================
                    // STEP 1
                    // ASK LARAVEL FOR SIGNED URL
                    // ========================================

                    const signedResponse =
                        await fetch(
                            '/backend/documents/upload-url',
                            {

                                method: 'POST',

                                headers: {

                                    'Accept':
                                        'application/json',

                                    'Content-Type':
                                        'application/json',

                                    'X-CSRF-TOKEN':
                                        csrfToken

                                },

                                body:
                                    JSON.stringify({
                                        filename:
                                            file.name
                                    })

                            }
                        );


                    let signedData;


                    try {

                        signedData =
                            await signedResponse.json();

                    } catch {

                        throw new Error(
                            'The server returned an invalid response while preparing the upload.'
                        );

                    }


                    if (
                        !signedResponse.ok ||
                        signedData.error
                    ) {

                        throw new Error(
                            signedData.message ||
                            'Could not prepare the upload.'
                        );

                    }


                    // ========================================
                    // STEP 2
                    // DIRECT UPLOAD TO SUPABASE
                    // ========================================

                    progressText.textContent =
                        'Uploading PDF to storage...';


                    uploadButton.textContent =
                        'Uploading PDF...';


                    await uploadToSupabase(
                        signedData.signedUrl,
                        file
                    );


                    // ========================================
                    // STEP 3
                    // SEND ONLY METADATA TO LARAVEL
                    // ========================================

                    progressText.textContent =
                        'Processing thesis...';


                    uploadButton.textContent =
                        'Processing thesis...';


                    const metadataResponse =
                        await fetch(
                            '/backend/documents/upload',
                            {

                                method: 'POST',

                                headers: {

                                    'Accept':
                                        'application/json',

                                    'Content-Type':
                                        'application/json',

                                    'X-CSRF-TOKEN':
                                        csrfToken

                                },

                                body:
                                    JSON.stringify({

                                        title:
                                            document
                                                .getElementById(
                                                    'title'
                                                )
                                                .value,

                                        author:
                                            document
                                                .getElementById(
                                                    'author'
                                                )
                                                .value,

                                        abstract:
                                            document
                                                .getElementById(
                                                    'abstract'
                                                )
                                                .value,

                                        file_path:
                                            signedData.path

                                    })

                            }
                        );


                    let data;


                    try {

                        data =
                            await metadataResponse.json();

                    } catch {

                        throw new Error(
                            'The server returned an invalid response while processing the thesis.'
                        );

                    }


                    if (
                        !metadataResponse.ok ||
                        data.error
                    ) {

                        throw new Error(
                            data.message ||
                            'Thesis processing failed.'
                        );

                    }


                    // ========================================
                    // STEP 4
                    // SUCCESS
                    // ========================================

                    uploadForm.reset();


                    progressContainer.style.display =
                        'none';


                    showSuccessPopup();


                } catch (error) {

                    console.error(
                        'Upload error:',
                        error
                    );


                    progressContainer.style.display =
                        'none';


                    showError(
                        error.message ||
                        'Upload failed.'
                    );


                } finally {

                    uploadButton.disabled =
                        false;


                    uploadButton.textContent =
                        'Submit & Upload Thesis';

                }

            }
        );

    </script>

</body>

</html>