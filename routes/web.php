<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/documents');

Route::get('/documents', function () {
    return view('documents');
});

Route::get('/chat', function () {
    return view('chat');
});