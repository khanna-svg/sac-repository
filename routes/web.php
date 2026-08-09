<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;

Route::redirect('/', '/documents');

Route::get('/documents', function () {
    return view('documents');
});

// Route to stream PDF files securely
Route::get('/documents/file/{filename}', [DocumentController::class, 'viewPdf'])->name('documents.file');

Route::get('/chat', function () {
    return view('chat');
});