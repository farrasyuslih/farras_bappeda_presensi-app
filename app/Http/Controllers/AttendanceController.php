<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $keys = ['name', 'exact_date', 'start_date', 'end_date', 'month', 'year', 'status'];
        $input = $request->only($keys);
        $validator = Validator::make($input, [
            'name' => ['nullable', 'string', 'max:255'],
            'exact_date' => ['nullable', 'date_format:Y-m-d'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:1,9999'],
            'status' => ['nullable', Rule::in(['hadir', 'terlambat', 'izin', 'sakit'])],
        ], [
            'string' => ':attribute harus berupa teks.',
            'max' => ':attribute maksimal :max karakter.',
            'date_format' => ':attribute harus berupa tanggal valid dengan format YYYY-MM-DD.',
            'integer' => ':attribute harus berupa bilangan bulat.',
            'between' => ':attribute harus antara :min dan :max.',
            'in' => ':attribute tidak valid.',
        ], [
            'name' => 'Nama', 'exact_date' => 'Tanggal tepat',
            'start_date' => 'Tanggal awal', 'end_date' => 'Tanggal akhir',
            'month' => 'Bulan', 'year' => 'Tahun', 'status' => 'Status',
        ]);

        $filled = fn (string $key): bool => isset($input[$key]) && $input[$key] !== '';
        $validator->after(function ($validator) use ($input, $filled) {
            $modes = (int) $filled('exact_date')
                + (int) ($filled('start_date') || $filled('end_date'))
                + (int) ($filled('month') || $filled('year'));

            if ($modes > 1) {
                $validator->errors()->add('date_mode', 'Gunakan hanya satu mode tanggal: tanggal tepat, rentang tanggal, atau bulan/tahun.');
            }

            if ($filled('start_date') && $filled('end_date')
                && ! $validator->errors()->has('start_date') && ! $validator->errors()->has('end_date')
                && $input['end_date'] < $input['start_date']) {
                $validator->errors()->add('end_date', 'Tanggal akhir harus sama dengan atau setelah tanggal awal.');
            }
        });

        // Keep scalar input visible even when validation fails; arrays cannot be rendered as form values.
        $filters = array_fill_keys($keys, '');
        foreach ($input as $key => $value) {
            $filters[$key] = is_scalar($value) ? (string) $value : '';
        }
        $hasFilters = collect($filters)->contains(fn ($value) => $value !== '');

        if ($validator->fails()) {
            return response()->view('attendances.index', [
                'attendances' => collect(), 'filters' => $filters,
                'hasFilters' => $hasFilters, 'filterErrors' => $validator->errors(),
            ], 422);
        }

        $query = $request->user()->attendances()->with('user');
        if ($filled('name')) {
            // Escape LIKE wildcards so the search text is treated literally.
            $name = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($filters['name']));
            $query->whereHas('user', fn ($user) => $user->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", ['%'.$name.'%']));
        }
        if ($filled('exact_date')) {
            $query->whereDate('attendance_date', $filters['exact_date']);
        }
        if ($filled('start_date')) {
            $query->whereDate('attendance_date', '>=', $filters['start_date']);
        }
        if ($filled('end_date')) {
            $query->whereDate('attendance_date', '<=', $filters['end_date']);
        }
        if ($filled('month')) {
            $query->whereMonth('attendance_date', (int) $filters['month']);
        }
        if ($filled('year')) {
            $query->whereYear('attendance_date', (int) $filters['year']);
        }
        if ($filled('status')) {
            $query->where('status', $filters['status']);
        }
        $attendances = $query->orderByDesc('attendance_date')->orderByDesc('id')->get();

        return view('attendances.index', [
            'attendances' => $attendances, 'filters' => $filters,
            'hasFilters' => $hasFilters, 'filterErrors' => $validator->errors(),
        ]);
    }
}
