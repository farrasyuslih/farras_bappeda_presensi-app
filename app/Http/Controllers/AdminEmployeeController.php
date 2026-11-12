<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class AdminEmployeeController extends Controller
{
    public function index(): View
    {
        return view('admin.employees', [
            'employees' => User::query()->where('role', 'pegawai')->orderBy('name')->paginate(10),
        ]);
    }
}
