<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;

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
                $dates->push([
                    'user_id' => $user->id,
                    'attendance_date' => now()->subDays($i)->format('Y-m-d'),
                ]);
            }
        }
        $records = $dates->shuffle()->take(100);
        foreach($records as $index => $record) {
            $factory = match (true) {
                $index < 60 => Attendance::factory()->hadir(),
                $index < 80 => Attendance::factory()->terlambat(),
                $index < 90 => Attendance::factory()->izin(),
                default => Attendance::factory()->sakit(),
            };
            $factory->create($record);
            
        }
        
    }
}
