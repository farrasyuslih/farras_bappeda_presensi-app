<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = now('Asia/Jakarta')->startOfDay();
        $employees = User::query()->where('role', 'pegawai');
        $monthAttendances = Attendance::query()
            ->whereHas('user', fn ($query) => $query->where('role', 'pegawai'))
            ->whereBetween('attendance_date', [$today->copy()->startOfMonth()->toDateString(), $today->toDateString()]);
        $counts = (clone $monthAttendances)->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')->pluck('total', 'status');
        $summary = collect(['hadir', 'terlambat', 'izin', 'sakit'])
            ->mapWithKeys(fn ($status) => [$status => (int) ($counts[$status] ?? 0)])->all();
        $checkIns = $summary['hadir'] + $summary['terlambat'];
        $onTime = $checkIns ? round($summary['hadir'] / $checkIns * 100, 1) : null;
        $start = $today->copy()->subDays(6);
        $weekCounts = Attendance::query()
            ->whereHas('user', fn ($query) => $query->where('role', 'pegawai'))
            ->whereBetween('attendance_date', [$start->toDateString(), $today->toDateString()])
            ->selectRaw('attendance_date, status, COUNT(*) AS total')
            ->groupBy('attendance_date', 'status')->get()
            ->groupBy('attendance_date');
        $week = collect(range(0, 6))->map(function ($offset) use ($start, $weekCounts): array {
            $date = $start->copy()->addDays($offset)->toDateString();
            $counts = $weekCounts->get($date, collect())->pluck('total', 'status');

            return [
                'date' => $date,
                'hadir' => (int) ($counts['hadir'] ?? 0),
                'terlambat' => (int) ($counts['terlambat'] ?? 0),
            ];
        });
        $todayCounts = Attendance::query()
            ->whereHas('user', fn ($query) => $query->where('role', 'pegawai'))
            ->whereDate('attendance_date', $today->toDateString())
            ->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $todaySummary = collect(['hadir', 'terlambat', 'izin', 'sakit'])
            ->mapWithKeys(fn ($status) => [$status => (int) ($todayCounts[$status] ?? 0)])->all();
        $missingCheckOut = Attendance::query()
            ->whereHas('user', fn ($query) => $query->where('role', 'pegawai'))
            ->whereIn('status', ['hadir', 'terlambat'])
            ->whereNull('check_out_time')->whereDate('attendance_date', '<', $today->toDateString())->count();
        $recentAttendances = Attendance::query()->with('user')
            ->whereHas('user', fn ($query) => $query->where('role', 'pegawai'))
            ->orderByDesc('attendance_date')->orderByDesc('id')->limit(10)->get();

        return view('admin.dashboard', [
            'today' => $today,
            'employeeCount' => (clone $employees)->count(),
            'summary' => $summary,
            'onTime' => $onTime,
            'week' => $week,
            'todaySummary' => $todaySummary,
            'missingCheckOut' => $missingCheckOut,
            'recentAttendances' => $recentAttendances,
        ]);
    }
}
