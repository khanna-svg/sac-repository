<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class KnowledgeGraphController extends Controller
{
    /**
     * Display Knowledge Graph view.
     */
    public function indexView()
    {
        return view('graph');
    }

    /**
     * Return Nodes and Edges JSON for Vis.js.
     */
    public function data()
    {
        $documents = Document::select('id', 'title', 'author', 'department', 'course_code', 'abstract')
            ->latest()
            ->get();

        $nodes = [];
        $edges = [];

        $departmentsAdded = [];
        $authorsAdded = [];

        foreach ($documents as $doc) {
            // 1. Department Hub Node
            $deptName = $doc->department ?: 'General Academic';
            $deptNodeId = 'dept_' . md5($deptName);

            if (!isset($departmentsAdded[$deptNodeId])) {
                $departmentsAdded[$deptNodeId] = true;
                $nodes[] = [
                    'id' => $deptNodeId,
                    'label' => strtoupper($deptName),
                    'group' => 'department',
                    'shape' => 'hexagon',
                    'size' => 30,
                    'color' => [
                        'background' => '#700000',
                        'border' => '#FFD700',
                        'highlight' => ['background' => '#8d0000', 'border' => '#FFD700']
                    ],
                    'font' => ['color' => '#ffffff', 'face' => 'sans-serif', 'bold' => true]
                ];
            }

            // 2. Thesis Document Node
            $thesisNodeId = 'doc_' . $doc->id;
            $nodes[] = [
                'id' => $thesisNodeId,
                'label' => mb_strimwidth($doc->title, 0, 35, '...'),
                'title' => $doc->title,
                'doc_id' => $doc->id,
                'full_title' => $doc->title,
                'author' => $doc->author,
                'department' => $doc->department,
                'abstract' => mb_strimwidth($doc->abstract ?? '', 0, 180, '...'),
                'group' => 'thesis',
                'shape' => 'dot',
                'size' => 20,
                'color' => [
                    'background' => '#FFD700',
                    'border' => '#700000',
                    'highlight' => ['background' => '#FFF', 'border' => '#700000']
                ],
                'font' => ['color' => '#1e293b', 'face' => 'sans-serif']
            ];

            // Edge: Thesis -> Department
            $edges[] = [
                'from' => $deptNodeId,
                'to' => $thesisNodeId,
                'color' => ['color' => '#cbd5e1', 'highlight' => '#700000'],
                'length' => 120
            ];

            // 3. Author Node
            if ($doc->author) {
                // Split multi-authors if separated by semicolon or comma
                $authorNames = preg_split('/[;,]/', $doc->author);
                foreach ($authorNames as $aName) {
                    $cleanAuthor = trim($aName);
                    if (!$cleanAuthor) continue;

                    $authorNodeId = 'author_' . md5($cleanAuthor);
                    if (!isset($authorsAdded[$authorNodeId])) {
                        $authorsAdded[$authorNodeId] = true;
                        $nodes[] = [
                            'id' => $authorNodeId,
                            'label' => $cleanAuthor,
                            'group' => 'author',
                            'shape' => 'ellipse',
                            'size' => 14,
                            'color' => [
                                'background' => '#f1f5f9',
                                'border' => '#64748b',
                                'highlight' => ['background' => '#e2e8f0', 'border' => '#334155']
                            ],
                            'font' => ['color' => '#334155', 'size' => 11]
                        ];
                    }

                    // Edge: Author -> Thesis
                    $edges[] = [
                        'from' => $authorNodeId,
                        'to' => $thesisNodeId,
                        'dashes' => true,
                        'color' => ['color' => '#94a3b8']
                    ];
                }
            }
        }

        return response()->json([
            'nodes' => $nodes,
            'edges' => $edges
        ]);
    }
}
