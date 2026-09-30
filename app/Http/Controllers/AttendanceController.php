<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $attendances = $request->user()->attendances()
            ->with('user')
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->get();

        return view('attendances.index', compact('attendances'));
    }
}
