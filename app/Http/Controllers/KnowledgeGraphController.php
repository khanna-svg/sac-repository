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
     * Focuses on:
     * - Theses
     * - Research Concepts & Topics
     * - Methodology & Research Design
     * - Tech Stack & Tools Used
     */
    public function data(Request $request): JsonResponse
    {
        $documents = Document::where('status', 'approved')
            ->orWhereNull('status')
            ->get();

        $nodes = [];
        $edges = [];
        $nodeTracker = [];
        
        // Track connected theses for non-thesis nodes so clicking them lists associated projects
        $conceptTheses = [];
        $methodTheses = [];
        $techTheses = [];

        // Pre-compute metadata and relationships
        $docMetaMap = [];
        foreach ($documents as $doc) {
            $meta = $this->extractGraphMetadata($doc);
            $docMetaMap[$doc->id] = $meta;

            $docSummary = [
                'id' => $doc->id,
                'title' => $doc->title,
                'author' => $doc->author ?? 'SAC Researchers',
                'view_url' => '/documents/' . $doc->id,
            ];

            foreach ($meta['concepts'] as $c) {
                $conceptTheses[$c][] = $docSummary;
            }
            foreach ($meta['methodologies'] as $m) {
                $methodTheses[$m][] = $docSummary;
            }
            foreach ($meta['tech_stack'] as $t) {
                $techTheses[$t][] = $docSummary;
            }
        }

        foreach ($documents as $doc) {
            $docNodeId = 'doc_' . $doc->id;
            $meta = $docMetaMap[$doc->id];

            // 1. Thesis Document Node
            if (!isset($nodeTracker[$docNodeId])) {
                $shortTitle = strlen($doc->title) > 36
                    ? substr($doc->title, 0, 33) . '...'
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
                    'meta' => [
                        'type' => 'thesis',
                        'document_id' => $doc->id,
                        'full_title' => $doc->title,
                        'author' => $doc->author ?? 'SAC Researchers',
                        'abstract' => $doc->abstract ?? 'No abstract provided.',
                        'concepts' => $meta['concepts'],
                        'methodologies' => $meta['methodologies'],
                        'tech_stack' => $meta['tech_stack'],
                        'view_url' => '/documents/' . $doc->id,
                        'pdf_url' => '/backend/documents/' . $doc->id . '/view'
                    ]
                ];
                $nodeTracker[$docNodeId] = true;
            }

            // 2. Concepts & Research Topics Nodes
            foreach ($meta['concepts'] as $conceptName) {
                $conceptNodeId = 'concept_' . md5(strtolower($conceptName));

                if (!isset($nodeTracker[$conceptNodeId])) {
                    $nodes[] = [
                        'id' => $conceptNodeId,
                        'label' => $conceptName,
                        'group' => 'concept',
                        'shape' => 'ellipse',
                        'color' => [
                            'background' => '#7c3aed',
                            'border' => '#c084fc',
                            'highlight' => [
                                'background' => '#6d28d9',
                                'border' => '#d8b4fe'
                            ]
                        ],
                        'font' => [
                            'color' => '#FFFFFF',
                            'size' => 11,
                            'bold' => true
                        ],
                        'meta' => [
                            'type' => 'concept',
                            'name' => $conceptName,
                            'theses' => $conceptTheses[$conceptName] ?? []
                        ]
                    ];
                    $nodeTracker[$conceptNodeId] = true;
                }

                $edges[] = [
                    'from' => $docNodeId,
                    'to' => $conceptNodeId,
                    'label' => 'concept',
                    'color' => ['color' => '#c084fc', 'highlight' => '#7c3aed'],
                    'arrows' => 'to'
                ];
            }

            // 3. Methodology & Research Design Nodes
            foreach ($meta['methodologies'] as $methodName) {
                $methodNodeId = 'method_' . md5(strtolower($methodName));

                if (!isset($nodeTracker[$methodNodeId])) {
                    $nodes[] = [
                        'id' => $methodNodeId,
                        'label' => $methodName,
                        'group' => 'methodology',
                        'shape' => 'triangle',
                        'size' => 18,
                        'color' => [
                            'background' => '#0891b2',
                            'border' => '#67e8f9',
                            'highlight' => [
                                'background' => '#0e7490',
                                'border' => '#a5f3fc'
                            ]
                        ],
                        'font' => [
                            'color' => '#FFFFFF',
                            'size' => 11,
                            'bold' => true
                        ],
                        'meta' => [
                            'type' => 'methodology',
                            'name' => $methodName,
                            'theses' => $methodTheses[$methodName] ?? []
                        ]
                    ];
                    $nodeTracker[$methodNodeId] = true;
                }

                $edges[] = [
                    'from' => $docNodeId,
                    'to' => $methodNodeId,
                    'label' => 'method',
                    'color' => ['color' => '#67e8f9', 'highlight' => '#0891b2'],
                    'arrows' => 'to'
                ];
            }

            // 4. Tech Stack & Tools Used Nodes
            foreach ($meta['tech_stack'] as $techName) {
                $techNodeId = 'tech_' . md5(strtolower($techName));

                if (!isset($nodeTracker[$techNodeId])) {
                    $nodes[] = [
                        'id' => $techNodeId,
                        'label' => $techName,
                        'group' => 'tech_stack',
                        'shape' => 'hexagon',
                        'size' => 20,
                        'color' => [
                            'background' => '#059669',
                            'border' => '#34d399',
                            'highlight' => [
                                'background' => '#047857',
                                'border' => '#6ee7b7'
                            ]
                        ],
                        'font' => [
                            'color' => '#FFFFFF',
                            'size' => 11,
                            'bold' => true
                        ],
                        'meta' => [
                            'type' => 'tech_stack',
                            'name' => $techName,
                            'theses' => $techTheses[$techName] ?? []
                        ]
                    ];
                    $nodeTracker[$techNodeId] = true;
                }

                $edges[] = [
                    'from' => $docNodeId,
                    'to' => $techNodeId,
                    'label' => 'tech stack',
                    'color' => ['color' => '#34d399', 'highlight' => '#059669'],
                    'arrows' => 'to'
                ];
            }
        }

        return response()->json([
            'nodes' => $nodes,
            'edges' => $edges,
            'summary' => [
                'total_theses' => $documents->count(),
                'total_nodes' => count($nodes),
                'total_connections' => count($edges),
                'total_concepts' => count($conceptTheses),
                'total_methodologies' => count($methodTheses),
                'total_tech_stacks' => count($techTheses),
            ]
        ]);
    }

    /**
     * Intelligent extractor that determines Concepts, Methodology, and Tech Stack for any thesis.
     */
    protected function extractGraphMetadata(Document $doc): array
    {
        $title = (string) $doc->title;
        $abstract = (string) $doc->abstract;
        $combined = mb_strtolower($title . ' ' . $abstract);
        $course = strtolower((string) $doc->course_code);
        $dept = strtolower((string) $doc->department);

        $concepts = [];
        $methodologies = [];
        $techStack = [];

        // 1. Methodology
        if (!empty($doc->methodology)) {
            $methodologies[] = trim($doc->methodology);
        } else {
            if (preg_match('/(in-vitro|extract|antibacterial|inhibitory|laboratory assay)/i', $combined)) {
                $methodologies[] = 'Experimental Laboratory Research';
            }
            if (preg_match('/(hedonic|sensory evaluation|acceptability rating|taste test)/i', $combined)) {
                $methodologies[] = 'Sensory Evaluation (Hedonic Scale)';
            }
            if (preg_match('/(prototype|prototyping|engineering design|automated umbrella|vending machine|rental system|nutriscale|monitoring through)/i', $combined) || in_array($course, ['bscpe', 'cpe'])) {
                $methodologies[] = 'Prototyping & Engineering Design';
            }
            if (in_array($course, ['bsce', 'ce']) || preg_match('/\b(structural design|storey|building design|slab|foundation|embankment)\b/i', $combined)) {
                $methodologies[] = 'Structural Analysis & Design Simulation';
            }
            if (preg_match('/(correlational|relationship between|alignment and)/i', $combined)) {
                $methodologies[] = 'Descriptive-Correlational Research';
            }
            if (preg_match('/(survey|questionnaire|descriptive-quantitative|level of awareness|level of knowledge|perceived stress|attitudes towards|competence in|spelling proficiency|discourse competence|common problems|common violations|accidents in)/i', $combined)) {
                $methodologies[] = 'Descriptive-Survey Research';
            }
            if (empty($methodologies)) {
                $methodologies[] = 'Descriptive-Quantitative Research';
            }
        }

        // 2. Tech Stack & Tools
        if (preg_match('/\b(arduino|atmega|uno r3)\b/i', $combined) || preg_match('/automated umbrella/i', $combined)) {
            $techStack[] = 'Arduino Uno / Microcontroller';
        }
        if (preg_match('/\b(iot|internet of things|telemetry|cloud monitoring)\b/i', $combined)) {
            $techStack[] = 'IoT & Cloud Telemetry';
        }
        if (preg_match('/\b(solar|photovoltaic|renewable energy|battery storage)\b/i', $combined)) {
            $techStack[] = 'Solar Power & Energy Management';
        }
        if (preg_match('/\b(rfid|radio frequency identification|id cards?)\b/i', $combined)) {
            $techStack[] = 'RFID User Authentication';
        }
        if (preg_match('/\b(sensors?|moisture|temperature|ph sensor|ultrasonic|load cell|weight sensor)\b/i', $combined) || preg_match('/(nutriscale|fish tank|umbrella)/i', $combined)) {
            $techStack[] = 'Embedded Sensors & Transducers';
        }
        if (preg_match('/\b(vending|dispens|coin acceptor|solenoid|stepper motor|dc motor)\b/i', $combined)) {
            $techStack[] = 'Electromechanical Actuators';
        }
        if (in_array($course, ['bsce', 'ce']) || preg_match('/\b(structural design|storey|building|slab|foundation)\b/i', $combined)) {
            $techStack[] = 'AutoCAD / Architectural Drafting';
            $techStack[] = 'Structural Analysis & Design Tools';
        }
        if (preg_match('/\b(microsoft|word|excel|powerpoint|office applications)\b/i', $combined)) {
            $techStack[] = 'Microsoft Office Suite';
        }
        if (preg_match('/\b(website|web portal|dashboard|user interface|cloud platform)\b/i', $combined)) {
            $techStack[] = 'Web Dashboard & Interfaces';
        }
        if (in_array($dept, ['bused', 'cjed', 'dte']) || preg_match('/\b(spss|statistical|questionnaire|correlation|percentage|mean|anova|t-test)\b/i', $combined)) {
            $techStack[] = 'Statistical Analysis (SPSS)';
        }
        if (preg_match('/(extract|disk diffusion|zone of inhibition|culture media|rotary evaporator|incubator|phytochemical)/i', $combined)) {
            $techStack[] = 'Laboratory Assay Instruments';
        }
        if (preg_match('/(standardized test|diagnostic test|rubric|assessment tool|hedonic scale)/i', $combined)) {
            $techStack[] = 'Assessment Tools & Evaluation Scales';
        }

        // 3. Concepts
        if (!empty($doc->keywords)) {
            $rawKw = is_array($doc->keywords) ? $doc->keywords : preg_split('/[,;]/', (string) $doc->keywords);
            foreach ($rawKw as $k) {
                if (strlen(trim($k)) > 2) $concepts[] = ucwords(trim($k));
            }
        }
        if (preg_match('/(financial literacy|financial management|budgeting|financial decision)/i', $combined)) {
            $concepts[] = 'Financial Literacy & Management';
        }
        if (preg_match('/(scholarship|college grantees)/i', $combined)) {
            $concepts[] = 'Scholarship & Educational Assistance';
        }
        if (preg_match('/(accounting information system|internship|job placement|employment|career growth|work alignment)/i', $combined)) {
            $concepts[] = 'Career Alignment & Employability';
        }
        if (preg_match('/(food innovation|tortilla chips|sensory|ube|kamote|product development)/i', $combined)) {
            $concepts[] = 'Food Product Innovation';
        }
        if (preg_match('/(academic stress|stress level|coping mechanism)/i', $combined)) {
            $concepts[] = 'Academic Stress & Coping Strategies';
        }
        if (preg_match('/(anti-bullying|bullying act|republic act 10627)/i', $combined)) {
            $concepts[] = 'Campus Safety & Anti-Bullying Policies';
        }
        if (preg_match('/(cybercrime|republic act 10175|digital security|cybersecurity)/i', $combined)) {
            $concepts[] = 'Cybersecurity & Cybercrime Law';
        }
        if (preg_match('/(social media|facebook|tiktok|content engagement)/i', $combined)) {
            $concepts[] = 'Social Media Engagement';
        }
        if (preg_match('/(motorcycle|traffic|road accident|traffic violation)/i', $combined)) {
            $concepts[] = 'Road Safety & Traffic Enforcement';
        }
        if (preg_match('/(student boarders|boarding house|housing problems)/i', $combined)) {
            $concepts[] = 'Student Living Conditions & Welfare';
        }
        if (preg_match('/(family income|family expenditures|household budget)/i', $combined)) {
            $concepts[] = 'Family Economics & Expenditures';
        }
        if (preg_match('/(microsoft applications|computer literacy|software knowledge)/i', $combined)) {
            $concepts[] = 'Digital & Software Literacy';
        }
        if (preg_match('/(research of 4th year|conducting research|research challenges)/i', $combined)) {
            $concepts[] = 'Undergraduate Research Competence';
        }
        if (preg_match('/(journal writing|reflective writing|pre-service)/i', $combined)) {
            $concepts[] = 'Reflective Journal Writing';
        }
        if (preg_match('/(project-based learning|pbl|instructional strategy)/i', $combined)) {
            $concepts[] = 'Project-Based Learning (PBL)';
        }
        if (preg_match('/(carica papaya|clitoria ternatea|antibacterial|bacterial growth|inhibition zone)/i', $combined)) {
            $concepts[] = 'Natural Antibacterial Agents';
        }
        if (preg_match('/(grammar|basic english|language skill)/i', $combined)) {
            $concepts[] = 'English Grammar Competence';
        }
        if (preg_match('/(spelling proficiency|spelling skill)/i', $combined)) {
            $concepts[] = 'English Spelling Proficiency';
        }
        if (preg_match('/(discourse competence|coherent text)/i', $combined)) {
            $concepts[] = 'Discourse & Communication Skills';
        }
        if (preg_match('/(mathematics anxiety|math performance)/i', $combined)) {
            $concepts[] = 'Mathematics Anxiety & Performance';
        }
        if (preg_match('/(smart campus|automated umbrella|rental system|sharing economy)/i', $combined)) {
            $concepts[] = 'Smart Campus Automation';
        }
        if (preg_match('/(bond paper|vending machine|automated dispensing)/i', $combined)) {
            $concepts[] = 'Automated Vending Systems';
        }
        if (preg_match('/(fish tank|aquaculture|water quality)/i', $combined)) {
            $concepts[] = 'Aquaculture & Water Quality Monitoring';
        }
        if (preg_match('/(plastic water bottle|reverse vending|recycling|reward)/i', $combined)) {
            $concepts[] = 'Solid Waste Recycling & Incentive Systems';
        }
        if (preg_match('/(nutriscale|nutrition|dietary intake|nutritional status)/i', $combined)) {
            $concepts[] = 'Nutritional Measurement & Health Tracking';
        }
        if (preg_match('/(building|hotel|apartment|residential|structural|slab|embankment)/i', $combined)) {
            $concepts[] = 'Structural Engineering & Infrastructure';
        }

        return [
            'concepts' => array_values(array_unique(array_slice($concepts, 0, 3))),
            'methodologies' => array_values(array_unique(array_slice($methodologies, 0, 2))),
            'tech_stack' => array_values(array_unique(array_slice($techStack, 0, 3))),
        ];
    }
}
