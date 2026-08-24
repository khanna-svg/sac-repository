<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\DocumentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware([
    'web',
    'sac.auth',
])->group(function () {

    Route::get('/documents', [
        DocumentController::class,
        'index',
    ]);


    Route::get('/documents/{document}/view', [
        DocumentController::class,
        'viewPdf',
    ]);

    Route::post('/chat', [
        ChatController::class,
        'ask',
    ]);

    Route::get('/bookmarks', [\App\Http\Controllers\BookmarkController::class, 'index']);
    Route::get('/bookmarks/ids', [\App\Http\Controllers\BookmarkController::class, 'getIds']);
    Route::post('/bookmarks/toggle', [\App\Http\Controllers\BookmarkController::class, 'toggle']);
    Route::get('/graph/data', [\App\Http\Controllers\KnowledgeGraphController::class, 'data']);
});

Route::middleware([
    'web',
    'sac.auth',
    'sac.admin',
])->group(function () {

    Route::post('/documents/upload-url', [
        DocumentController::class,
        'createUploadUrl',
    ]);

    Route::post('/documents/upload', [
        DocumentController::class,
        'store',
    ]);

    Route::get('/admin/analytics-data', [
        \App\Http\Controllers\AnalyticsController::class,
        'data',
    ]);
});
