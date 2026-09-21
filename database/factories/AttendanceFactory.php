<?php

namespace Database\Factories;

use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkIn = fake()->dateTimeBetween('06:30:00', '07:30:00');
        $checkOut = (clone $checkIn)->modify('+8 hours');

        return [
            'user_id' => User::factory(),
            'attendance_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'status' => 'hadir',
            'check_in_time' => $checkIn->format('H:i:s'),
            'check_out_time' => $checkOut->format('H:i:s'),
        ];
    }
}
