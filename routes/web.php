<?php

use App\Http\Controllers\AbsenceController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\CheckOutController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.authenticate');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $todayAttendance = auth()->user()->attendances()
            ->whereDate('attendance_date', now()->toDateString())
            ->first();

        return view('presensi', compact('todayAttendance'));
    })->name('dashboard');

    Route::post('/check-in', [CheckInController::class, 'store'])->name('check-in.store');
    Route::post('/check-out', [CheckOutController::class, 'store'])->name('check-out.store');
    Route::post('/absence', [AbsenceController::class, 'store'])->name('absence.store');
    Route::get('/attendances', [AttendanceController::class, 'index'])->name('attendances.index');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
