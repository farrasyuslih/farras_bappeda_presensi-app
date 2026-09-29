<?php

namespace App\Http\Controllers;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => ['prohibited'],
            'attendance_date' => ['prohibited'],
            'check_in_time' => ['prohibited'],
            'check_out_time' => ['prohibited'],
            'status' => ['prohibited'],
        ]);

        $checkIn = now();
        $date = $checkIn->toDateString();
        $attendances = $request->user()->attendances();

        if ($attendances->whereDate('attendance_date', $date)->exists()) {
            return back()->withErrors(['check_in' => 'Anda sudah check-in hari ini.']);
        }

        try {
            $attendances->create([
                'attendance_date' => $date,
                'check_in_time' => $checkIn->format('H:i:s'),
                'check_out_time' => null,
                'status' => $checkIn->format('H:i:s') <= '07:30:00' ? 'hadir' : 'terlambat',
            ]);
        } catch (UniqueConstraintViolationException $exception) {
            return back()->withErrors(['check_in' => 'Anda sudah check-in hari ini.']);
        }

        return redirect()->route('dashboard')->with('success', 'Check-in berhasil.');
    }
}
