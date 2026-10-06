<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $dates = collect();
        foreach ($users as $user) {
            for ($i = 0; $i < 30; $i++) {
                if (Carbon::now('Asia/Jakarta')->subDays($i)->isWeekend()) {
                    continue;
                }

                $dates->push([
                    'user_id' => $user->id,
                    'attendance_date' => now()->subDays($i)->format('Y-m-d'),
                ]);
            }
        }
        $records = $dates->shuffle()->take(100);
        foreach ($records as $index => $record) {
            $factory = match (true) {
                $index < 60 => Attendance::factory()->state($record)->hadir(),
                $index < 80 => Attendance::factory()->state($record)->terlambat(),
                $index < 90 => Attendance::factory()->state($record)->izin(),
                default => Attendance::factory()->state($record)->sakit(),
            };
            $factory->create();
        }
    }
}
