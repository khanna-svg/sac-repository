<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SArchive - Knowledge Graph</title>
    <link rel="icon" href="{{ asset('images/airis-logo.webp') }}" type="image/webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Vis.js Network CDN -->
    <script type="text/javascript" src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        #networkGraph {
            width: 100%;
            height: 100%;
            min-height: 400px;
            background: radial-gradient(circle, #ffffff 0%, #f8fafc 100%);
        }
    </style>
</head>

<body class="h-screen bg-slate-50 text-slate-800 font-sans overflow-hidden">

    {{-- SAC PORTAL TOP HEADER --}}
    @include('partials.header', ['title' => 'KNOWLEDGE GRAPH'])

    @include('partials.sidebar')

    <main id="mainContent" class="md:ml-64 h-screen flex flex-col pt-16 md:pt-20 transition-all duration-300 overflow-hidden">

        <!-- Top Control Toolbar & Subtitle -->
        <div class="border-b border-gray-200 bg-white px-4 md:px-8 py-3 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3">

            <!-- Toolbar Controls -->
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Department Cluster Filter -->
                <div class="relative">
                    <select
                        id="deptClusterFilter"
                        onchange="filterByDepartment(this.value)"
                        class="rounded-xl border border-gray-300 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-gray-700 focus:border-[#0A2549] focus:ring-1 focus:ring-[#0A2549] shadow-2xs cursor-pointer outline-none">
                        <option value="all">All Departments (Full Network)</option>
                        <option value="dte">Teacher Education (DTE)</option>
                        <option value="cjed">Criminal Justice Education (CJED)</option>
                        <option value="bused">Business Education (BUSED)</option>
                        <option value="eng">Engineering (ENG)</option>
                        <option value="lad">Liberal Arts (LAD)</option>
                    </select>
                </div>

                <!-- Search Filter in Graph -->
                <div class="relative">
                    <input
                        type="text"
                        id="graphSearchInput"
                        placeholder="Search concept, tech, or thesis..."
                        class="rounded-xl border border-gray-300 bg-slate-50 px-3 py-1.5 pl-8 text-xs text-gray-800 focus:border-[#0A2549] focus:outline-none focus:ring-1 focus:ring-[#0A2549] w-44 md:w-52 transition">
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
                    <svg class="w-3.5 h-3.5 text-[#0A2549]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                    <span id="physicsStatusText">Freeze</span>
                </button>
            </div>
        </div>

        <!-- Legend Banner -->
        <div class="border-b border-gray-200 bg-slate-100/80 px-4 md:px-8 py-2 flex items-center gap-2 md:gap-3 overflow-x-auto text-[11px] font-medium text-gray-700 select-none">
            <span class="font-bold text-gray-700 uppercase tracking-wider text-[10px] shrink-0 mr-0.5">Legend:</span>
            
            <!-- Thesis Papers -->
            <span class="inline-flex items-center gap-2 bg-white px-2.5 py-1 rounded-lg border border-gray-200 shadow-2xs shrink-0">
                <span style="background-color: #0A2549; border: 1.5px solid #CBA144;" class="w-3.5 h-3.5 rounded-xs inline-block shrink-0 shadow-2xs"></span>
                <span class="font-semibold text-gray-800">Thesis Papers</span>
            </span>

            <!-- Concepts & Topics -->
            <span class="inline-flex items-center gap-2 bg-white px-2.5 py-1 rounded-lg border border-gray-200 shadow-2xs shrink-0">
                <span style="background-color: #7c3aed; border: 1.5px solid #c084fc; border-radius: 6px;" class="w-3.5 h-3.5 inline-block shrink-0 shadow-2xs"></span>
                <span class="font-semibold text-gray-800">Concepts & Topics</span>
            </span>

            <!-- Methodology & Design -->
            <span class="inline-flex items-center gap-2 bg-white px-2.5 py-1 rounded-lg border border-gray-200 shadow-2xs shrink-0">
                <span style="background-color: #0891b2; border: 1.5px solid #67e8f9; border-radius: 3px;" class="w-3.5 h-3.5 inline-block shrink-0 shadow-2xs"></span>
                <span class="font-semibold text-gray-800">Methodology & Design</span>
            </span>

            <!-- Tech Stack & Tools -->
            <span class="inline-flex items-center gap-2 bg-white px-2.5 py-1 rounded-lg border border-gray-200 shadow-2xs shrink-0">
                <span style="background-color: #059669; border: 1.5px solid #34d399; border-radius: 3px;" class="w-3.5 h-3.5 inline-block shrink-0 shadow-2xs"></span>
                <span class="font-semibold text-gray-800">Tech Stack & Tools</span>
            </span>
        </div>

        <!-- Main Graph Canvas Container -->
        <div class="relative flex-1 w-full min-h-0 bg-white overflow-hidden">
            <!-- Active Search Filter Floating Pill (Google-Style) -->
            <div
                id="graphSearchFilterBanner"
                class="hidden absolute top-4 left-1/2 -translate-x-1/2 z-20 bg-white/95 backdrop-blur-md border border-[#0A2549]/20 shadow-xl rounded-2xl px-4 py-2 flex items-center gap-3.5 text-xs max-w-[92vw] transition-all duration-300">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#CBA144] animate-pulse shrink-0"></span>
                    <p class="text-gray-700 truncate">
                        <span class="text-gray-500 font-medium">Search Cluster:</span>
                        <strong id="filterBannerQuery" class="text-[#0A2549] font-black ml-1"></strong>
                        <span id="filterBannerCount" class="text-xs text-gray-500 ml-1 font-semibold"></span>
                    </p>
                </div>
                <button
                    type="button"
                    onclick="resetFullGraphView()"
                    title="View Full Knowledge Graph"
                    class="shrink-0 px-3 py-1.5 rounded-xl bg-[#0A2549] hover:bg-[#123668] text-[#CBA144] hover:text-white font-bold text-xs transition flex items-center gap-1.5 cursor-pointer shadow-xs border border-[#CBA144]/30">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0 0h4.5m-4.5 0L9 3.75M20.25 20.25v-4.5m0 0h-4.5m4.5 0L15 20.25M3.75 20.25h4.5m-4.5 0v-4.5m0 4.5L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9" />
                    </svg>
                    <span>Show Full Network</span>
                </button>
            </div>

            <div id="networkGraph" class="w-full h-full"></div>

            <!-- Loading Spinner Indicator -->
            <div id="graphLoader" class="absolute inset-0 bg-white/80 backdrop-blur-xs flex flex-col items-center justify-center gap-3 z-10 transition-opacity">
                <svg class="w-8 h-8 animate-spin text-[#0A2549]" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-xs font-bold text-[#0A2549] tracking-wide">Building Knowledge Graph...</p>
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

            <!-- Slide-Out Details Drawer (Fixed to Viewport so Footer Button is Always 100% Visible) -->
            <div
                id="detailsDrawer"
                class="fixed top-16 md:top-20 right-0 bottom-0 w-80 md:w-96 bg-white border-l border-gray-200 shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out z-40 flex flex-col">
                
                <!-- Drawer Header -->
                <div class="border-b border-gray-100 p-4 bg-slate-50 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2">
                        <span id="drawerBadge" style="background-color: #0A2549; color: #FFFFFF; border: 1.5px solid #CBA144;" class="text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-lg">
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
                            <div class="max-h-40 overflow-y-auto rounded-xl bg-slate-50 p-3 text-xs text-gray-600 leading-relaxed border border-gray-200" id="drawerAbstract"></div>
                        </div>
                    </div>

                    <!-- NON-THESIS NODE SECTION (CONNECTED THESES LIST) -->
                    <div id="drawerConnectedSection" class="hidden pt-2 border-t border-gray-100">
                        <h4 id="drawerConnectedHeading" class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Connected Research Theses</h4>
                        <div id="drawerConnectedList" class="space-y-2 max-h-96 overflow-y-auto pr-1"></div>
                    </div>
                </div>

                <!-- Drawer Action Footer (Pinned at Bottom, Always Visible) -->
                <div id="drawerFooter" class="border-t border-gray-200 p-4 bg-slate-50 shrink-0 shadow-md">
                    <a
                        id="drawerReadBtn"
                        href="#"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#0A2549] px-4 py-3 text-xs font-bold text-[#CBA144] hover:bg-[#123668] shadow-md transition cursor-pointer">
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
        let isFilteredView = false;
        let activeFilteredNodeIds = [];
        let hasAppliedInitialSearchOrFocus = false;

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
                        shape: 'box',
                        font: { face: 'sans-serif', size: 11, color: '#ffffff' },
                        borderWidth: 2,
                        shadow: true,
                        margin: 8
                    },
                    groups: {
                        thesis: {
                            color: {
                                background: '#0A2549',
                                border: '#CBA144',
                                highlight: { background: '#123668', border: '#dfb556' },
                                hover: { background: '#123668', border: '#dfb556' }
                            },
                            shape: 'box',
                            borderWidth: 2,
                            font: { color: '#ffffff', size: 11, bold: true, face: 'sans-serif' }
                        },
                        concept: {
                            color: {
                                background: '#7c3aed',
                                border: '#c084fc',
                                highlight: { background: '#6d28d9', border: '#d8b4fe' },
                                hover: { background: '#6d28d9', border: '#d8b4fe' }
                            },
                            shape: 'box',
                            shapeProperties: { borderRadius: 16 },
                            borderWidth: 1.5,
                            font: { color: '#ffffff', size: 10, bold: true, face: 'sans-serif' }
                        },
                        methodology: {
                            color: {
                                background: '#0891b2',
                                border: '#67e8f9',
                                highlight: { background: '#0e7490', border: '#a5f3fc' },
                                hover: { background: '#0e7490', border: '#a5f3fc' }
                            },
                            shape: 'box',
                            shapeProperties: { borderRadius: 8 },
                            borderWidth: 1.5,
                            font: { color: '#ffffff', size: 10, bold: true, face: 'sans-serif' }
                        },
                        tech_stack: {
                            color: {
                                background: '#059669',
                                border: '#34d399',
                                highlight: { background: '#047857', border: '#6ee7b7' },
                                hover: { background: '#047857', border: '#6ee7b7' }
                            },
                            shape: 'box',
                            shapeProperties: { borderRadius: 8 },
                            borderWidth: 1.5,
                            font: { color: '#ffffff', size: 10, bold: true, face: 'sans-serif' }
                        }
                    },
                    edges: {
                        width: 1.5,
                        smooth: {
                            type: 'continuous',
                            roundness: 0.2
                        },
                        font: { size: 9, align: 'middle', color: '#94a3b8' },
                        color: { color: '#cbd5e1', highlight: '#0A2549', hover: '#0A2549' },
                        arrows: { to: { enabled: true, scaleFactor: 0.5 } }
                    },
                    physics: {
                        solver: 'forceAtlas2Based',
                        forceAtlas2Based: {
                            gravitationalConstant: -130,
                            centralGravity: 0.003,
                            springLength: 200,
                            springConstant: 0.035,
                            damping: 0.45,
                            avoidOverlap: 1
                        },
                        stabilization: {
                            iterations: 200,
                            updateInterval: 25
                        }
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

                // Once stabilized, hide loader and auto-freeze physics so graph stays perfectly organized and stationary
                network.once('stabilizationIterationsDone', function() {
                    loader.classList.add('hidden');
                    network.setOptions({ physics: { enabled: false } });
                    physicsEnabled = false;
                    const btnText = document.getElementById('physicsStatusText');
                    if (btnText) btnText.textContent = 'Unfreeze';
                    setTimeout(() => {
                        applyUrlSearchAndFilter();
                    }, 150);
                });

                // Fallback in case stabilization completes early or takes longer
                setTimeout(() => {
                    loader.classList.add('hidden');
                    if (network && physicsEnabled) {
                        network.setOptions({ physics: { enabled: false } });
                        physicsEnabled = false;
                        const btnText = document.getElementById('physicsStatusText');
                        if (btnText) btnText.textContent = 'Unfreeze';
                    }
                    applyUrlSearchAndFilter();
                }, 2800);

            } catch (err) {
                console.error(err);
                loader.classList.add('hidden');
                emptyState.classList.remove('hidden');
            }
        }

        function applyUrlSearchAndFilter() {
            if (hasAppliedInitialSearchOrFocus || !network || !graphData.nodes) return;
            const urlParams = new URLSearchParams(window.location.search);
            const query = urlParams.get('q') || urlParams.get('search') || '';
            const docs = urlParams.get('docs') || '';
            let focusId = urlParams.get('focus') || urlParams.get('node') || '';

            if (focusId && !focusId.startsWith('doc_') && !isNaN(focusId)) {
                focusId = 'doc_' + focusId;
            }

            if (query.trim() || docs.trim()) {
                hasAppliedInitialSearchOrFocus = true;
                filterAndZoomConnectedResults(query.trim(), docs.trim(), focusId);
            } else if (focusId) {
                hasAppliedInitialSearchOrFocus = true;
                applyUrlFocus(focusId);
            }
        }

        function applyUrlFocus(focusId) {
            if (!network || !graphData.nodes || !focusId) return;

            if (!focusId.startsWith('doc_') && !isNaN(focusId)) {
                focusId = 'doc_' + focusId;
            }

            const targetNode = graphData.nodes.get(focusId);
            if (targetNode) {
                network.selectNodes([focusId]);
                setTimeout(() => {
                    network.focus(focusId, {
                        scale: 1.45,
                        animation: {
                            duration: 1000,
                            easingFunction: 'easeInOutQuad'
                        }
                    });
                    if (targetNode.meta) {
                        openDetailsDrawer(targetNode.meta);
                    }
                }, 200);
            }
        }

        function filterAndZoomConnectedResults(query, docsParam = '', focusId = '') {
            if (!network || !graphData.nodes) return;

            const matchingThesisNodeIds = new Set();
            const directlyMatchedNodeIds = new Set();

            // 1. If docsParam is passed from documents search (e.g. docs=78,86,83)
            if (docsParam) {
                const idList = docsParam.split(',').map(s => s.trim()).filter(Boolean);
                idList.forEach(id => {
                    const nodeId = id.startsWith('doc_') ? id : 'doc_' + id;
                    if (graphData.nodes.get(nodeId)) {
                        matchingThesisNodeIds.add(nodeId);
                    }
                });
            }

            // 2. Keyword matching fallback ONLY if docsParam did not provide matches
            // or when searching directly inside the graph search input
            if (matchingThesisNodeIds.size === 0 && query) {
                const stopWords = new Set([
                    'a', 'an', 'the', 'in', 'on', 'at', 'to', 'for', 'of', 'and', 'or', 'is', 'are', 'with', 'from', 'by', 'as', 'into', 'about'
                ]);
                const academicStopWords = new Set([
                    'based', 'project', 'projects', 'system', 'systems', 'study', 'studies', 'development', 'analysis', 'using', 'proposed', 'application', 'level', 'among', 'effects', 'evaluation', 'through', 'program', 'practices', 'paper', 'research'
                ]);

                const cleanQuery = query.toLowerCase().replace(/[^\w\s-]/g, ' ').trim();
                const rawTokens = cleanQuery.split(/\s+/).filter(t => t.length >= 2 && !stopWords.has(t));
                const meaningfulTokens = rawTokens.filter(t => !academicStopWords.has(t));
                const searchTokens = meaningfulTokens.length > 0 ? meaningfulTokens : rawTokens;
                const fullLowerQuery = query.toLowerCase().trim();

                // Helper to check if text contains a token (using word boundary for short abbreviations like 'iot', 'ai')
                const testTokenMatch = (targetText, token) => {
                    if (token.length <= 4) {
                        const regex = new RegExp(`\\b${token.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\b`, 'i');
                        return regex.test(targetText);
                    }
                    return targetText.includes(token);
                };

                graphData.nodes.forEach(n => {
                    const label = (n.label || '').toLowerCase();
                    const meta = n.meta || {};

                    if (meta.type === 'thesis') {
                        const fullTitle = (meta.full_title || '').toLowerCase();
                        const abstract = (meta.abstract || '').toLowerCase();
                        const author = (meta.author || '').toLowerCase();
                        const concepts = (meta.concepts || []).map(c => c.toLowerCase()).join(' ');
                        const methods = (meta.methodologies || []).map(m => m.toLowerCase()).join(' ');
                        const tech = (meta.tech_stack || []).map(t => t.toLowerCase()).join(' ');
                        const combined = `${label} ${fullTitle} ${abstract} ${author} ${concepts} ${methods} ${tech}`;

                        const phraseMatch = combined.includes(fullLowerQuery);
                        const tokenMatch = searchTokens.length > 0 && searchTokens.every(t => testTokenMatch(combined, t));

                        if (phraseMatch || tokenMatch) {
                            matchingThesisNodeIds.add(n.id);
                        }
                    } else {
                        const nodeName = (meta.name || label).toLowerCase();
                        const phraseMatch = nodeName.includes(fullLowerQuery);
                        const tokenMatch = searchTokens.length > 0 && searchTokens.every(t => testTokenMatch(nodeName, t));

                        if (phraseMatch || tokenMatch) {
                            directlyMatchedNodeIds.add(n.id);
                            (meta.theses || []).forEach(t => {
                                const tNodeId = 'doc_' + t.id;
                                if (graphData.nodes.get(tNodeId)) {
                                    matchingThesisNodeIds.add(tNodeId);
                                }
                            });
                        }
                    }
                });
            }

            if (focusId && graphData.nodes.get(focusId)) {
                if (focusId.startsWith('doc_')) {
                    matchingThesisNodeIds.add(focusId);
                } else {
                    directlyMatchedNodeIds.add(focusId);
                }
            }

            // 3. Find all connected entities (the matching theses + their connected concepts, methods, tools)
            const connectedNodeIds = new Set(matchingThesisNodeIds);
            directlyMatchedNodeIds.forEach(id => connectedNodeIds.add(id));

            if (graphData.edges) {
                graphData.edges.forEach(e => {
                    if (matchingThesisNodeIds.has(e.from)) {
                        connectedNodeIds.add(e.to);
                    } else if (matchingThesisNodeIds.has(e.to)) {
                        connectedNodeIds.add(e.from);
                    }

                    if (directlyMatchedNodeIds.has(e.from)) {
                        connectedNodeIds.add(e.to);
                    } else if (directlyMatchedNodeIds.has(e.to)) {
                        connectedNodeIds.add(e.from);
                    }
                });
            }

            // Fallback: If nothing matched, keep graph visible and log warning
            if (connectedNodeIds.size === 0) {
                console.warn('No matching graph nodes found for query:', query);
                return;
            }

            // 4. Hide all unrelated nodes so only connected results are shown
            const updates = [];
            graphData.nodes.forEach(n => {
                const isVisible = connectedNodeIds.has(n.id);
                updates.push({
                    id: n.id,
                    hidden: !isVisible,
                    opacity: isVisible ? 1 : 0
                });
            });
            graphData.nodes.update(updates);

            // 5. Update Active Search Filter Pill
            const banner = document.getElementById('graphSearchFilterBanner');
            const queryEl = document.getElementById('filterBannerQuery');
            const countEl = document.getElementById('filterBannerCount');
            const searchInput = document.getElementById('graphSearchInput');

            if (banner) banner.classList.remove('hidden');
            if (queryEl) queryEl.textContent = query || 'Search Results';
            if (countEl) {
                const matchingDocCount = Array.from(connectedNodeIds).filter(id => id.startsWith('doc_')).length;
                const connectedEntityCount = connectedNodeIds.size - matchingDocCount;
                countEl.textContent = `(${matchingDocCount} ${matchingDocCount === 1 ? 'thesis' : 'theses'}${connectedEntityCount > 0 ? `, ${connectedEntityCount} connected topics & tools` : ''})`;
            }
            if (searchInput && query) searchInput.value = query;

            // 6. Highlight matching theses and smoothly zoom camera onto connected results
            isFilteredView = true;
            activeFilteredNodeIds = Array.from(connectedNodeIds);

            // Visually select the matching thesis nodes so they pop out immediately
            if (matchingThesisNodeIds.size > 0) {
                network.selectNodes(Array.from(matchingThesisNodeIds));
            }

            setTimeout(() => {
                network.fit({
                    nodes: activeFilteredNodeIds,
                    animation: {
                        duration: 1000,
                        easingFunction: 'easeInOutQuad'
                    }
                });

                if (focusId && graphData.nodes.get(focusId)) {
                    setTimeout(() => {
                        network.selectNodes([focusId]);
                        const targetNode = graphData.nodes.get(focusId);
                        if (targetNode && targetNode.meta) {
                            openDetailsDrawer(targetNode.meta);
                        }
                    }, 300);
                }
            }, 200);
        }

        function resetFullGraphView() {
            if (!network || !graphData.nodes) return;

            const updates = [];
            graphData.nodes.forEach(n => {
                updates.push({ id: n.id, hidden: false, opacity: 1 });
            });
            graphData.nodes.update(updates);

            const banner = document.getElementById('graphSearchFilterBanner');
            if (banner) banner.classList.add('hidden');

            const input = document.getElementById('graphSearchInput');
            if (input) input.value = '';

            isFilteredView = false;
            activeFilteredNodeIds = [];

            network.fit({
                animation: {
                    duration: 800,
                    easingFunction: 'easeInOutQuad'
                }
            });

            // Clean query parameters from URL without reloading
            const cleanUrl = window.location.pathname;
            window.history.replaceState({}, document.title, cleanUrl);
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
                drawerBadge.className = 'text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-lg';
                drawerBadge.style.backgroundColor = '#0A2549';
                drawerBadge.style.color = '#FFFFFF';
                drawerBadge.style.border = '1.5px solid #CBA144';
                
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
                let bgColor = '#7c3aed';
                let borderColor = '#c084fc';

                if (meta.type === 'methodology') {
                    badgeLabel = 'RESEARCH METHODOLOGY';
                    bgColor = '#0891b2';
                    borderColor = '#67e8f9';
                } else if (meta.type === 'tech_stack') {
                    badgeLabel = 'TECH STACK & TOOLS';
                    bgColor = '#059669';
                    borderColor = '#34d399';
                }

                drawerBadge.textContent = badgeLabel;
                drawerBadge.className = 'text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-lg';
                drawerBadge.style.backgroundColor = bgColor;
                drawerBadge.style.color = '#FFFFFF';
                drawerBadge.style.border = `1.5px solid ${borderColor}`;

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
                        <a href="${t.view_url}" class="inline-flex items-center gap-1 text-[11px] font-bold text-[#0A2549] hover:underline mt-2">
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
            if (!network) return;
            if (isFilteredView && activeFilteredNodeIds.length > 0) {
                network.fit({
                    nodes: activeFilteredNodeIds,
                    animation: { duration: 600, easingFunction: 'easeInOutQuad' }
                });
            } else {
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

        // Department Cluster Filter Function
        function filterByDepartment(dept) {
            if (!network || !graphData.nodes) return;

            const banner = document.getElementById('graphSearchFilterBanner');
            if (banner) banner.classList.add('hidden');

            if (dept === 'all') {
                const allUpdates = [];
                graphData.nodes.forEach(n => {
                    allUpdates.push({ id: n.id, hidden: false, opacity: 1 });
                });
                graphData.nodes.update(allUpdates);
                isFilteredView = false;
                activeFilteredNodeIds = [];
                resetGraphView();
                return;
            }

            // Find all thesis nodes in this department
            const targetDeptTheses = new Set();
            const visibleNodeIds = new Set();

            graphData.nodes.forEach(n => {
                if (n.meta && n.meta.type === 'thesis') {
                    const nodeDept = (n.meta.department || '').toLowerCase();
                    if (nodeDept === dept.toLowerCase()) {
                        targetDeptTheses.add(n.id);
                        visibleNodeIds.add(n.id);
                    }
                }
            });

            // Keep connected concept, methodology, and tech stack nodes visible
            graphData.edges.forEach(e => {
                if (targetDeptTheses.has(e.from)) {
                    visibleNodeIds.add(e.to);
                } else if (targetDeptTheses.has(e.to)) {
                    visibleNodeIds.add(e.from);
                }
            });

            // Update node visibility
            const updates = [];
            graphData.nodes.forEach(n => {
                const isVisible = visibleNodeIds.has(n.id);
                updates.push({
                    id: n.id,
                    hidden: !isVisible,
                    opacity: isVisible ? 1 : 0.1
                });
            });
            graphData.nodes.update(updates);

            isFilteredView = true;
            activeFilteredNodeIds = Array.from(visibleNodeIds);

            // Center view on this cluster
            setTimeout(() => {
                if (visibleNodeIds.size > 0) {
                    network.fit({
                        nodes: Array.from(visibleNodeIds),
                        animation: { duration: 600, easingFunction: 'easeInOutQuad' }
                    });
                }
            }, 100);
        }

        // Search Input Handling with cluster filtering & zoom
        let searchDebounceTimer = null;
        const graphSearchInput = document.getElementById('graphSearchInput');
        if (graphSearchInput) {
            graphSearchInput.addEventListener('input', function(e) {
                const query = e.target.value.trim();
                if (searchDebounceTimer) clearTimeout(searchDebounceTimer);

                searchDebounceTimer = setTimeout(() => {
                    if (!query) {
                        resetFullGraphView();
                    } else {
                        filterAndZoomConnectedResults(query);
                    }
                }, 300);
            });

            graphSearchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const query = e.target.value.trim();
                    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
                    if (!query) {
                        resetFullGraphView();
                    } else {
                        filterAndZoomConnectedResults(query);
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', initKnowledgeGraph);
    </script>
</body>

</html>
