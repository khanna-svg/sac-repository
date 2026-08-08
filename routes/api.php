<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ChatController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Document Upload & Search Routes
Route::get('/documents', [DocumentController::class, 'index']);
Route::post('/documents/upload', [DocumentController::class, 'store']);

// RAG AI Chatbot Route
Route::post('/chat', [ChatController::class, 'ask']);