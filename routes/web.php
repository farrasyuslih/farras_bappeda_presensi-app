<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [App\Http\Controllers\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [App\Http\Controllers\AuthController::class, 'login'])->name('login.authenticate');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {return view ('index');})->name('dashboard');

    Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->name('logout');
});
