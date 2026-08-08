<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('login');
});

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::get('/documents', function () {
    return view('documents');
});

Route::get('/chat', function () {
    return view('chat');
});