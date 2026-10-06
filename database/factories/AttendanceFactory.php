<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    public function definition(): array
    {
        do {
            $date = Carbon::instance(fake()->dateTimeBetween('-30 days', 'now'));
        } while ($date->isWeekend());

        return [
            'user_id' => User::factory(),
            'attendance_date' => $date->toDateString(),
            'status' => 'hadir',
            'check_in_time' => $this->clockBetween('06:30:00', '07:30:00'),
            'check_out_time' => $this->regularCheckOut($date->toDateString()),
        ];
    }

    public function hadir(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'hadir',
            'check_in_time' => $this->clockBetween('06:30:00', '07:30:00'),
            'check_out_time' => $this->regularCheckOut($attributes['attendance_date']),
        ]);
    }

    public function terlambat(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'terlambat',
            'check_in_time' => $this->clockBetween('07:30:01', '09:00:00'),
            'check_out_time' => $this->regularCheckOut($attributes['attendance_date']),
        ]);
    }

    public function izin(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'izin',
            'check_in_time' => null,
            'check_out_time' => null,
        ]);
    }

    public function sakit(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sakit',
            'check_in_time' => null,
            'check_out_time' => null,
        ]);
    }

    private function regularCheckOut(string $date): string
    {
        $end = Carbon::parse($date, 'Asia/Jakarta')->dayOfWeekIso === 5 ? '16:30:00' : '16:00:00';

        return $this->clockBetween($end, Carbon::parse($end)->addMinutes(30)->format('H:i:s'));
    }

    private function clockBetween(string $start, string $end): string
    {
        return gmdate('H:i:s', fake()->numberBetween($this->seconds($start), $this->seconds($end)));
    }

    private function seconds(string $time): int
    {
        [$hour, $minute, $second] = array_map('intval', explode(':', $time));

        return $hour * 3600 + $minute * 60 + $second;
    }
}
