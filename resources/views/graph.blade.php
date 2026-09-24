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

    {{-- SAC PORTAL TOP HEADER --}}
    @include('partials.header', ['title' => 'KNOWLEDGE GRAPH'])

    @include('partials.sidebar')

    <main id="mainContent" class="md:ml-64 min-h-screen flex flex-col pt-16 md:pt-20 transition-all duration-300">

        <!-- Top Control Toolbar & Subtitle -->
        <div class="border-b border-gray-200 bg-white px-4 md:px-8 py-3 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
                <p class="text-xs text-gray-500 font-medium">
                    Visually explore connections between research concepts, methodologies, and tech stacks across repository theses.
                </p>
            </div>

            <!-- Toolbar Controls -->
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Search Filter in Graph -->
                <div class="relative">
                    <input
                        type="text"
                        id="graphSearchInput"
                        placeholder="Search concept, tech, or thesis..."
                        class="rounded-xl border border-gray-300 bg-slate-50 px-3 py-1.5 pl-8 text-xs text-gray-800 focus:border-[#700000] focus:outline-none focus:ring-1 focus:ring-[#700000] w-48 md:w-60 transition">
                    <svg class="w-3.5 h-3.5 absolute left-2.5 top-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Reset View Button -->
                <button
                    onclick="resetGraphView()"
                    title="Center & Fit View"
                    class="rounded-xl border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-slate-50 transition shadow-sm flex items-center gap-1.5 cursor-pointer">
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
                    class="rounded-xl border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-slate-50 transition shadow-sm flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-[#700000]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                    <span id="physicsStatusText">Freeze</span>
                </button>
            </div>
        </div>

        <!-- Legend Banner -->
        <div class="border-b border-gray-200 bg-slate-100/70 px-4 md:px-8 py-2 flex items-center gap-2.5 overflow-x-auto text-[11px] font-medium text-gray-600">
            <span class="font-bold text-gray-700 uppercase tracking-wider text-[10px] shrink-0">Legend:</span>
            <span class="inline-flex items-center gap-1.5 bg-white px-2.5 py-1 rounded-lg border border-gray-200 shadow-2xs shrink-0">
                <span class="w-2.5 h-2.5 rounded bg-[#700000]"></span> Thesis Papers
            </span>
            <span class="inline-flex items-center gap-1.5 bg-white px-2.5 py-1 rounded-lg border border-gray-200 shadow-2xs shrink-0">
                <span class="w-2.5 h-2.5 rounded-full bg-[#7c3aed]"></span> Concepts & Topics
            </span>
            <span class="inline-flex items-center gap-1.5 bg-white px-2.5 py-1 rounded-lg border border-gray-200 shadow-2xs shrink-0">
                <span class="w-2.5 h-2.5 rounded-sm bg-[#0891b2]"></span> Methodology & Design
            </span>
            <span class="inline-flex items-center gap-1.5 bg-white px-2.5 py-1 rounded-lg border border-gray-200 shadow-2xs shrink-0">
                <span class="w-2.5 h-2.5 rounded bg-[#059669]"></span> Tech Stack & Tools
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

            <!-- Slide-Out Details Drawer -->
            <div
                id="detailsDrawer"
                class="absolute top-0 right-0 bottom-0 w-80 md:w-96 bg-white border-l border-gray-200 shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out z-20 flex flex-col">
                
                <!-- Drawer Header -->
                <div class="border-b border-gray-100 p-4 bg-slate-50 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span id="drawerBadge" class="text-xs font-bold text-[#700000] uppercase tracking-wider px-2 py-0.5 rounded bg-amber-50 border border-amber-200">
                            Thesis Details
                        </span>
                    </div>
                    <button onclick="closeDetailsDrawer()" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-200 hover:text-gray-700 transition cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Drawer Body (Scrollable) -->
                <div class="p-5 flex-1 overflow-y-auto space-y-4">
                    <div>
                        <h2 id="drawerTitle" class="text-sm md:text-base font-bold text-gray-900 leading-snug"></h2>
                        <p id="drawerSubtitle" class="text-xs text-gray-600 mt-1 font-medium"></p>
                    </div>

                    <!-- THESIS-ONLY METADATA SECTIONS -->
                    <div id="drawerThesisSections" class="space-y-3 pt-2 border-t border-gray-100 text-xs">
                        <!-- Concepts -->
                        <div class="bg-purple-50/70 p-3 rounded-xl border border-purple-100">
                            <p class="text-[10px] font-bold text-purple-700 uppercase tracking-wider">Research Concepts</p>
                            <div id="drawerConcepts" class="flex flex-wrap gap-1.5 mt-1.5"></div>
                        </div>

                        <!-- Methodology -->
                        <div class="bg-cyan-50/70 p-3 rounded-xl border border-cyan-100">
                            <p class="text-[10px] font-bold text-cyan-800 uppercase tracking-wider">Methodology & Design</p>
                            <div id="drawerMethodologies" class="flex flex-wrap gap-1.5 mt-1.5"></div>
                        </div>

                        <!-- Tech Stack & Tools -->
                        <div class="bg-emerald-50/70 p-3 rounded-xl border border-emerald-100">
                            <p class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider">Tech Stack & Tools Used</p>
                            <div id="drawerTechStack" class="flex flex-wrap gap-1.5 mt-1.5"></div>
                        </div>

                        <!-- Abstract -->
                        <div class="pt-2">
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Abstract</h4>
                            <div class="max-h-48 overflow-y-auto rounded-xl bg-slate-50 p-3 text-xs text-gray-600 leading-relaxed border border-gray-200" id="drawerAbstract"></div>
                        </div>
                    </div>

                    <!-- NON-THESIS NODE SECTION (CONNECTED THESES LIST) -->
                    <div id="drawerConnectedSection" class="hidden pt-2 border-t border-gray-100">
                        <h4 id="drawerConnectedHeading" class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Connected Research Theses</h4>
                        <div id="drawerConnectedList" class="space-y-2 max-h-96 overflow-y-auto pr-1"></div>
                    </div>
                </div>

                <!-- Drawer Action Footer (For Thesis) -->
                <div id="drawerFooter" class="border-t border-gray-200 p-4 bg-slate-50">
                    <a
                        id="drawerReadBtn"
                        href="#"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#700000] px-4 py-2.5 text-xs font-bold text-[#FFD700] hover:bg-[#800000] shadow-sm transition cursor-pointer">
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
                            gravitationalConstant: -40,
                            centralGravity: 0.008,
                            springLength: 120,
                            springConstant: 0.08
                        },
                        stabilization: { iterations: 160 }
                    },
                    interaction: {
                        hover: true,
                        tooltipDelay: 200,
                        zoomView: true,
                        dragView: true
                    }
                };

                network = new vis.Network(container, graphData, options);

                // Click event on any node
                network.on('click', function(params) {
                    if (params.nodes.length > 0) {
                        const nodeId = params.nodes[0];
                        const node = graphData.nodes.get(nodeId);
                        if (node && node.meta) {
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
            const drawer = document.getElementById('detailsDrawer');
            const drawerBadge = document.getElementById('drawerBadge');
            const drawerTitle = document.getElementById('drawerTitle');
            const drawerSubtitle = document.getElementById('drawerSubtitle');
            const thesisSections = document.getElementById('drawerThesisSections');
            const connectedSection = document.getElementById('drawerConnectedSection');
            const connectedList = document.getElementById('drawerConnectedList');
            const connectedHeading = document.getElementById('drawerConnectedHeading');
            const drawerFooter = document.getElementById('drawerFooter');
            const readBtn = document.getElementById('drawerReadBtn');

            if (meta.type === 'thesis') {
                // THESIS NODE DETAILS
                drawerBadge.textContent = 'THESIS DETAILS';
                drawerBadge.className = 'text-xs font-bold text-[#700000] uppercase tracking-wider px-2 py-0.5 rounded bg-amber-50 border border-amber-200';
                
                drawerTitle.textContent = meta.full_title || 'Untitled Thesis';
                drawerSubtitle.textContent = meta.author ? 'By ' + meta.author : 'SAC Researchers';

                // Render Concept Pills
                const conceptsContainer = document.getElementById('drawerConcepts');
                conceptsContainer.innerHTML = '';
                (meta.concepts || []).forEach(c => {
                    const pill = document.createElement('span');
                    pill.className = 'inline-block px-2.5 py-1 rounded-lg bg-purple-100 text-purple-800 text-[11px] font-semibold border border-purple-200';
                    pill.textContent = c;
                    conceptsContainer.appendChild(pill);
                });
                if ((meta.concepts || []).length === 0) {
                    conceptsContainer.innerHTML = '<span class="text-gray-400 italic text-[11px]">General research topic</span>';
                }

                // Render Methodology Pills
                const methodsContainer = document.getElementById('drawerMethodologies');
                methodsContainer.innerHTML = '';
                (meta.methodologies || []).forEach(m => {
                    const pill = document.createElement('span');
                    pill.className = 'inline-block px-2.5 py-1 rounded-lg bg-cyan-100 text-cyan-800 text-[11px] font-semibold border border-cyan-200';
                    pill.textContent = m;
                    methodsContainer.appendChild(pill);
                });

                // Render Tech Stack Pills
                const techContainer = document.getElementById('drawerTechStack');
                techContainer.innerHTML = '';
                (meta.tech_stack || []).forEach(t => {
                    const pill = document.createElement('span');
                    pill.className = 'inline-block px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-800 text-[11px] font-semibold border border-emerald-200';
                    pill.textContent = t;
                    techContainer.appendChild(pill);
                });
                if ((meta.tech_stack || []).length === 0) {
                    techContainer.innerHTML = '<span class="text-gray-400 italic text-[11px]">Standard scholarly documentation</span>';
                }

                document.getElementById('drawerAbstract').textContent = meta.abstract || 'No abstract available.';

                if (readBtn) readBtn.href = meta.view_url || '#';

                thesisSections.classList.remove('hidden');
                connectedSection.classList.add('hidden');
                drawerFooter.classList.remove('hidden');

            } else {
                // CONCEPT / METHODOLOGY / TECH STACK NODE DETAILS
                let badgeLabel = 'RESEARCH CONCEPT';
                let badgeClass = 'text-xs font-bold text-purple-700 uppercase tracking-wider px-2 py-0.5 rounded bg-purple-50 border border-purple-200';

                if (meta.type === 'methodology') {
                    badgeLabel = 'RESEARCH METHODOLOGY';
                    badgeClass = 'text-xs font-bold text-cyan-800 uppercase tracking-wider px-2 py-0.5 rounded bg-cyan-50 border border-cyan-200';
                } else if (meta.type === 'tech_stack') {
                    badgeLabel = 'TECH STACK & TOOLS';
                    badgeClass = 'text-xs font-bold text-emerald-800 uppercase tracking-wider px-2 py-0.5 rounded bg-emerald-50 border border-emerald-200';
                }

                drawerBadge.textContent = badgeLabel;
                drawerBadge.className = badgeClass;

                drawerTitle.textContent = meta.name || 'Research Topic';
                const count = (meta.theses || []).length;
                drawerSubtitle.textContent = `Connected with ${count} repository thesis paper${count === 1 ? '' : 's'}`;

                connectedHeading.textContent = `Papers using this ${meta.type === 'concept' ? 'concept' : (meta.type === 'methodology' ? 'methodology' : 'tech stack')}`;

                connectedList.innerHTML = '';
                (meta.theses || []).forEach(t => {
                    const card = document.createElement('div');
                    card.className = 'bg-slate-50 hover:bg-slate-100 p-3 rounded-xl border border-gray-200 transition';
                    card.innerHTML = `
                        <p class="text-xs font-bold text-gray-900 leading-snug line-clamp-2">${t.title}</p>
                        <p class="text-[11px] text-gray-500 mt-1">${t.author || 'SAC Researchers'}</p>
                        <a href="${t.view_url}" class="inline-flex items-center gap-1 text-[11px] font-bold text-[#700000] hover:underline mt-2">
                            <span>View Thesis Paper</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                    `;
                    connectedList.appendChild(card);
                });

                thesisSections.classList.add('hidden');
                connectedSection.classList.remove('hidden');
                drawerFooter.classList.add('hidden');
            }

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
                const name = (n.meta?.name || '').toLowerCase();
                if (label.includes(query) || full.includes(query) || name.includes(query)) {
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
