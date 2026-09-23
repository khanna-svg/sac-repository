<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Research Analytics Dashboard - SAC Thesis System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="https://sac.campus-erp.com/Student/images/sac.png" type="image/png">
</head>

<body class="min-h-screen bg-slate-50 text-slate-800 font-sans">

    {{-- SAC PORTAL TOP HEADER --}}
    @include('partials.header', ['title' => 'DASHBOARD'])

    @include('partials.sidebar')

    <main class="md:ml-64 min-h-screen p-4 sm:p-6 md:p-10 transition-all pt-20 md:pt-28">
        <div class="mx-auto max-w-6xl space-y-8">

            <!-- Sub-Header & Export Action Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-200 pb-4">
                <div>
                    <p class="text-xs md:text-sm text-gray-500 font-medium">
                        Institutional research output, academic program breakdown, and student metrics.
                    </p>
                </div>

                <!-- Export Actions -->
                <div class="flex items-center gap-2.5 shrink-0">
                    <a
                        href="/admin/analytics/export-csv"
                        class="inline-flex items-center gap-2 rounded-xl bg-[#700000] px-4 py-2 text-xs font-bold text-[#FFD700] hover:bg-[#850000] shadow-md transition">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        <span>Export CSV Report</span>
                    </a>

                    <button
                        type="button"
                        onclick="window.print()"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-xs font-bold text-gray-700 hover:bg-slate-50 transition shadow-2xs">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.205-.37-2.454-.37-3.729 0-1.275.13-2.524.37-3.729m10.56 0c.24 1.205.37 2.454.37 3.729 0 1.275-.13 2.524-.37 3.729m-5.28-7.458c-.808 2.278-1.28 4.792-1.28 7.458 0 2.666.472 5.18 1.28 7.458m0-14.916c.808 2.278 1.28 4.792 1.28 7.458 0 2.666-.472 5.18-1.28 7.458" />
                        </svg>
                        <span>Print</span>
                    </button>
                </div>
            </div>

            <!-- Key Institutional Metrics -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Theses</p>
                <p id="statTotalTheses" class="text-3xl md:text-4xl font-extrabold text-[#700000] mt-1.5">--</p>
                <p class="text-xs text-gray-500 mt-1">Total published theses in repository</p>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Degree Program Breakdown (Bar) -->
                <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">Theses by Academic Program</h3>
                    <div id="courseChartContainer" class="h-64 flex items-center justify-center">
                        <canvas id="courseChart"></canvas>
                    </div>
                </div>

                <!-- Yearly Trend Line Chart -->
                <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">Annual Research Publication Growth</h3>
                    <div id="yearlyChartContainer" class="h-64 flex items-center justify-center">
                        <canvas id="yearlyChart"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
        async function loadAnalytics() {
            try {
                const res = await fetch('/backend/admin/analytics-data');
                if (!res.ok) throw new Error('Failed to load analytics');
                const data = await res.json();

                // 1. Populate Metric Cards
                if (document.getElementById('statTotalTheses')) {
                    document.getElementById('statTotalTheses').textContent = data.metrics.total_theses;
                }
                if (document.getElementById('statTotalPages')) {
                    document.getElementById('statTotalPages').textContent = data.metrics.total_pages;
                }
                if (document.getElementById('statTotalDepts')) {
                    document.getElementById('statTotalDepts').textContent = data.metrics.total_departments;
                }
                if (document.getElementById('statTotalBookmarks')) {
                    document.getElementById('statTotalBookmarks').textContent = data.metrics.total_bookmarks;
                }

                const courseColorMap = {
                    'BSA': '#ca8a04',
                    'BSAIS': '#d97706',
                    'BA': '#eab308',
                    'BSHM': '#f59e0b',
                    'BSCRIM': '#dc2626',
                    'BSC': '#dc2626',
                    'BSED': '#9333ea',
                    'BEED': '#a855f7',
                    'BSCE': '#0891b2',
                    'BSCPE': '#0284c7',
                    'BSIT': '#2563eb',
                    'AB_PHILO': '#4f46e5',
                    'BSN': '#059669',
                    'BSMARE': '#0d9488'
                };

                const defaultPalette = ['#700000', '#0284c7', '#059669', '#d97706', '#7c3aed', '#dc2626', '#ca8a04'];

                // 3. Course Bar Chart (Matching Department Colors)
                const courseLabels = data.courses.map(c => c.course_code.toUpperCase());
                const courseCounts = data.courses.map(c => c.count);
                const courseColors = courseLabels.map((lbl, idx) => courseColorMap[lbl] || defaultPalette[idx % defaultPalette.length]);

                const courseContainer = document.getElementById('courseChartContainer');
                if (!courseLabels.length || data.metrics.total_theses === 0) {
                    courseContainer.innerHTML = `
                        <div class="flex flex-col items-center justify-center text-center p-6 text-gray-400">
                            <svg class="w-9 h-9 mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                            <p class="text-xs font-semibold text-gray-500">No published theses yet</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Program breakdown will display once theses are approved</p>
                        </div>
                    `;
                } else {
                    new Chart(document.getElementById('courseChart'), {
                        type: 'bar',
                        data: {
                            labels: courseLabels,
                            datasets: [{
                                label: 'Theses Count',
                                data: courseCounts,
                                backgroundColor: courseColors,
                                borderRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { stepSize: 1 }
                                }
                            }
                        }
                    });
                }

                // 4. Yearly Line Chart
                const yearLabels = data.yearly.map(y => y.year);
                const yearCounts = data.yearly.map(y => y.count);
                const yearlyContainer = document.getElementById('yearlyChartContainer');

                if (!yearLabels.length || data.metrics.total_theses === 0) {
                    yearlyContainer.innerHTML = `
                        <div class="flex flex-col items-center justify-center text-center p-6 text-gray-400">
                            <svg class="w-9 h-9 mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                            </svg>
                            <p class="text-xs font-semibold text-gray-500">No annual publication data yet</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Annual trends will plot as theses are published</p>
                        </div>
                    `;
                } else {
                    new Chart(document.getElementById('yearlyChart'), {
                        type: 'line',
                        data: {
                            labels: yearLabels,
                            datasets: [{
                                label: 'Theses Published',
                                data: yearCounts,
                                borderColor: '#700000',
                                backgroundColor: 'rgba(112, 0, 0, 0.08)',
                                fill: true,
                                tension: 0.3,
                                pointRadius: 5,
                                pointBackgroundColor: '#FFD700',
                                pointBorderColor: '#700000',
                                pointBorderWidth: 2
                            }]
                        },
                        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
                    });
                }

            } catch (err) {
                console.error('Analytics load error:', err);
            }
        }

        loadAnalytics();
    </script>
</body>

</html>
