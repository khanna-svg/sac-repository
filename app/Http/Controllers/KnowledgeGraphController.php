<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeGraphController extends Controller
{
    /**
     * Render the Knowledge Graph view for students.
     */
    public function index(): View
    {
        return view('graph');
    }

    /**
     * Return structured node and edge data for Vis.js network visualization.
     */
    public function data(Request $request): JsonResponse
    {
        $documents = Document::all();

        $nodes = [];
        $edges = [];
        $nodeTracker = [];

        foreach ($documents as $doc) {
            $docNodeId = 'doc_' . $doc->id;

            // 1. Thesis Document Node
            if (!isset($nodeTracker[$docNodeId])) {
                $shortTitle = strlen($doc->title) > 35
                    ? substr($doc->title, 0, 32) . '...'
                    : $doc->title;

                $nodes[] = [
                    'id' => $docNodeId,
                    'label' => $shortTitle,
                    'group' => 'thesis',
                    'shape' => 'box',
                    'margin' => 10,
                    'color' => [
                        'background' => '#700000',
                        'border' => '#FFD700',
                        'highlight' => [
                            'background' => '#900000',
                            'border' => '#FFD700'
                        ]
                    ],
                    'font' => [
                        'color' => '#FFFFFF',
                        'size' => 12,
                        'face' => 'sans-serif',
                        'bold' => true
                    ],
                    'shadow' => true,
                    // Extended metadata for slide-out detail panel
                    'meta' => [
                        'type' => 'thesis',
                        'document_id' => $doc->id,
                        'full_title' => $doc->title,
                        'author' => $doc->author ?? 'Unknown Author',
                        'department' => $doc->department ?? 'General',
                        'course_code' => $doc->course_code ?? 'N/A',
                        'abstract' => $doc->abstract ?? 'No abstract provided.',
                        'view_url' => '/documents/' . $doc->id,
                        'pdf_url' => '/backend/documents/' . $doc->id . '/view'
                    ]
                ];
                $nodeTracker[$docNodeId] = true;
            }

            // 2. Department Node
            if (!empty($doc->department)) {
                $deptName = trim($doc->department);
                $deptNodeId = 'dept_' . md5(strtolower($deptName));

                if (!isset($nodeTracker[$deptNodeId])) {
                    $nodes[] = [
                        'id' => $deptNodeId,
                        'label' => strtoupper($deptName),
                        'group' => 'department',
                        'shape' => 'hexagon',
                        'size' => 25,
                        'color' => [
                            'background' => '#1e3a8a',
                            'border' => '#60a5fa',
                            'highlight' => [
                                'background' => '#1d4ed8',
                                'border' => '#93c5fd'
                            ]
                        ],
                        'font' => [
                            'color' => '#FFFFFF',
                            'size' => 11,
                            'bold' => true
                        ],
                        'meta' => [
                            'type' => 'department',
                            'name' => $deptName
                        ]
                    ];
                    $nodeTracker[$deptNodeId] = true;
                }

                $edges[] = [
                    'from' => $docNodeId,
                    'to' => $deptNodeId,
                    'label' => 'department',
                    'color' => ['color' => '#93c5fd', 'highlight' => '#3b82f6'],
                    'arrows' => 'to',
                    'smooth' => ['type' => 'cubicBezier']
                ];
            }

            // 3. Academic Program / Course Node
            if (!empty($doc->course_code)) {
                $courseName = trim($doc->course_code);
                $courseNodeId = 'course_' . md5(strtolower($courseName));

                if (!isset($nodeTracker[$courseNodeId])) {
                    $nodes[] = [
                        'id' => $courseNodeId,
                        'label' => strtoupper($courseName),
                        'group' => 'course',
                        'shape' => 'diamond',
                        'size' => 20,
                        'color' => [
                            'background' => '#b45309',
                            'border' => '#fbbf24',
                            'highlight' => [
                                'background' => '#d97706',
                                'border' => '#fde68a'
                            ]
                        ],
                        'font' => [
                            'color' => '#FFFFFF',
                            'size' => 11,
                            'bold' => true
                        ],
                        'meta' => [
                            'type' => 'course',
                            'name' => $courseName
                        ]
                    ];
                    $nodeTracker[$courseNodeId] = true;
                }

                $edges[] = [
                    'from' => $docNodeId,
                    'to' => $courseNodeId,
                    'label' => 'program',
                    'color' => ['color' => '#fbbf24', 'highlight' => '#d97706'],
                    'arrows' => 'to'
                ];
            }

            // 4. Author Nodes (split if multiple authors)
            if (!empty($doc->author)) {
                $rawAuthors = preg_split('/[,;\/]|(?:\band\b)/i', $doc->author);
                foreach ($rawAuthors as $authorName) {
                    $cleanAuthor = trim($authorName);
                    if (strlen($cleanAuthor) < 3) {
                        continue;
                    }

                    $authorNodeId = 'author_' . md5(strtolower($cleanAuthor));

                    if (!isset($nodeTracker[$authorNodeId])) {
                        $nodes[] = [
                            'id' => $authorNodeId,
                            'label' => $cleanAuthor,
                            'group' => 'author',
                            'shape' => 'dot',
                            'size' => 14,
                            'color' => [
                                'background' => '#047857',
                                'border' => '#34d399',
                                'highlight' => [
                                    'background' => '#059669',
                                    'border' => '#6ee7b7'
                                ]
                            ],
                            'font' => [
                                'color' => '#064e3b',
                                'size' => 11,
                                'bold' => true
                            ],
                            'meta' => [
                                'type' => 'author',
                                'name' => $cleanAuthor
                            ]
                        ];
                        $nodeTracker[$authorNodeId] = true;
                    }

                    $edges[] = [
                        'from' => $authorNodeId,
                        'to' => $docNodeId,
                        'label' => 'authored',
                        'color' => ['color' => '#34d399', 'highlight' => '#059669'],
                        'arrows' => 'to'
                    ];
                }
            }
        }

        return response()->json([
            'nodes' => $nodes,
            'edges' => $edges,
            'summary' => [
                'total_theses' => $documents->count(),
                'total_nodes' => count($nodes),
                'total_connections' => count($edges),
            ]
        ]);
    }
}
