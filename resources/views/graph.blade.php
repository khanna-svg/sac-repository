<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knowledge Graph - St. Anthony's College</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Vis.js Network CDN -->
    <script type="text/javascript" src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        #networkGraph {
            width: 100%;
            height: calc(100vh - 170px);
            background: radial-gradient(circle, #ffffff 0%, #f8fafc 100%);
        }
    </style>
</head>

<body class="min-h-screen bg-slate-50 text-slate-800 font-sans overflow-hidden">

    @include('partials.sidebar')

    <main id="mainContent" class="md:ml-64 min-h-screen flex flex-col pt-14 md:pt-0 transition-all duration-300">

        <!-- Top Header & Control Toolbar -->
        <div class="border-b border-gray-200 bg-white px-4 md:px-8 py-3.5 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
                <h1 class="text-lg md:text-xl font-bold text-[#700000] flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#700000]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                    </svg>
                    <span>Interactive Knowledge Graph</span>
                </h1>
                <p class="text-[11px] md:text-xs text-gray-500">
                    Visually explore relationships between research topics, authors, and departments.
                </p>
            </div>

            <!-- Toolbar Controls -->
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Search Filter in Graph -->
                <div class="relative">
                    <input
                        type="text"
                        id="graphSearchInput"
                        placeholder="Highlight node or topic..."
                        class="rounded-xl border border-gray-300 bg-slate-50 px-3 py-1.5 pl-8 text-xs text-gray-800 focus:border-[#700000] focus:outline-none focus:ring-1 focus:ring-[#700000] w-44 md:w-56 transition">
                    <svg class="w-3.5 h-3.5 absolute left-2.5 top-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Reset View Button -->
                <button
                    onclick="resetGraphView()"
                    title="Center & Fit View"
                    class="rounded-xl border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-slate-50 transition shadow-sm flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0 0h4.5m-4.5 0L9 3.75M20.25 20.25v-4.5m0 0h-4.5m4.5 0L15 20.25M3.75 20.25h4.5m-4.5 0v-4.5m0 4.5L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9" />
                    </svg>
                    <span>Reset View</span>
                </button>

                <!-- Physics Toggle Button -->
                <button
                    id="physicsToggleBtn"
                    onclick="togglePhysics()"
                    title="Toggle Node Physics"
                    class="rounded-xl border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-slate-50 transition shadow-sm flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-[#700000]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                    <span id="physicsStatusText">Freeze</span>
                </button>
            </div>
        </div>

        <!-- Legend Banner -->
        <div class="border-b border-gray-200 bg-slate-100/70 px-4 md:px-8 py-2 flex items-center gap-3 overflow-x-auto text-[11px] font-medium text-gray-600">
            <span class="font-bold text-gray-700 uppercase tracking-wider text-[10px]">Legend:</span>
            <span class="inline-flex items-center gap-1.5 bg-white px-2.5 py-1 rounded-lg border border-gray-200 shadow-2xs">
                <span class="w-2.5 h-2.5 rounded bg-[#700000]"></span> Thesis Papers
            </span>
            <span class="inline-flex items-center gap-1.5 bg-white px-2.5 py-1 rounded-lg border border-gray-200 shadow-2xs">
                <span class="w-2.5 h-2.5 rounded bg-[#1e3a8a]"></span> Departments
            </span>
            <span class="inline-flex items-center gap-1.5 bg-white px-2.5 py-1 rounded-lg border border-gray-200 shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-[#047857]"></span> Authors / Researchers
            </span>
            <span class="inline-flex items-center gap-1.5 bg-white px-2.5 py-1 rounded-lg border border-gray-200 shadow-2xs">
                <span class="w-2.5 h-2.5 transform rotate-45 bg-[#b45309]"></span> Degree Programs
            </span>
        </div>

        <!-- Main Graph Canvas Container -->
        <div class="relative flex-1 bg-white">
            <div id="networkGraph"></div>

            <!-- Loading Spinner Indicator -->
            <div id="graphLoader" class="absolute inset-0 bg-white/80 backdrop-blur-xs flex flex-col items-center justify-center gap-3 z-10 transition-opacity">
                <svg class="w-8 h-8 animate-spin text-[#700000]" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-xs font-bold text-[#700000] tracking-wide">Building Knowledge Graph...</p>
            </div>

            <!-- Empty State -->
            <div id="graphEmptyState" class="hidden absolute inset-0 flex flex-col items-center justify-center p-6 text-center z-10">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-gray-800">No Graph Data Available</h3>
                <p class="text-xs text-gray-500 max-w-sm mt-1">Upload approved thesis documents to visualize the research repository network.</p>
            </div>

            <!-- Slide-Out Thesis Details Drawer -->
            <div
                id="detailsDrawer"
                class="absolute top-0 right-0 bottom-0 w-80 md:w-96 bg-white border-l border-gray-200 shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out z-20 flex flex-col">
                
                <!-- Drawer Header -->
                <div class="border-b border-gray-100 p-4 bg-slate-50 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span id="drawerBadge" class="text-xs font-bold text-[#700000] uppercase tracking-wider">
                            Thesis Details
                        </span>
                    </div>
                    <button onclick="closeDetailsDrawer()" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-200 hover:text-gray-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Drawer Body (Scrollable) -->
                <div class="p-5 flex-1 overflow-y-auto space-y-4">
                    <div>
                        <h2 id="drawerTitle" class="text-sm md:text-base font-bold text-gray-900 leading-snug"></h2>
                        <p id="drawerAuthor" class="text-xs text-gray-600 mt-1 font-medium"></p>
                    </div>

                    <!-- Meta Tags -->
                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-gray-100 text-xs">
                        <div class="bg-slate-50 p-2.5 rounded-xl border border-gray-200">
                            <p class="text-[10px] font-bold text-gray-400 uppercase">Department</p>
                            <p id="drawerDept" class="font-semibold text-gray-800 mt-0.5 truncate"></p>
                        </div>
                        <div class="bg-slate-50 p-2.5 rounded-xl border border-gray-200">
                            <p class="text-[10px] font-bold text-gray-400 uppercase">Program</p>
                            <p id="drawerProgram" class="font-semibold text-gray-800 mt-0.5 truncate"></p>
                        </div>
                    </div>

                    <!-- Abstract -->
                    <div class="pt-2 border-t border-gray-100">
                        <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Abstract</h4>
                        <div class="max-h-48 overflow-y-auto rounded-xl bg-slate-50 p-3 text-xs text-gray-600 leading-relaxed border border-gray-200" id="drawerAbstract"></div>
                    </div>
                </div>

                <!-- Drawer Action Footer -->
                <div class="border-t border-gray-200 p-4 bg-slate-50">
                    <a
                        id="drawerReadBtn"
                        href="#"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#700000] px-4 py-2.5 text-xs font-bold text-[#FFD700] hover:bg-[#800000] shadow-sm transition">
                        <span>Read Full Thesis</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>

            </div>

        </div>
    </main>

    <script>
        let network = null;
        let graphData = { nodes: new vis.DataSet([]), edges: new vis.DataSet([]) };
        let physicsEnabled = true;

        async function initKnowledgeGraph() {
            const loader = document.getElementById('graphLoader');
            const emptyState = document.getElementById('graphEmptyState');

            try {
                const response = await fetch('/backend/graph/data');
                if (!response.ok) throw new Error('Failed to load graph data');
                const raw = await response.json();

                if (!raw.nodes || raw.nodes.length === 0) {
                    loader.classList.add('hidden');
                    emptyState.classList.remove('hidden');
                    return;
                }

                const container = document.getElementById('networkGraph');
                graphData.nodes = new vis.DataSet(raw.nodes);
                graphData.edges = new vis.DataSet(raw.edges);

                const options = {
                    nodes: {
                        shape: 'dot',
                        font: { face: 'sans-serif', size: 12 },
                        borderWidth: 2,
                        shadow: true
                    },
                    edges: {
                        width: 1.5,
                        font: { size: 9, align: 'middle', color: '#94a3b8' },
                        color: { color: '#cbd5e1', highlight: '#700000' },
                        arrows: { to: { enabled: true, scaleFactor: 0.5 } }
                    },
                    physics: {
                        solver: 'forceAtlas2Based',
                        forceAtlas2Based: {
                            gravitationalConstant: -50,
                            centralGravity: 0.01,
                            springLength: 100,
                            springConstant: 0.08
                        },
                        stabilization: { iterations: 150 }
                    },
                    interaction: {
                        hover: true,
                        tooltipDelay: 200,
                        zoomView: true,
                        dragView: true
                    }
                };

                network = new vis.Network(container, graphData, options);

                // Click event on nodes
                network.on('click', function(params) {
                    if (params.nodes.length > 0) {
                        const nodeId = params.nodes[0];
                        const node = graphData.nodes.get(nodeId);
                        if (node && node.meta && node.meta.type === 'thesis') {
                            openDetailsDrawer(node.meta);
                        } else {
                            closeDetailsDrawer();
                        }
                    } else {
                        closeDetailsDrawer();
                    }
                });

                // Once stabilized, hide loader
                network.once('stabilizationIterationsDone', function() {
                    loader.classList.add('hidden');
                });

                // Fallback hide loader in 2 seconds
                setTimeout(() => {
                    loader.classList.add('hidden');
                }, 2000);

            } catch (err) {
                console.error(err);
                loader.classList.add('hidden');
                emptyState.classList.remove('hidden');
            }
        }

        function openDetailsDrawer(meta) {
            document.getElementById('drawerTitle').textContent = meta.full_title || 'Untitled Thesis';
            document.getElementById('drawerAuthor').textContent = meta.author ? 'By ' + meta.author : '';
            document.getElementById('drawerDept').textContent = meta.department || 'N/A';
            document.getElementById('drawerProgram').textContent = meta.course_code || 'N/A';
            document.getElementById('drawerAbstract').textContent = meta.abstract || 'No abstract available.';
            document.getElementById('drawerReadBtn').href = meta.view_url || '#';
            document.getElementById('drawerPdfBtn').href = meta.pdf_url || '#';

            const drawer = document.getElementById('detailsDrawer');
            drawer.classList.remove('translate-x-full');
        }

        function closeDetailsDrawer() {
            const drawer = document.getElementById('detailsDrawer');
            if (drawer) {
                drawer.classList.add('translate-x-full');
            }
        }

        function resetGraphView() {
            if (network) {
                network.fit({ animation: { duration: 600, easingFunction: 'easeInOutQuad' } });
            }
        }

        function togglePhysics() {
            if (!network) return;
            physicsEnabled = !physicsEnabled;
            network.setOptions({ physics: { enabled: physicsEnabled } });
            const btnText = document.getElementById('physicsStatusText');
            btnText.textContent = physicsEnabled ? 'Freeze' : 'Unfreeze';
        }

        // Live Search / Node Highlight
        document.getElementById('graphSearchInput').addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase().trim();
            if (!network || !graphData.nodes) return;

            if (query === '') {
                graphData.nodes.forEach(n => {
                    graphData.nodes.update({ id: n.id, hidden: false, opacity: 1 });
                });
                return;
            }

            const matchingNodeIds = [];
            graphData.nodes.forEach(n => {
                const label = (n.label || '').toLowerCase();
                const full = (n.meta?.full_title || '').toLowerCase();
                const author = (n.meta?.author || '').toLowerCase();
                if (label.includes(query) || full.includes(query) || author.includes(query)) {
                    matchingNodeIds.push(n.id);
                }
            });

            if (matchingNodeIds.length > 0) {
                network.selectNodes(matchingNodeIds);
                network.focus(matchingNodeIds[0], { scale: 1.2, animation: true });
            }
        });

        document.addEventListener('DOMContentLoaded', initKnowledgeGraph);
    </script>
</body>

</html>
