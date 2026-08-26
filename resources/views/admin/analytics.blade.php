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

    @include('partials.sidebar')

    <main class="md:ml-64 min-h-screen p-4 sm:p-6 md:p-10 transition-all pt-16 md:pt-10">
        <div class="mx-auto max-w-6xl space-y-8">

            <!-- Header & Export Action Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-200 pb-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-extrabold text-[#700000] flex items-center gap-2.5">
                        <svg class="w-8 h-8 text-[#700000]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                        <span>Research Analytics Dashboard</span>
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-500">
                        Institutional research output, departmental breakdown, and student bookmark metrics.
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
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Theses</p>
                    <p id="statTotalTheses" class="text-3xl md:text-4xl font-extrabold text-[#700000] mt-1.5">--</p>
                    <p class="text-xs text-gray-500 mt-1">Total published theses across all departments</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Departments</p>
                    <p id="statTotalDepts" class="text-3xl md:text-4xl font-extrabold text-[#700000] mt-1.5">--</p>
                    <p class="text-xs text-gray-500 mt-1">Academic departments participating in research</p>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Department Distribution (Doughnut) -->
                <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">Research Output by Department</h3>
                    <div class="h-64 flex items-center justify-center">
                        <canvas id="deptChart"></canvas>
                    </div>
                </div>

                <!-- Degree Program Breakdown (Bar) -->
                <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">Theses by Academic Program</h3>
                    <div class="h-64 flex items-center justify-center">
                        <canvas id="courseChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Yearly Trend Line Chart -->
            <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-bold text-gray-900 mb-4">Annual Research Publication Growth</h3>
                <div class="h-72">
                    <canvas id="yearlyChart"></canvas>
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

                // 2. Department Doughnut Chart
                const deptLabels = data.departments.map(d => d.department.toUpperCase());
                const deptCounts = data.departments.map(d => d.count);
                new Chart(document.getElementById('deptChart'), {
                    type: 'doughnut',
                    data: {
                        labels: deptLabels.length ? deptLabels : ['Information Technology', 'Nursing', 'Marine Engineering'],
                        datasets: [{
                            data: deptCounts.length ? deptCounts : [1, 0, 0],
                            backgroundColor: ['#700000', '#0284c7', '#059669', '#d97706', '#7c3aed', '#dc2626'],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });

                // 3. Course Bar Chart
                const courseLabels = data.courses.map(c => c.course_code.toUpperCase());
                const courseCounts = data.courses.map(c => c.count);
                new Chart(document.getElementById('courseChart'), {
                    type: 'bar',
                    data: {
                        labels: courseLabels.length ? courseLabels : ['BSIT', 'BSN', 'BSMarE'],
                        datasets: [{
                            label: 'Theses Count',
                            data: courseCounts.length ? courseCounts : [1, 0, 0],
                            backgroundColor: '#700000',
                            borderRadius: 6
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
                });

                // 4. Yearly Line Chart
                const yearLabels = data.yearly.map(y => y.year);
                const yearCounts = data.yearly.map(y => y.count);
                new Chart(document.getElementById('yearlyChart'), {
                    type: 'line',
                    data: {
                        labels: yearLabels.length ? yearLabels : ['2024', '2025', '2026'],
                        datasets: [{
                            label: 'Theses Published',
                            data: yearCounts.length ? yearCounts : [0, 0, 1],
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

            } catch (err) {
                console.error('Analytics load error:', err);
            }
        }

        loadAnalytics();
    </script>
</body>

</html>
