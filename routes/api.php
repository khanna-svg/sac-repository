<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ChatController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Document Routes
Route::get('/documents', [DocumentController::class, 'index']);
Route::post('/documents/upload', [DocumentController::class, 'store']);

// View a thesis PDF
Route::get('/documents/{document}/view', [
    DocumentController::class,
    'viewPdf'
]);

// RAG AI Chatbot
Route::post('/chat', [ChatController::class, 'ask']);