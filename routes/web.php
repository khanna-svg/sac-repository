<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Middleware\RequireSacAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login/send-code', [AuthController::class, 'sendCode'])
    ->middleware('throttle:5,1');

Route::post('/login/verify-code', [AuthController::class, 'verifyCode'])
    ->middleware('throttle:10,1');

Route::post('/login/reset', function (Request $request) {
    $request->session()->forget('pending_email');

    return redirect('/login');
})->name('login.reset');

Route::post('/admin/login', [AdminAuthController::class, 'login'])
    ->middleware('throttle:5,1');

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');


/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('sac.auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/', function () {

        if (session('sac_user_role') === 'admin') {
            return redirect()->route('admin.upload');
        }

        return redirect()->route('documents');

    });


    /*
    |--------------------------------------------------------------------------
    | Documents
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | Generate Embeddings
    |--------------------------------------------------------------------------
    |
    | This endpoint processes chunks that currently have no embedding.
    |
    */

    Route::post(
        '/documents/{id}/generate-embeddings',
        [DocumentController::class, 'generateEmbeddings']
    )->name('documents.generate-embeddings');


    /*
    |--------------------------------------------------------------------------
    | AI Chat
    |--------------------------------------------------------------------------
    */

    Route::get('/chat', function () {
        return view('chat');
    })->name('chat');


    /*
    |--------------------------------------------------------------------------
    | View PDF
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/backend/documents/{document}/view',
        [DocumentController::class, 'viewPdf']
    );


    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware([RequireSacAdmin::class])->group(function () {

        Route::get('/admin/upload', function () {
            return view('admin.upload');
        })->name('admin.upload');


        /*
        |--------------------------------------------------------------------------
        | Direct Supabase Upload
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/backend/documents/upload-url',
            [DocumentController::class, 'createUploadUrl']
        );


        /*
        |--------------------------------------------------------------------------
        | Normal Upload
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/backend/documents/upload',
            [DocumentController::class, 'store']
        );


        /*
        |--------------------------------------------------------------------------
        | Signed URL Upload
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/backend/documents/store-signed',
            [DocumentController::class, 'storeFromSignedUrl']
        );

    });

});