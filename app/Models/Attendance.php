<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_date',
        'status',
        'check_in_time',
        'check_out_time',
    ];

    public function checkoutLabel(): ?string
    {
        if (! $this->check_in_time || ! $this->check_out_time) {
            return null;
        }

        $day = Carbon::parse($this->attendance_date, 'Asia/Jakarta')->dayOfWeekIso;
        if ($day > 5) {
            return null;
        }

        $endTime = $day === 5 ? '16:30:00' : '16:00:00';

        return $this->check_out_time < $endTime ? 'Pulang lebih awal' : 'Sesuai jam kerja';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
