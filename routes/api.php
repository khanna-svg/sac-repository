<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\DocumentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['web', 'sac.auth'])->group(function () {
    // Document routes
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::post('/documents/upload', [DocumentController::class, 'store']);

    // Private PDF viewer
    Route::get('/documents/{document}/view', [
        DocumentController::class,
        'viewPdf',
    ]);

    // RAG chatbot
    Route::post('/chat', [ChatController::class, 'ask']);
});