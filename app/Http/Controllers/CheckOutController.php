<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckOutController extends Controller
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

        $checkOut = now();
        $attendance = $request->user()->attendances()
            ->whereDate('attendance_date', $checkOut->toDateString())
            ->first();

        if (! $attendance || ! $attendance->check_in_time) {
            return back()->withErrors(['check_out' => 'Anda belum check-in hari ini.']);
        }

        if ($attendance->check_out_time) {
            return back()->withErrors(['check_out' => 'Anda sudah check-out hari ini.']);
        }

        $updated = $request->user()->attendances()
            ->whereKey($attendance->id)
            ->whereNull('check_out_time')
            ->update(['check_out_time' => $checkOut->format('H:i:s')]);

        if (! $updated) {
            return back()->withErrors(['check_out' => 'Anda sudah check-out hari ini.']);
        }

        return redirect()->route('dashboard')->with('success', 'Check-out berhasil.');
    }
}
