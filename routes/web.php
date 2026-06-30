<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Giao diện test API
Route::get('/docs', function () {
    return view('api-docs');
});
