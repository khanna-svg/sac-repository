<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessThesisPdf;
use App\Models\Document;
use App\Models\ThesisNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DocumentController extends Controller
{

    public function viewPdf(Request $request, Document $document)
    {
        $baseUrl = rtrim(
            (string) config(
                'services.supabase.url',
                env('SUPABASE_URL')
            ),
            '/'
        );
        $key = (string) config(
            'services.supabase.service_role_key',
            env('SUPABASE_SERVICE_ROLE_KEY')
        );
        $bucket = (string) config(
            'services.supabase.bucket',
            env('SUPABASE_STORAGE_BUCKET', 'thesis')
        );
        if ($baseUrl === '' || $key === '' || $bucket === '') {
            abort(500, 'Supabase Storage is not configured.');
        }
        $path = ltrim(
            trim((string) $document->file_path),
            '/'
        );
        if ($path === '') {
            abort(404, 'Document file path is empty.');
        }
        if (str_starts_with($path, $bucket . '/')) {
            $path = substr(
                $path,
                strlen($bucket) + 1
            );
        }
        $encodedBucket = rawurlencode($bucket);
        $encodedPath = collect(
            explode('/', $path)
        )
            ->map(
                fn($part) => rawurlencode($part)
            )
            ->implode('/');
        $signUrl =
            "{$baseUrl}/storage/v1/object/sign/{$encodedBucket}/{$encodedPath}";
        $isDownload = $request->has('download');
        $safeFilename = preg_replace('/[^A-Za-z0-9_\-\. ]/', '', $document->title);
        $safeFilename = trim($safeFilename) ?: 'Thesis_Document';
        if (!str_ends_with(strtolower($safeFilename), '.pdf')) {
            $safeFilename .= '.pdf';
        }
        $payload = [
            'expiresIn' => 3600,
        ];
        if ($isDownload) {
            $payload['download'] = $safeFilename;
        }
        try {
            $response = Http::withoutVerifying()
                ->timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$key}",
                    'apikey' => $key,
                    'Content-Type' => 'application/json',
                ])
                ->post($signUrl, $payload);
            if (!$response->successful()) {
                Log::error('Supabase signed PDF failed', [
                    'document_id' => $document->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                abort(404, 'PDF could not be found in Supabase Storage.');
            }
            $data = $response->json();
            $relativeSignedUrl = $data['signedURL'] ?? $data['signedUrl'] ?? null;
            if (!$relativeSignedUrl) {
                abort(500, 'Supabase did not return a signed URL.');
            }
            if (str_starts_with($relativeSignedUrl, 'http://') || str_starts_with($relativeSignedUrl, 'https://')) {
                $signedUrl = $relativeSignedUrl;
            } elseif (str_starts_with($relativeSignedUrl, '/storage/v1/')) {
                $signedUrl = $baseUrl . $relativeSignedUrl;
            } elseif (str_starts_with($relativeSignedUrl, '/object/')) {
                $signedUrl = $baseUrl . '/storage/v1' . $relativeSignedUrl;
            } else {
                $signedUrl = $baseUrl . '/storage/v1/' . ltrim($relativeSignedUrl, '/');
            }
            if ($isDownload && !str_contains($signedUrl, 'download=')) {
                $signedUrl .= (str_contains($signedUrl, '?') ? '&' : '?') . 'download=' . urlencode($safeFilename);
            }
            return redirect()->away($signedUrl);
        } catch (\Throwable $e) {
            Log::error('PDF view/download exception', [
                'document_id' => $document->id,
                'message' => $e->getMessage(),
            ]);
            abort(500, 'Unable to open the thesis PDF.');
        }
    }

    public function getSignedUrl(Document $document)
    {
        $baseUrl = rtrim((string) config('services.supabase.url', env('SUPABASE_URL')), '/');
        $key = (string) config('services.supabase.service_role_key', env('SUPABASE_SERVICE_ROLE_KEY'));
        $bucket = (string) config('services.supabase.bucket', env('SUPABASE_STORAGE_BUCKET', 'thesis'));

        $path = ltrim(trim((string) $document->file_path), '/');
        if (str_starts_with($path, $bucket . '/')) {
            $path = substr($path, strlen($bucket) + 1);
        }

        $encodedBucket = rawurlencode($bucket);
        $encodedPath = collect(explode('/', $path))->map(fn($part) => rawurlencode($part))->implode('/');
        $signUrl = "{$baseUrl}/storage/v1/object/sign/{$encodedBucket}/{$encodedPath}";

        try {
            $response = Http::withoutVerifying()
                ->timeout(20)
                ->withHeaders([
                    'Authorization' => "Bearer {$key}",
                    'apikey' => $key,
                    'Content-Type' => 'application/json',
                ])
                ->post($signUrl, ['expiresIn' => 3600]);

            if (!$response->successful()) {
                return response()->json(['error' => 'Could not sign PDF URL'], 404);
            }

            $data = $response->json();
            $relative = $data['signedURL'] ?? null;
            if (!$relative) {
                return response()->json(['error' => 'No signed URL returned'], 500);
            }

            if (str_starts_with($relative, 'http://') || str_starts_with($relative, 'https://')) {
                $signedUrl = $relative;
            } elseif (str_starts_with($relative, '/storage/v1/')) {
                $signedUrl = $baseUrl . $relative;
            } elseif (str_starts_with($relative, '/object/')) {
                $signedUrl = $baseUrl . '/storage/v1' . $relative;
            } else {
                $signedUrl = $baseUrl . '/storage/v1/' . ltrim($relative, '/');
            }

            return response()->json(['url' => $signedUrl]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    protected function applyDepartmentFilter($query, string $department): void
    {
        $dept = strtolower(trim($department));
        if ($dept === '' || $dept === 'all') {
            return;
        }

        $query->where(function ($q) use ($dept) {
            if (in_array($dept, ['bused', 'bus.ed', 'bus_ed', 'business', 'hospitality', 'bsa', 'bsais', 'ba', 'bshm'])) {
                $q->whereIn(DB::raw('LOWER(department)'), ['bused', 'bus.ed', 'bus_ed', 'business', 'hospitality'])
                  ->orWhereIn(DB::raw('LOWER(course_code)'), ['bsa', 'bsais', 'ba', 'bshm'])
                  ->orWhereRaw('LOWER(department) LIKE ?', ['%business%'])
                  ->orWhereRaw('LOWER(department) LIKE ?', ['%accountan%'])
                  ->orWhereRaw('LOWER(department) LIKE ?', ['%hospitality%']);
            } elseif (in_array($dept, ['cjed', 'criminology', 'bscrim', 'bsc'])) {
                $q->whereIn(DB::raw('LOWER(department)'), ['cjed', 'criminology'])
                  ->orWhereIn(DB::raw('LOWER(course_code)'), ['bscrim', 'bsc'])
                  ->orWhereRaw('LOWER(department) LIKE ?', ['%criminal justice%'])
                  ->orWhereRaw('LOWER(department) LIKE ?', ['%criminology%']);
            } elseif (in_array($dept, ['dte', 'education', 'bsed', 'bsed_english', 'bsed_math', 'bsed_science', 'beed'])) {
                $q->whereIn(DB::raw('LOWER(department)'), ['dte', 'education'])
                  ->orWhereIn(DB::raw('LOWER(course_code)'), ['bsed', 'bsed_english', 'bsed_math', 'bsed_science', 'beed'])
                  ->orWhereRaw('LOWER(course_code) LIKE ?', ['%bsed%'])
                  ->orWhereRaw('LOWER(department) LIKE ?', ['%teacher education%'])
                  ->orWhereRaw('LOWER(department) LIKE ?', ['%education%']);
            } elseif (in_array($dept, ['eng', 'engineering', 'marine', 'bsce', 'bscpe', 'bsmare'])) {
                $q->whereIn(DB::raw('LOWER(department)'), ['eng', 'engineering', 'marine'])
                  ->orWhereIn(DB::raw('LOWER(course_code)'), ['bsce', 'bscpe', 'bsmare'])
                  ->orWhereRaw('LOWER(department) LIKE ?', ['%engineering%'])
                  ->orWhereRaw('LOWER(department) LIKE ?', ['%marine%']);
            } elseif (in_array($dept, ['itd', 'it', 'computer', 'bsit'])) {
                $q->whereIn(DB::raw('LOWER(department)'), ['itd', 'it'])
                  ->orWhereIn(DB::raw('LOWER(course_code)'), ['bsit'])
                  ->orWhereRaw('LOWER(department) LIKE ?', ['%information technology%'])
                  ->orWhereRaw('LOWER(department) LIKE ?', ['%computer%']);
            } elseif (in_array($dept, ['lad', 'liberal_arts', 'arts', 'ab_philo', 'ab_phil', 'philosophy'])) {
                $q->whereIn(DB::raw('LOWER(department)'), ['lad', 'liberal_arts', 'arts'])
                  ->orWhereIn(DB::raw('LOWER(course_code)'), ['ab_philo', 'ab_phil'])
                  ->orWhereRaw('LOWER(department) LIKE ?', ['%liberal arts%'])
                  ->orWhereRaw('LOWER(department) LIKE ?', ['%philosophy%']);
            } elseif (in_array($dept, ['nursing', 'bsn'])) {
                $q->whereIn(DB::raw('LOWER(department)'), ['nursing'])
                  ->orWhereIn(DB::raw('LOWER(course_code)'), ['bsn'])
                  ->orWhereRaw('LOWER(department) LIKE ?', ['%nursing%']);
            } else {
                $q->whereRaw('LOWER(department) = ?', [$dept])
                  ->orWhereRaw('LOWER(course_code) = ?', [$dept]);
            }
        });
    }

    public function index(Request $request)
    {
        try {
            $search = trim((string) $request->input('search', ''));
            $department = trim((string) $request->input('department', ''));
            $sort = trim((string) $request->input('sort', 'latest'));

            $approvedScope = fn($q) => $q->where(fn($sub) => $sub->where('status', 'approved')->orWhereNull('status'));

            if ($search === '') {
                $query = Document::query()->where($approvedScope);

                if ($department !== '' && $department !== 'all') {
                    $this->applyDepartmentFilter($query, $department);
                }

                if ($sort === 'oldest') {
                    $query->orderByRaw('COALESCE(publication_date, created_at::date) asc')->orderBy('id', 'asc');
                } elseif ($sort === 'title_asc') {
                    $query->orderBy('title', 'asc');
                } elseif ($sort === 'title_desc') {
                    $query->orderBy('title', 'desc');
                } else {
                    $query->orderByRaw('COALESCE(publication_date, created_at::date) desc')->latest();
                }

                return response()->json($query->get());
            }

            $searchTerm = '%' . strtolower($search) . '%';
            $keywordDocs = collect([]);
            $semanticDocIds = [];
            $similarityMap = [];

            // Tokenize words (ignoring short noise particles)
            $tokens = array_filter(
                preg_split('/[\s,\.\-_]+/', strtolower($search)),
                fn($w) => strlen(trim($w)) >= 3
            );

            // 1. Hybrid Multi-token Keyword Query
            $keywordQuery = Document::query()->where($approvedScope);
            $keywordQuery->where(function ($q) use ($searchTerm, $tokens) {
                // Exact phrase match
                $q->whereRaw('LOWER(title) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(author) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(department) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(course_code) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(abstract) LIKE ?', [$searchTerm]);

                // Individual word token matches
                foreach ($tokens as $token) {
                    $tokenPattern = '%' . $token . '%';
                    $q->orWhereRaw('LOWER(title) LIKE ?', [$tokenPattern])
                      ->orWhereRaw('LOWER(abstract) LIKE ?', [$tokenPattern]);
                }
            });

            if ($department !== '' && $department !== 'all') {
                $this->applyDepartmentFilter($keywordQuery, $department);
            }

            $keywordDocs = $keywordQuery->get();
            $searchLower = strtolower($search);

            foreach ($keywordDocs as $doc) {
                $titleLower = strtolower($doc->title);
                $authorLower = strtolower($doc->author);
                $abstractLower = strtolower($doc->abstract ?? '');

                if (str_contains($titleLower, $searchLower) || str_contains($authorLower, $searchLower)) {
                    $similarityMap[$doc->id] = 98;
                } elseif (str_contains($abstractLower, $searchLower)) {
                    $similarityMap[$doc->id] = 90;
                } else {
                    $titleTokensMatched = 0;
                    $abstractTokensMatched = 0;
                    foreach ($tokens as $t) {
                        if (str_contains($titleLower, $t)) $titleTokensMatched++;
                        if (str_contains($abstractLower, $t)) $abstractTokensMatched++;
                    }

                    if ($titleTokensMatched > 0) {
                        $ratio = $titleTokensMatched / max(1, count($tokens));
                        $similarityMap[$doc->id] = (int) round(82 + ($ratio * 15)); // 82% to 97%
                    } else {
                        $ratio = $abstractTokensMatched / max(1, count($tokens));
                        $similarityMap[$doc->id] = (int) round(60 + ($ratio * 15)); // 60% to 75%
                    }
                }
            }

            // 2. Semantic Search via Gemini + Supabase pgvector
            try {
                $geminiService = app(\App\Services\GeminiService::class);
                $queryEmbedding = $geminiService->generateEmbedding($search);
                if (!empty($queryEmbedding)) {
                    $embeddingString = '[' . implode(',', $queryEmbedding) . ']';

                    $similarChunks = DB::select("
                        SELECT
                            dc.document_id,
                            MIN(dc.embedding OPERATOR(extensions.<=>) ?::extensions.vector) AS distance
                        FROM document_chunks dc
                        INNER JOIN documents d ON d.id = dc.document_id
                        WHERE dc.embedding IS NOT NULL
                          AND (d.status = 'approved' OR d.status IS NULL)
                        GROUP BY dc.document_id
                        ORDER BY distance ASC
                        LIMIT 30
                    ", [$embeddingString]);

                    foreach ($similarChunks as $chunk) {
                        $docId = (int) $chunk->document_id;
                        $distance = (float) $chunk->distance;

                        // Include semantic matches up to distance 0.44
                        if ($distance > 0.44) {
                            continue;
                        }

                        $normalized = 1 - (($distance - 0.12) / 0.36);
                        $score = max(25, min(99, (int) round($normalized * 100)));

                        if (isset($similarityMap[$docId])) {
                            // Boost if matched both keywords and semantic vector
                            $similarityMap[$docId] = min(99, max($similarityMap[$docId], $score) + 5);
                        } else {
                            $similarityMap[$docId] = $score;
                            $semanticDocIds[] = $docId;
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Hybrid Search Semantic phase fallback: ' . $e->getMessage());
            }

            if (!empty($semanticDocIds)) {
                $semanticQuery = Document::whereIn('id', $semanticDocIds)->where($approvedScope);
                if ($department !== '' && $department !== 'all') {
                    $this->applyDepartmentFilter($semanticQuery, $department);
                }
                $semanticDocs = $semanticQuery->get();
                $allResults = $keywordDocs->concat($semanticDocs)->unique('id');
            } else {
                $allResults = $keywordDocs;
            }

            $rankedDocs = $allResults->map(function ($doc) use ($similarityMap) {
                $doc->similarity_score = $similarityMap[$doc->id] ?? null;
                return $doc;
            });

            if ($sort === 'oldest') {
                $rankedDocs = $rankedDocs->sortBy('id');
            } elseif ($sort === 'title_asc') {
                $rankedDocs = $rankedDocs->sortBy('title');
            } elseif ($sort === 'title_desc') {
                $rankedDocs = $rankedDocs->sortByDesc('title');
            } else {
                $rankedDocs = $rankedDocs->sortByDesc('similarity_score');
            }

            return response()->json($rankedDocs->values());
        } catch (\Throwable $e) {
            Log::error('DocumentController index error: ' . $e->getMessage());
            $approvedScope = fn($q) => $q->where(fn($sub) => $sub->where('status', 'approved')->orWhereNull('status'));
            return response()->json(Document::where($approvedScope)->latest()->get());
        }
    }

    public function show($id)
    {
        try {
            $document = Document::with([
                'chunks' => function ($query) {
                    $query->select('id', 'document_id', 'page_number', 'chunk_text')
                        ->orderBy('page_number', 'asc');
                }
            ])->findOrFail($id);

            // Access check: If unapproved, allow only admin or the student submitter
            $userRole = session('sac_user_role');
            $userEmail = strtolower((string) session('sac_user_email'));
            if ($document->status !== 'approved' && $userRole !== 'admin' && strtolower((string) $document->submitted_by_email) !== $userEmail) {
                abort(403, 'This thesis manuscript is currently undergoing review and is not publicly accessible.');
            }

            return view('document_detail', [
                'document' => $document,
            ]);
        } catch (\Throwable $e) {
            Log::error('Document detail error: ' . $e->getMessage());
            abort(404, 'Thesis document not found or inaccessible.');
        }
    }

    public function createUploadUrl(Request $request)
    {
        if (
            $request->session()->get(
                'sac_user_role'
            ) !== 'admin'
        ) {

            return response()->json(
                [
                    'error' =>
                    true,

                    'message' =>
                    'Unauthorized. Admin access required.',
                ],
                403
            );
        }

        $filename =
            (string) $request->input(
                'filename'
            );

        if ($filename === '') {

            return response()->json(
                [
                    'error' =>
                    true,

                    'message' =>
                    'No filename was provided.',
                ],
                400
            );
        }

        $baseUrl = rtrim(
            (string) config(
                'services.supabase.url',
                env('SUPABASE_URL')
            ),
            '/'
        );

        $key = (string) config(
            'services.supabase.service_role_key',
            env('SUPABASE_SERVICE_ROLE_KEY')
        );

        $bucket = (string) config(
            'services.supabase.bucket',
            env(
                'SUPABASE_STORAGE_BUCKET',
                'thesis'
            )
        );

        if (
            $baseUrl === '' ||
            $key === '' ||
            $bucket === ''
        ) {

            return response()->json(
                [
                    'error' =>
                    true,

                    'message' =>
                    'Supabase Storage is not configured.',
                ],
                500
            );
        }

        $extension =
            strtolower(
                pathinfo(
                    $filename,
                    PATHINFO_EXTENSION
                )
            );

        if ($extension !== 'pdf') {

            return response()->json(
                [
                    'error' =>
                    true,

                    'message' =>
                    'Only PDF files are allowed.',
                ],
                422
            );
        }

        $path =
            'documents/' .
            Str::uuid() .
            '.pdf';

        try {

            $encodedBucket =
                rawurlencode(
                    $bucket
                );

            $encodedPath =
                collect(
                    explode(
                        '/',
                        $path
                    )
                )
                ->map(
                    fn($part) =>
                    rawurlencode($part)
                )
                ->implode('/');

            $url =
                "{$baseUrl}/storage/v1/object/upload/sign/{$encodedBucket}/{$encodedPath}";

            $response =
                Http::withoutVerifying()
                ->timeout(30)
                ->withHeaders([
                    'Authorization' =>
                    "Bearer {$key}",

                    'apikey' =>
                    $key,

                    'Content-Type' =>
                    'application/json',
                ])
                ->post(
                    $url,
                    []
                );

            if (!$response->successful()) {

                Log::error(
                    'Supabase upload URL failed',
                    [
                        'status' =>
                        $response->status(),

                        'body' =>
                        $response->body(),
                    ]
                );

                return response()->json(
                    [
                        'error' =>
                        true,

                        'message' =>
                        'Supabase could not create the upload URL.',
                    ],
                    500
                );
            }

            $data =
                $response->json();

            $relativeUrl =
                $data['url']
                ?? $data['signedURL']
                ?? null;

            if (!$relativeUrl) {

                return response()->json(
                    [
                        'error' =>
                        true,

                        'message' =>
                        'Supabase did not return an upload URL.',
                    ],
                    500
                );
            }

            $signedUrl =
                str_starts_with(
                    $relativeUrl,
                    'http://'
                )
                ||
                str_starts_with(
                    $relativeUrl,
                    'https://'
                )
                ? $relativeUrl
                : $baseUrl .
                $relativeUrl;

            $parsedUrl =
                parse_url(
                    $signedUrl
                );

            $queryParams = [];

            parse_str(
                $parsedUrl['query'] ?? '',
                $queryParams
            );

            $token =
                $queryParams['token']
                ?? null;

            if (!$token) {

                return response()->json(
                    [
                        'error' =>
                        true,

                        'message' =>
                        'Supabase returned an invalid upload URL.',
                    ],
                    500
                );
            }

            return response()->json(
                [
                    'error' =>
                    false,

                    'path' =>
                    $path,

                    'signedUrl' =>
                    $signedUrl,

                    'token' =>
                    $token,
                ]
            );
        } catch (\Throwable $e) {

            Log::error(
                'Create Supabase upload URL failed',
                [
                    'message' =>
                    $e->getMessage(),
                ]
            );

            return response()->json(
                [
                    'error' =>
                    true,

                    'message' =>
                    'Could not prepare the file upload.',
                ],
                500
            );
        }
    }

    public function storeFromSignedUrl(Request $request)
    {
        try {
            $submissionId = $request->input('submission_id');
            $title = trim((string) $request->input('title'));
            $author = trim((string) $request->input('author'));
            $department = trim((string) $request->input('department'));
            $courseCode = trim((string) $request->input('course_code'));
            $abstract = trim((string) $request->input('abstract'));
            $filePath = trim((string) $request->input('file_path'));
            $chunks = $request->input('chunks', []);

            if ($title === '' || $author === '' || $abstract === '' || $filePath === '') {
                return response()->json([
                    'error' => true,
                    'message' => 'Thesis information is incomplete.',
                ], 400);
            }
            if (!str_starts_with($filePath, 'documents/')) {
                return response()->json([
                    'error' => true,
                    'message' => 'Invalid thesis file path.',
                ], 400);
            }
            $rawPubDate = $request->input('publication_date');
            $publicationDate = !empty($rawPubDate) ? \Carbon\Carbon::parse($rawPubDate)->toDateString() : now()->toDateString();

            if (!empty($submissionId)) {
                $document = Document::findOrFail($submissionId);
                $document->update([
                    'title' => $title,
                    'author' => $author,
                    'department' => $department,
                    'course_code' => $courseCode,
                    'publication_date' => $publicationDate,
                    'abstract' => $abstract,
                    'file_path' => $filePath,
                    'file_url' => "/backend/documents/{$document->id}/view",
                    'status' => 'approved',
                    'admin_notes' => null,
                ]);

                // If new chunks provided, replace existing chunks
                if (is_array($chunks) && !empty($chunks)) {
                    DB::table('document_chunks')->where('document_id', $document->id)->delete();
                    $now = now();
                    $insertData = [];
                    foreach ($chunks as $chunk) {
                        $text = trim((string) ($chunk['text'] ?? ''));
                        $page = (int) ($chunk['page'] ?? 1);
                        if ($text !== '') {
                            $insertData[] = [
                                'document_id' => $document->id,
                                'page_number' => $page,
                                'chunk_text' => $text,
                                'embedding' => null,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }
                    if (!empty($insertData)) {
                        foreach (array_chunk($insertData, 100) as $batch) {
                            DB::table('document_chunks')->insert($batch);
                        }
                    }
                }

                // Dispatch approval notification to student
                if ($document->submitted_by_email) {
                    ThesisNotification::create([
                        'user_email' => $document->submitted_by_email,
                        'title' => '🎉 Congratulations your thesis was approved',
                        'message' => "Congratulations! Your thesis \"{$document->title}\" has been approved and is now officially published in the St. Anthony's College Repository.",
                        'type' => 'approved',
                        'document_id' => $document->id,
                        'is_read' => false,
                    ]);
                }
            } else {
                $document = Document::create([
                    'title' => $title,
                    'author' => $author,
                    'department' => $department,
                    'course_code' => $courseCode,
                    'publication_date' => $publicationDate,
                    'abstract' => $abstract,
                    'file_path' => $filePath,
                    'file_url' => '',
                    'status' => 'approved',
                ]);
                $document->update([
                    'file_url' => "/backend/documents/{$document->id}/view",
                ]);
                if (is_array($chunks) && !empty($chunks)) {
                    $now = now();
                    $insertData = [];
                    foreach ($chunks as $chunk) {
                        $text = trim((string) ($chunk['text'] ?? ''));
                        $page = (int) ($chunk['page'] ?? 1);
                        if ($text !== '') {
                            $insertData[] = [
                                'document_id' => $document->id,
                                'page_number' => $page,
                                'chunk_text' => $text,
                                'embedding' => null,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }
                    if (!empty($insertData)) {
                        foreach (array_chunk($insertData, 100) as $batch) {
                            DB::table('document_chunks')->insert($batch);
                        }
                    }
                } else {
                    ProcessThesisPdf::dispatchSync($document);
                }
            }

            $totalChunks = DB::table('document_chunks')
                ->where('document_id', $document->id)
                ->count();
            return response()->json([
                'error' => false,
                'message' => 'Thesis metadata and pages saved successfully.',
                'document' => $document,
                'total_chunks' => $totalChunks,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Store signed metadata failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => true,
                'message' => 'Failed to save thesis: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' =>
            'required|string|max:255',

            'author' =>
            'required|string|max:255',

            'department' =>
            'required|string|max:255',

            'course_code' =>
            'required|string|max:100',

            'abstract' =>
            'required|string',

            'file' =>
            'required|file|mimes:pdf|max:51200',
        ]);

        try {

            $file =
                $request->file('file');

            $filePath =
                'documents/' .
                Str::uuid() .
                '.pdf';

            $baseUrl = rtrim(
                (string) config(
                    'services.supabase.url',
                    env('SUPABASE_URL')
                ),
                '/'
            );

            $key = (string) config(
                'services.supabase.service_role_key',
                env('SUPABASE_SERVICE_ROLE_KEY')
            );

            $bucket = (string) config(
                'services.supabase.bucket',
                env(
                    'SUPABASE_STORAGE_BUCKET',
                    'thesis'
                )
            );

            if (
                $baseUrl === '' ||
                $key === '' ||
                $bucket === ''
            ) {

                return response()->json(
                    [
                        'error' =>
                        true,

                        'message' =>
                        'Supabase Storage is not configured.',
                    ],
                    500
                );
            }

            $fileStream =
                fopen(
                    $file->getRealPath(),
                    'r'
                );

            $response =
                Http::withoutVerifying()
                ->timeout(120)
                ->withHeaders([
                    'Authorization' =>
                    "Bearer {$key}",

                    'apikey' =>
                    $key,
                ])
                ->withBody(
                    $fileStream,
                    'application/pdf'
                )
                ->post(
                    "{$baseUrl}/storage/v1/object/{$bucket}/{$filePath}"
                );

            if (is_resource($fileStream)) {
                fclose($fileStream);
            }

            if (!$response->successful()) {

                Log::error(
                    'Supabase file upload failed',
                    [
                        'status' =>
                        $response->status(),

                        'body' =>
                        $response->body(),
                    ]
                );

                return response()->json(
                    [
                        'error' =>
                        true,

                        'message' =>
                        'Failed to store file in Supabase Storage.',
                    ],
                    500
                );
            }

            $rawPubDate = $request->input('publication_date');
            $publicationDate = !empty($rawPubDate) ? \Carbon\Carbon::parse($rawPubDate)->toDateString() : now()->toDateString();

            $document =
                Document::create([
                    'title' =>
                    $request->input(
                        'title'
                    ),

                    'author' =>
                    $request->input(
                        'author'
                    ),

                    'department' =>
                    $request->input(
                        'department'
                    ),

                    'course_code' =>
                    $request->input(
                        'course_code'
                    ),

                    'publication_date' =>
                    $publicationDate,

                    'abstract' =>
                    $request->input(
                        'abstract'
                    ),

                    'file_path' =>
                    $filePath,

                    'file_url' =>
                    '',
                ]);

            $document->update([
                'file_url' =>
                "/backend/documents/{$document->id}/view",
            ]);

            ProcessThesisPdf::dispatchSync(
                $document
            );

            return response()->json(
                [
                    'error' =>
                    false,

                    'message' =>
                    'Thesis uploaded and processed successfully.',

                    'document' =>
                    $document,
                ],
                201
            );
        } catch (\Throwable $e) {

            Log::error(
                'Upload failed',
                [
                    'message' =>
                    $e->getMessage(),

                    'trace' =>
                    $e->getTraceAsString(),
                ]
            );

            return response()->json(
                [
                    'error' =>
                    true,

                    'message' =>
                    'Upload failed: ' .
                        $e->getMessage(),
                ],
                500
            );
        }
    }

    public function generateEmbeddings(
        Request $request,
        $id
    ) {
        try {

            $document =
                Document::findOrFail($id);

            $geminiService =
                app(
                    \App\Services\GeminiService::class
                );

            $chunks =
                DB::table(
                    'document_chunks'
                )
                ->where(
                    'document_id',
                    $document->id
                )
                ->whereNull(
                    'embedding'
                )
                ->orderBy('id')
                ->limit(20)
                ->get();

            if ($chunks->isEmpty()) {

                $remaining =
                    DB::table(
                        'document_chunks'
                    )
                    ->where(
                        'document_id',
                        $document->id
                    )
                    ->whereNull(
                        'embedding'
                    )
                    ->count();

                return response()->json([
                    'error' =>
                    false,

                    'processed' =>
                    0,

                    'remaining' =>
                    $remaining,

                    'message' =>
                    'No more embeddings to generate.',
                ]);
            }

            $texts =
                $chunks
                ->pluck(
                    'chunk_text'
                )
                ->map(
                    fn($text) =>
                    trim(
                        (string) $text
                    )
                )
                ->filter()
                ->values()
                ->toArray();

            if (empty($texts)) {

                throw new \Exception(
                    'No valid chunk text was found.'
                );
            }

            $embeddings =
                $geminiService
                ->generateEmbeddings(
                    $texts
                );

            if (!is_array($embeddings)) {

                throw new \Exception(
                    'Gemini embedding service did not return an array.'
                );
            }

            if (
                count($embeddings) !==
                count($chunks)
            ) {

                throw new \Exception(
                    'Embedding count does not match chunk count.'
                );
            }

            foreach (
                $chunks as $index => $chunk
            ) {

                $embedding =
                    $embeddings[$index]
                    ?? null;

                if (
                    !is_array($embedding) ||
                    empty($embedding)
                ) {

                    throw new \Exception(
                        "Invalid embedding returned for chunk ID {$chunk->id}."
                    );
                }

                $vector =
                    '[' .
                    implode(
                        ',',
                        $embedding
                    ) .
                    ']';

                DB::table(
                    'document_chunks'
                )
                    ->where(
                        'id',
                        $chunk->id
                    )
                    ->update([
                        'embedding' =>
                        $vector,

                        'updated_at' =>
                        now(),
                    ]);
            }

            $remaining =
                DB::table(
                    'document_chunks'
                )
                ->where(
                    'document_id',
                    $document->id
                )
                ->whereNull(
                    'embedding'
                )
                ->count();

            return response()->json([
                'error' =>
                false,

                'processed' =>
                count($embeddings),

                'remaining' =>
                $remaining,

                'message' =>
                'Processed ' .
                    count($embeddings) .
                    ' chunks. ' .
                    $remaining .
                    ' remaining.',
            ]);
        } catch (\Throwable $e) {

            Log::error(
                'Embedding generation failed',
                [
                    'document_id' =>
                    $id,

                    'message' =>
                    $e->getMessage(),

                    'trace' =>
                    $e->getTraceAsString(),
                ]
            );

            return response()->json(
                [
                    'error' =>
                    true,

                    'message' =>
                    $e->getMessage(),
                ],
                500
            );
        }
    }
    public function adminList(Request $request)
    {
        try {
            $search = trim((string) $request->input('search', ''));
            $department = trim((string) $request->input('department', ''));
            $tab = trim((string) $request->input('tab', 'published'));

            $query = Document::query()->withCount('chunks');

            if ($tab === 'archived') {
                $query->where('status', 'archived');
            } else {
                $query->where(function ($q) {
                    $q->where('status', 'approved')
                        ->orWhereNull('status');
                });
            }

            if ($search !== '') {
                $searchTerm = '%' . strtolower($search) . '%';
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereRaw('LOWER(title) LIKE ?', [$searchTerm])
                        ->orWhereRaw('LOWER(author) LIKE ?', [$searchTerm])
                        ->orWhereRaw('LOWER(department) LIKE ?', [$searchTerm])
                        ->orWhereRaw('LOWER(course_code) LIKE ?', [$searchTerm]);
                });
            }

            if ($department !== '' && $department !== 'all') {
                $this->applyDepartmentFilter($query, $department);
            }

            $documents = $query->orderByRaw('COALESCE(publication_date, created_at::date) desc')->latest()->get();

            $publishedCount = Document::where(function ($q) {
                $q->where('status', 'approved')
                    ->orWhereNull('status');
            })->count();

            $archivedCount = Document::where('status', 'archived')->count();

            return response()->json([
                'error' => false,
                'theses' => $documents,
                'counts' => [
                    'published' => $publishedCount,
                    'archived' => $archivedCount,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Admin thesis list error: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Failed to fetch theses: ' . $e->getMessage()
            ], 500);
        }
    }

    public function archive($id)
    {
        try {
            $document = Document::findOrFail($id);
            $document->update(['status' => 'archived']);

            return response()->json([
                'error' => false,
                'message' => "Thesis '{$document->title}' has been archived and moved to Archived Theses."
            ]);
        } catch (\Throwable $e) {
            Log::error('Admin thesis archive error: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Failed to archive thesis: ' . $e->getMessage()
            ], 500);
        }
    }

    public function restore($id)
    {
        try {
            $document = Document::findOrFail($id);
            $document->update(['status' => 'approved']);

            return response()->json([
                'error' => false,
                'message' => "Thesis '{$document->title}' has been restored and republished."
            ]);
        } catch (\Throwable $e) {
            Log::error('Admin thesis restore error: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Failed to restore thesis: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);

            $validated = $request->validate([
                'title' => ['required', 'string', 'max:500'],
                'author' => ['required', 'string', 'max:500'],
                'department' => ['required', 'string', 'max:100'],
                'course_code' => ['required', 'string', 'max:50'],
                'publication_date' => ['nullable', 'date'],
                'abstract' => ['nullable', 'string'],
            ]);

            $document->update([
                'title' => trim($validated['title']),
                'author' => trim($validated['author']),
                'department' => strtolower(trim($validated['department'])),
                'course_code' => strtolower(trim($validated['course_code'])),
                'publication_date' => !empty($validated['publication_date']) ? \Carbon\Carbon::parse($validated['publication_date'])->toDateString() : $document->publication_date,
                'abstract' => isset($validated['abstract']) ? trim($validated['abstract']) : $document->abstract,
            ]);

            return response()->json([
                'error' => false,
                'message' => 'Thesis metadata updated successfully.',
                'document' => $document
            ]);
        } catch (\Throwable $e) {
            Log::error('Admin thesis update error: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Failed to update thesis: ' . $e->getMessage()
            ], 422);
        }
    }

    public function destroy($id)
    {
        try {
            $document = Document::findOrFail($id);
            $title = $document->title;

            if (!empty($document->file_path)) {
                try {
                    $baseUrl = rtrim((string) env('SUPABASE_URL'), '/');
                    $serviceKey = (string) (env('SUPABASE_SERVICE_ROLE_KEY') ?: env('SUPABASE_PUBLISHABLE_KEY'));
                    if ($baseUrl && $serviceKey) {
                        Http::withoutVerifying()->withHeaders([
                            'apikey' => $serviceKey,
                            'Authorization' => "Bearer {$serviceKey}",
                        ])->delete("{$baseUrl}/storage/v1/object/theses", [
                            'prefixes' => [$document->file_path]
                        ]);
                    }
                } catch (\Throwable $stErr) {
                    Log::warning('Storage file deletion error (ignored): ' . $stErr->getMessage());
                }
            }

            $document->delete();

            return response()->json([
                'error' => false,
                'message' => "Thesis '{$title}' and its indexed vectors were permanently deleted."
            ]);
        } catch (\Throwable $e) {
            Log::error('Admin thesis deletion error: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Failed to delete thesis: ' . $e->getMessage()
            ], 500);
        }
    }

    public function searchByProposal(Request $request)
    {
        try {
            $request->validate([
                'proposal_file' => 'required|file|max:10240',
            ]);

            $file = $request->file('proposal_file');
            if (!$file || !$file->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Uploaded file is invalid or missing.'
                ], 422);
            }

            $extension = strtolower($file->getClientOriginalExtension());
            $clientFilename = $file->getClientOriginalName();
            $rawText = '';

            if ($extension === 'pdf') {
                try {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($file->getRealPath());
                    $rawText = $pdf->getText();
                } catch (\Throwable $pdfErr) {
                    Log::error('PDF parsing error: ' . $pdfErr->getMessage());
                    return response()->json([
                        'success' => false,
                        'message' => 'Unable to read this PDF. Please ensure the document is not password-protected or corrupted.'
                    ], 422);
                }
            } elseif ($extension === 'docx') {
                try {
                    if (class_exists('ZipArchive')) {
                        $zip = new \ZipArchive();
                        if ($zip->open($file->getRealPath()) === true) {
                            $xml = $zip->getFromName('word/document.xml');
                            if ($xml) {
                                $xml = str_replace(['</w:r>', '</w:p>', '<w:tab/>', '<w:br/>', '<w:cr/>'], [' ', "\n", "\t", "\n", "\n"], $xml);
                                $rawText = strip_tags($xml);
                                $rawText = html_entity_decode($rawText, ENT_QUOTES | ENT_XML1, 'UTF-8');
                            }

                            if (empty(trim($rawText))) {
                                for ($i = 0; $i < $zip->numFiles; $i++) {
                                    $name = $zip->getNameIndex($i);
                                    if (str_starts_with($name, 'word/') && str_ends_with($name, '.xml')) {
                                        $subXml = $zip->getFromIndex($i);
                                        if ($subXml) {
                                            $subXml = str_replace(['</w:r>', '</w:p>', '<w:tab/>', '<w:br/>', '<w:cr/>'], [' ', "\n", "\t", "\n", "\n"], $subXml);
                                            $rawText .= ' ' . strip_tags($subXml);
                                        }
                                    }
                                }
                                $rawText = html_entity_decode($rawText, ENT_QUOTES | ENT_XML1, 'UTF-8');
                            }
                            $zip->close();
                        }
                    }
                } catch (\Throwable $docxErr) {
                    Log::error('DOCX parsing error: ' . $docxErr->getMessage());
                }

                // Fallback: If ZipArchive failed or extracted no text, attempt binary regex extraction on <w:t> tags
                if (empty(trim($rawText))) {
                    try {
                        $binary = file_get_contents($file->getRealPath());
                        if ($binary !== false && preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/si', $binary, $matches)) {
                            $rawText = implode(' ', $matches[1]);
                            $rawText = html_entity_decode($rawText, ENT_QUOTES | ENT_XML1, 'UTF-8');
                        }
                    } catch (\Throwable $binErr) {
                        Log::warning('DOCX binary fallback error: ' . $binErr->getMessage());
                    }
                }
            } elseif (in_array($extension, ['txt', 'md', 'rtf'], true)) {
                $rawText = file_get_contents($file->getRealPath());
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Unsupported file type (.{$extension}). Please upload a PDF, DOCX, or TXT file."
                ], 422);
            }

            $cleanedText = trim(preg_replace('/\s+/', ' ', $rawText ?? ''));
            if (mb_strlen($cleanedText) < 30) {
                return response()->json([
                    'success' => false,
                    'message' => 'The uploaded file does not contain enough readable text. If this is a scanned image PDF, please provide a text-based document.'
                ], 422);
            }

            /** @var \App\Services\GeminiService $geminiService */
            $geminiService = app(\App\Services\GeminiService::class);

            // 1. Synthesize concepts from proposal
            $conceptData = $geminiService->extractProposalConcepts($cleanedText);

            // 2. Generate vector embedding for literature matching
            $queryText = !empty($conceptData['embedding_query']) 
                ? $conceptData['embedding_query'] 
                : mb_substr($cleanedText, 0, 1500);

            $queryEmbedding = $geminiService->generateEmbedding($queryText);
            if (empty($queryEmbedding)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate vector representation for your proposal. Please try again.'
                ], 500);
            }

            $embeddingString = '[' . implode(',', $queryEmbedding) . ']';

            // 3. Search Supabase document_chunks using pgvector cosine distance
            $similarChunks = DB::select("
                SELECT
                    dc.document_id,
                    MIN(dc.embedding OPERATOR(extensions.<=>) ?::extensions.vector) AS distance
                FROM document_chunks dc
                INNER JOIN documents d ON d.id = dc.document_id
                WHERE dc.embedding IS NOT NULL
                  AND (d.status = 'approved' OR d.status IS NULL)
                GROUP BY dc.document_id
                ORDER BY distance ASC
                LIMIT 35
            ", [$embeddingString]);

            $similarityMap = [];
            $docIds = [];

            foreach ($similarChunks as $chunk) {
                $docId = (int) $chunk->document_id;
                $distance = (float) $chunk->distance;

                // Calibrated similarity for natural language proposal matching
                $normalized = 1 - (($distance - 0.08) / 0.42);
                $score = max(10, min(99, (int) round($normalized * 100)));

                if ($distance <= 0.45) {
                    $similarityMap[$docId] = $score;
                    $docIds[] = $docId;
                }
            }

            $approvedScope = fn($q) => $q->where(fn($sub) => $sub->where('status', 'approved')->orWhereNull('status'));
            $department = trim((string) $request->input('department', ''));

            if (!empty($docIds)) {
                $query = Document::whereIn('id', $docIds)->where($approvedScope);
                if ($department !== '' && $department !== 'all') {
                    $this->applyDepartmentFilter($query, $department);
                }
                $matchedDocs = $query->get();
            } else {
                // If vector chunks yielded no matches under threshold, fallback to topic keyword search
                $matchedDocs = collect([]);
                if (!empty($conceptData['topics'])) {
                    $topicQuery = Document::where($approvedScope);
                    $topicQuery->where(function ($q) use ($conceptData) {
                        foreach ($conceptData['topics'] as $topic) {
                            $t = '%' . strtolower($topic) . '%';
                            $q->orWhereRaw('LOWER(title) LIKE ?', [$t])
                              ->orWhereRaw('LOWER(abstract) LIKE ?', [$t]);
                        }
                    });
                    if ($department !== '' && $department !== 'all') {
                        $this->applyDepartmentFilter($topicQuery, $department);
                    }
                    $matchedDocs = $topicQuery->limit(10)->get();
                    foreach ($matchedDocs as $mDoc) {
                        $similarityMap[$mDoc->id] = 60;
                    }
                }
            }

            $rankedDocs = $matchedDocs->map(function ($doc) use ($similarityMap) {
                $doc->similarity_score = $similarityMap[$doc->id] ?? 50;
                return $doc;
            })->sortByDesc('similarity_score')->values();

            return response()->json([
                'success' => true,
                'filename' => $clientFilename,
                'title' => $conceptData['title'] ?? 'Concept Proposal',
                'summary' => $conceptData['summary'] ?? '',
                'topics' => $conceptData['topics'] ?? [],
                'documents' => $rankedDocs,
                'total' => $rankedDocs->count(),
            ]);

        } catch (\Throwable $e) {
            Log::error('searchByProposal error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error analyzing proposal: ' . $e->getMessage()
            ], 500);
        }
    }
}
