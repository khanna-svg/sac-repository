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
}
