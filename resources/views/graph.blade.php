<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knowledge Graph - SAC Thesis System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Vis.js CDN for Network Graph -->
    <script src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
</head>

<body class="min-h-screen bg-slate-50 text-slate-800 font-sans">

    @include('partials.sidebar')

    <main class="md:ml-64 min-h-screen p-4 sm:p-6 md:p-10 transition-all pt-16 md:pt-10">
        <div class="mx-auto max-w-6xl space-y-4">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-[#700000] flex items-center gap-2.5">
                        <svg class="w-8 h-8 text-[#700000]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 16.875h3.375m0 0h3.375m-3.375 0V13.5m0 3.375v3.375M6 10.5h2.25a2.25 2.25 0 002.25-2.25V6a2.25 2.25 0 00-2.25-2.25H6A2.25 2.25 0 003.75 6v2.25A2.25 2.25 0 006 10.5zm0 9.75h2.25A2.25 2.25 0 0010.5 18v-2.25a2.25 2.25 0 00-2.25-2.25H6a2.25 2.25 0 00-2.25 2.25V18A2.25 2.25 0 006 20.25zm9.75-9.75H18a2.25 2.25 0 002.25-2.25V6A2.25 2.25 0 0018 3.75h-2.25A2.25 2.25 0 0013.5 6v2.25a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                        <span>Interactive Knowledge Graph</span>
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-500">
                        Explore interconnected relationships between academic departments, thesis papers, and authors.
                    </p>
                </div>

                <div class="flex items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-[#700000] inline-block"></span> Department</span>
                    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-[#FFD700] border border-[#700000] inline-block"></span> Thesis</span>
                    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-slate-300 inline-block"></span> Author</span>
                </div>
            </div>

            <!-- Canvas Card -->
            <div class="relative rounded-3xl border border-gray-200 bg-white shadow-sm overflow-hidden h-[600px]">
                <div id="networkGraph" class="w-full h-full"></div>

                <!-- Slide-out Drawer for Clicked Node Details -->
                <div id="nodeDetailsDrawer" class="absolute top-4 right-4 z-20 w-80 rounded-2xl border border-gray-200 bg-white/95 backdrop-blur-md p-5 shadow-xl transition-all translate-x-96 hidden">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#700000]">Thesis Details</span>
                        <button onclick="closeDrawer()" class="text-gray-400 hover:text-gray-700 font-bold text-sm">✕</button>
                    </div>
                    <h4 id="drawerTitle" class="mt-3 text-sm font-bold text-gray-900 leading-snug"></h4>
                    <p id="drawerAuthor" class="mt-1 text-xs text-[#700000] font-semibold"></p>
                    <p id="drawerAbstract" class="mt-2 text-xs text-gray-600 leading-relaxed"></p>
                    <a id="drawerLink" href="#" class="mt-4 inline-flex items-center justify-center gap-1.5 w-full rounded-xl bg-[#700000] px-4 py-2 text-xs font-bold text-[#FFD700] hover:bg-[#800000]">
                        <span>Open Thesis Page</span>
                    </a>
                </div>
            </div>

        </div>
    </main>

    <script>
        let network = null;
        let graphData = null;

        async function initGraph() {
            try {
                const res = await fetch('/backend/graph/data');
                if (!res.ok) throw new Error('Failed to load graph data');
                graphData = await res.json();

                const container = document.getElementById('networkGraph');
                const data = {
                    nodes: new vis.DataSet(graphData.nodes),
                    edges: new vis.DataSet(graphData.edges)
                };

                const options = {
                    nodes: { font: { size: 12 } },
                    physics: {
                        stabilization: true,
                        barnesHut: { gravitationalConstant: -3000, springLength: 95 }
                    },
                    interaction: { hover: true, tooltipDelay: 200 }
                };

                network = new vis.Network(container, data, options);

                network.on('click', function (params) {
                    if (params.nodes.length > 0) {
                        const clickedId = params.nodes[0];
                        const node = graphData.nodes.find(n => n.id === clickedId);
                        if (node && node.group === 'thesis') {
                            showDrawer(node);
                        } else {
                            closeDrawer();
                        }
                    } else {
                        closeDrawer();
                    }
                });

            } catch (e) {
                console.error(e);
            }
        }

        function showDrawer(node) {
            const drawer = document.getElementById('nodeDetailsDrawer');
            document.getElementById('drawerTitle').textContent = node.full_title;
            document.getElementById('drawerAuthor').textContent = 'by ' + (node.author || 'Unknown');
            document.getElementById('drawerAbstract').textContent = node.abstract || 'No abstract available.';
            document.getElementById('drawerLink').href = '/documents/' + node.doc_id;

            drawer.classList.remove('hidden', 'translate-x-96');
            drawer.classList.add('translate-x-0');
        }

        function closeDrawer() {
            const drawer = document.getElementById('nodeDetailsDrawer');
            drawer.classList.add('translate-x-96');
            setTimeout(() => drawer.classList.add('hidden'), 300);
        }

        initGraph();
    </script>
</body>

</html>
