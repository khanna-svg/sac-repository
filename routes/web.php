<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\RequireSacAdmin;
use App\Http\Controllers\DocumentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Login page
Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');


// Student OTP - Send Code
Route::post('/login/send-code', [AuthController::class, 'sendCode'])
    ->middleware('throttle:5,1');


// Student OTP - Verify Code
Route::post('/login/verify-code', [AuthController::class, 'verifyCode'])
    ->middleware('throttle:10,1');


// Reset pending login
Route::post('/login/reset', function (Request $request) {

    $request->session()->forget('pending_email');

    return redirect('/login');

})->name('login.reset');

Route::post('/admin/login', [AdminAuthController::class, 'login']);

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

Route::middleware('sac.auth')->group(function () {

    Route::get('/', function () {

        if (session('sac_user_role') === 'admin') {
            return redirect()->route('admin.upload');
        }

        return redirect()->route('documents');

    });

    Route::get('/documents', function () {

        if (session('sac_user_role') === 'admin') {
            return redirect()->route('admin.upload');
        }

        return view('documents');

    })->name('documents');

    Route::get('/chat', function () {

        return view('chat');

    })->name('chat');

    Route::get('/admin/upload', function () {

        if (session('sac_user_role') !== 'admin') {

            return redirect()->route('documents');

        }

        return view('admin.upload');

    })->name('admin.upload');

    Route::get('/backend/documents/{document}/view', [DocumentController::class, 'viewPdf']);

    // Admin-Only Routes (Protected by RequireSacAdmin Middleware)
    Route::middleware(RequireSacAdmin::class)->group(function () {

        Route::get('/admin/upload', function () {
            return view('admin.upload');
        })->name('admin.upload');

        Route::post('/backend/documents/upload-url', [DocumentController::class, 'createUploadUrl']);

        Route::post('/backend/documents/upload', [DocumentController::class, 'store']);

    });

});