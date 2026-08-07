<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::get('/register', function () {
    return view('register');
});

Route::get('/documents', function () {
    return view('documents');
});

Route::get('/chat', function () {
    return view('chat');
});