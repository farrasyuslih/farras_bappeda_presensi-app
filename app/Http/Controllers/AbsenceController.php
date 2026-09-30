<?php

namespace App\Http\Controllers;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AbsenceController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['izin', 'sakit'])],
            'user_id' => ['prohibited'],
            'attendance_date' => ['prohibited'],
            'check_in_time' => ['prohibited'],
            'check_out_time' => ['prohibited'],
        ]);

        $date = now()->toDateString();
        $attendances = $request->user()->attendances();

        if ($attendances->whereDate('attendance_date', $date)->exists()) {
            return back()->withErrors(['status' => 'Presensi Anda hari ini sudah tercatat.']);
        }

        try {
            $attendances->create([
                'attendance_date' => $date,
                'check_in_time' => null,
                'check_out_time' => null,
                'status' => $validated['status'],
            ]);
        } catch (UniqueConstraintViolationException $exception) {
            return back()->withErrors(['status' => 'Presensi Anda hari ini sudah tercatat.']);
        }

        return redirect()->route('dashboard')->with('success', ucfirst($validated['status']).' hari ini berhasil dicatat.');
    }
}
