<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Middleware\RequireSacAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login/send-code', [AuthController::class, 'sendCode'])
    ->middleware('throttle:5,1');

Route::post('/login/verify-code', [AuthController::class, 'verifyCode'])
    ->middleware('throttle:10,1');

Route::post('/login/reset', [AuthController::class, 'resetLogin'])
    ->name('login.reset');

Route::post('/admin/login', [AdminAuthController::class, 'login'])
    ->middleware('throttle:5,1');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

// Direct Routing: Directs logged-in users to their dashboard, guests to login
Route::get('/', function () {
    $email = session('sac_user_email');
    $role = session('sac_user_role');

    if ($email && str_ends_with(strtolower($email), '@sac.edu.ph')) {
        if ($role === 'admin') {
            return redirect()->route('admin.analytics');
        }
        return redirect()->route('documents');
    }

    return redirect()->route('login');
})->name('home');

Route::get('/home', function () {
    return redirect()->route('home');
});

Route::middleware('sac.auth')->group(function () {

    Route::get('/dashboard', function () {
        if (session('sac_user_role') === 'admin') {
            return redirect()->route('admin.upload');
        }
        return redirect()->route('documents');
    })->name('dashboard');

    Route::get('/documents', function () {

        if (session('sac_user_role') === 'admin') {
            return redirect()->route('admin.upload');
        }

        return view('documents');
    })->name('documents');


    Route::get(
        '/documents/{document}',
        [DocumentController::class, 'show']
    )->name('documents.show');

    Route::post(
        '/documents/{id}/generate-embeddings',
        [DocumentController::class, 'generateEmbeddings']
    )->name('documents.generate-embeddings');

    Route::get('/chat', function () {
        return view('chat');
    })->name('chat');

    Route::get('/graph', [\App\Http\Controllers\KnowledgeGraphController::class, 'index'])
        ->name('graph');

    Route::get(
        '/backend/graph/data',
        [\App\Http\Controllers\KnowledgeGraphController::class, 'data']
    );

    Route::get('/bookmarks', [\App\Http\Controllers\BookmarkController::class, 'indexView'])
        ->name('bookmarks');

    Route::get('/settings', function () {
        return view('settings');
    })->name('settings');

    Route::get(
        '/backend/documents/{document}/view',
        [DocumentController::class, 'viewPdf']
    );

    Route::get(
        '/backend/documents/{document}/signed-url',
        [DocumentController::class, 'getSignedUrl']
    );

    Route::post(
        '/backend/chat',
        [\App\Http\Controllers\ChatController::class, 'ask']
    );

    Route::middleware([RequireSacAdmin::class])->group(function () {

        Route::get('/admin/upload', function () {
            return view('admin.upload');
        })->name('admin.upload');

        Route::get(
            '/admin/analytics',
            [\App\Http\Controllers\AnalyticsController::class, 'indexView']
        )->name('admin.analytics');

        Route::get(
            '/backend/admin/analytics-data',
            [\App\Http\Controllers\AnalyticsController::class, 'data']
        );

        Route::get(
            '/admin/analytics/export-csv',
            [\App\Http\Controllers\AnalyticsController::class, 'exportCsv']
        )->name('admin.analytics.export');

        Route::post(
            '/backend/documents/upload-url',
            [DocumentController::class, 'createUploadUrl']
        );

        Route::post(
            '/backend/documents/upload',
            [DocumentController::class, 'store']
        );

        Route::post(
            '/backend/documents/store-signed',
            [DocumentController::class, 'storeFromSignedUrl']
        );
    });
});
