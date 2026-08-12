<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login/send-code', [AuthController::class, 'sendCode'])
    ->middleware('throttle:5,1');

Route::post('/login/verify-code', [AuthController::class, 'verifyCode'])
    ->middleware('throttle:10,1');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('sac.auth')->group(function () {
    Route::redirect('/', '/documents');

    Route::get('/documents', function () {
        return view('documents');
    });

    Route::get('/chat', function () {
        return view('chat');
    });
});