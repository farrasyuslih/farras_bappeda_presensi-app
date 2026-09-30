<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [App\Http\Controllers\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [App\Http\Controllers\AuthController::class, 'login'])->name('login.authenticate');
    Route::get('/register', [App\Http\Controllers\AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [App\Http\Controllers\AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $todayAttendance = auth()->user()->attendances()
            ->whereDate('attendance_date', now()->toDateString())
            ->first();

        return view('index', compact('todayAttendance'));
    })->name('dashboard');

    Route::post('/check-in', [App\Http\Controllers\CheckInController::class, 'store'])->name('check-in.store');
    Route::post('/check-out', [App\Http\Controllers\CheckOutController::class, 'store'])->name('check-out.store');
    Route::post('/absence', [App\Http\Controllers\AbsenceController::class, 'store'])->name('absence.store');

    Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->name('logout');
});
