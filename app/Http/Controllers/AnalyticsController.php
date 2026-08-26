<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Document;
use App\Models\DocumentChunk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Show the Admin Analytics view.
     */
    public function indexView(Request $request)
    {
        if ($request->session()->get('sac_user_role') !== 'admin') {
            return redirect('/documents');
        }

        return view('admin.analytics');
    }

    /**
     * Return JSON statistics for Chart.js.
     */
    public function data(Request $request)
    {
        if ($request->session()->get('sac_user_role') !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // 1. Total Metrics
        $totalTheses = Document::count();
        $totalPages = DocumentChunk::count();
        $totalBookmarks = Bookmark::count();
        $totalDepartments = Document::whereNotNull('department')->distinct('department')->count('department');

        // 2. Department Breakdown
        $departmentStats = Document::select('department', DB::raw('count(*) as count'))
            ->whereNotNull('department')
            ->groupBy('department')
            ->orderByDesc('count')
            ->get();

        // 3. Course Code Breakdown
        $courseStats = Document::select('course_code', DB::raw('count(*) as count'))
            ->whereNotNull('course_code')
            ->groupBy('course_code')
            ->orderByDesc('count')
            ->get();

        // 4. Yearly Output Trend
        $yearlyStats = Document::select(
            DB::raw("COALESCE(TO_CHAR(created_at, 'YYYY'), '2026') as year"),
            DB::raw('count(*) as count')
        )
            ->groupBy('year')
            ->orderBy('year', 'asc')
            ->get();

        return response()->json([
            'metrics' => [
                'total_theses' => $totalTheses,
                'total_pages' => $totalPages,
                'total_bookmarks' => $totalBookmarks,
                'total_departments' => $totalDepartments ?: 1,
            ],
            'departments' => $departmentStats,
            'courses' => $courseStats,
            'yearly' => $yearlyStats,
        ]);
    }

    /**
     * Export all repository theses as CSV report for library administration.
     */
    public function exportCsv(Request $request)
    {
        if ($request->session()->get('sac_user_role') !== 'admin') {
            return redirect('/documents');
        }

        $theses = Document::withCount('chunks')->latest()->get();
        $filename = 'SAC_Thesis_Repository_Report_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($theses) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fputs($file, "\xEF\xBB\xBF");

            // CSV Header Row
            fputcsv($file, [
                'ID',
                'Thesis Title',
                'Author(s)',
                'Department',
                'Course / Program',
                'Indexed Pages',
                'Date Uploaded',
                'Abstract'
            ]);

            foreach ($theses as $thesis) {
                fputcsv($file, [
                    $thesis->id,
                    $thesis->title,
                    $thesis->author,
                    strtoupper($thesis->department ?? 'N/A'),
                    strtoupper($thesis->course_code ?? 'N/A'),
                    $thesis->chunks_count ?? 0,
                    $thesis->created_at ? $thesis->created_at->format('Y-m-d H:i') : 'N/A',
                    preg_replace('/\s+/', ' ', trim($thesis->abstract ?? ''))
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
