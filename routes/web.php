<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

// Student OTP Verification Routes
Route::post('/login/send-code', [AuthController::class, 'sendCode'])
    ->middleware('throttle:5,1');

Route::post('/login/verify-code', [AuthController::class, 'verifyCode'])
    ->middleware('throttle:10,1');

Route::post('/login/reset', function (Request $request) {
    $request->session()->forget('pending_email');
    return redirect('/login');
})->name('login.reset');

// Admin Password Login Route
Route::post('/admin/login', [AdminAuthController::class, 'login']);

// Sign Out Route
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes (Requires SAC Authentication)
Route::middleware('sac.auth')->group(function () {
    Route::redirect('/', '/documents');

    // Student View: Search & Read Thesis Documents
    Route::get('/documents', function () {
        return view('documents');
    })->name('documents');

    // AI Assistant View
    Route::get('/chat', function () {
        return view('chat');
    })->name('chat');

    // Admin Dashboard View: Uploading Thesis
    Route::get('/admin/upload', function () {
        if (session('user_role') !== 'admin') {
            return redirect('/documents');
        }
        return view('admin.upload');
    })->name('admin.upload');

    // Backend Document Upload Endpoint
    Route::post('/backend/documents/upload', [DocumentController::class, 'store']);
});