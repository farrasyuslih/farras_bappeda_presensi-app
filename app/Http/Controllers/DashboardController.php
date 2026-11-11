<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        if ($request->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $today = now('Asia/Jakarta')->startOfDay();
        $query = $request->user()->attendances();
        $todayAttendance = (clone $query)->where('attendance_date', $today->toDateString())->first();
        $counts = (clone $query)
            ->whereBetween('attendance_date', [$today->copy()->startOfMonth()->toDateString(), $today->toDateString()])
            ->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $summary = collect(['hadir', 'terlambat', 'izin', 'sakit'])->mapWithKeys(fn ($status) => [$status => (int) ($counts[$status] ?? 0)])->all();
        $checkIns = $summary['hadir'] + $summary['terlambat'];
        $onTime = $checkIns ? round($summary['hadir'] / $checkIns * 100, 1) : null;
        $start = $today->copy()->subDays(6);
        $daily = (clone $query)->whereBetween('attendance_date', [$start->toDateString(), $today->toDateString()])->get()->keyBy('attendance_date');
        $week = collect(range(0, 6))->map(function ($offset) use ($start, $daily) {
            $date = $start->copy()->addDays($offset)->toDateString();

            return ['date' => $date, 'status' => $daily->get($date)?->status];
        });
        $recentAttendances = (clone $query)->orderByDesc('attendance_date')->orderByDesc('id')->limit(10)->get();

        return view('presensi', compact('todayAttendance', 'summary', 'onTime', 'week', 'recentAttendances', 'today'));
    }
}
