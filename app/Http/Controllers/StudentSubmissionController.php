<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\ThesisNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StudentSubmissionController extends Controller
{
    /**
     * Display student thesis submission form and previous submission history.
     */
    public function showForm(Request $request)
    {
        $email = strtolower((string) $request->session()->get('sac_user_email'));

        $submissions = Document::where('submitted_by_email', $email)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('student.submit', [
            'submissions' => $submissions,
            'userEmail' => $email,
        ]);
    }

    /**
     * Generate signed Supabase upload URL for student PDF manuscript.
     */
    public function createUploadUrl(Request $request)
    {
        $email = strtolower((string) $request->session()->get('sac_user_email'));
        if ($email === '' || !str_ends_with($email, '@sac.edu.ph')) {
            return response()->json([
                'error' => true,
                'message' => 'Unauthorized. Please sign in with your @sac.edu.ph account.',
            ], 403);
        }

        $filename = (string) $request->input('filename', 'thesis.pdf');
        $baseUrl = rtrim((string) config('services.supabase.url', env('SUPABASE_URL')), '/');
        $key = (string) config('services.supabase.service_role_key', env('SUPABASE_SERVICE_ROLE_KEY'));
        $bucket = (string) config('services.supabase.bucket', env('SUPABASE_STORAGE_BUCKET', 'thesis'));

        if ($baseUrl === '' || $key === '' || $bucket === '') {
            return response()->json([
                'error' => true,
                'message' => 'Storage service is not configured.',
            ], 500);
        }

        $safeName = Str::slug(pathinfo($filename, PATHINFO_FILENAME));
        $safeName = $safeName ?: 'student_thesis';
        $path = "documents/{$safeName}_" . time() . '_' . Str::random(6) . '.pdf';

        $encodedBucket = rawurlencode($bucket);
        $encodedPath = collect(explode('/', $path))->map(fn($part) => rawurlencode($part))->implode('/');
        $signUrl = "{$baseUrl}/storage/v1/object/upload/sign/{$encodedBucket}/{$encodedPath}";

        try {
            $response = Http::withoutVerifying()
                ->timeout(20)
                ->withHeaders([
                    'Authorization' => "Bearer {$key}",
                    'apikey' => $key,
                    'Content-Type' => 'application/json',
                ])
                ->post($signUrl);

            if (!$response->successful()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Could not obtain upload signature from storage service.',
                ], 502);
            }

            $relativeUrl = $response->json('url');
            if (!$relativeUrl) {
                return response()->json([
                    'error' => true,
                    'message' => 'Invalid upload signature response.',
                ], 500);
            }

            $signedUrl = (str_starts_with($relativeUrl, 'http://') || str_starts_with($relativeUrl, 'https://'))
                ? $relativeUrl
                : $baseUrl . $relativeUrl;

            $parsed = parse_url($signedUrl);
            $queryParams = [];
            parse_str($parsed['query'] ?? '', $queryParams);
            $token = $response->json('token') ?? ($queryParams['token'] ?? null);

            if (!$token) {
                return response()->json([
                    'error' => true,
                    'message' => 'Missing upload token in signature.',
                ], 500);
            }

            return response()->json([
                'error' => false,
                'path' => $path,
                'signedUrl' => $signedUrl,
                'token' => $token,
            ]);
        } catch (\Throwable $e) {
            Log::error('Student createUploadUrl failed: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Failed to prepare upload destination.',
            ], 500);
        }
    }

    /**
     * Store student thesis submission with 'pending' status.
     */
    public function store(Request $request)
    {
        $email = strtolower((string) $request->session()->get('sac_user_email'));
        if ($email === '' || !str_ends_with($email, '@sac.edu.ph')) {
            return response()->json([
                'error' => true,
                'message' => 'Unauthorized. Please sign in with your @sac.edu.ph account.',
            ], 403);
        }

        $request->validate([
            'title' => ['required', 'string', 'max:500'],
            'leader_name' => ['required', 'string', 'max:255'],
            'members' => ['nullable', 'string', 'max:500'],
            'department' => ['required', 'string'],
            'course_code' => ['required', 'string'],
            'abstract' => ['required', 'string'],
            'file_path' => ['required', 'string'],
        ]);

        $title = trim($request->title);
        $leaderName = trim($request->leader_name);
        $members = trim((string) $request->members);
        $author = $leaderName . ($members !== '' ? ', ' . $members : '');
        $department = strtolower(trim($request->department));
        $courseCode = strtolower(trim($request->course_code));
        $abstract = trim($request->abstract);
        $filePath = trim($request->file_path);
        $chunks = $request->input('chunks', []);

        try {
            $document = Document::create([
                'title' => $title,
                'author' => $author,
                'department' => $department,
                'course_code' => $courseCode,
                'abstract' => $abstract,
                'file_path' => $filePath,
                'file_url' => '',
                'status' => 'pending',
                'submitted_by_name' => $leaderName,
                'submitted_by_email' => $email,
                'admin_notes' => null,
            ]);

            $document->update([
                'file_url' => "/backend/documents/{$document->id}/view",
            ]);

            // Save text chunks if provided from PDF.js extraction
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
            }

            // Create initial notification for the student
            ThesisNotification::create([
                'user_email' => $email,
                'title' => 'Thesis Submission Received',
                'message' => "Your manuscript \"{$title}\" has been successfully uploaded and is pending review by the repository administrator.",
                'type' => 'info',
                'document_id' => $document->id,
                'is_read' => false,
            ]);

            return response()->json([
                'error' => false,
                'message' => 'Thesis submitted successfully! It has been routed to the administrator for review.',
                'document' => $document,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Student thesis submission failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => true,
                'message' => 'Failed to record thesis submission. Please try again.',
            ], 500);
        }
    }
}
