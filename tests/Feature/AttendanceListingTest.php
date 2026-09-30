<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('attendances.index'))->assertRedirect(route('login'));
    }

    public function test_user_sees_only_own_attendance_in_newest_date_order(): void
    {
        $user = User::factory()->create(['name' => 'Pegawai Satu']);
        $other = User::factory()->create(['name' => 'Pegawai Lain']);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-28',
            'status' => 'izin',
            'check_in_time' => null,
            'check_out_time' => null,
        ]);
        Attendance::factory()->create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-30',
            'status' => 'terlambat',
            'check_in_time' => '07:40:00',
            'check_out_time' => '16:00:00',
        ]);
        Attendance::factory()->create([
            'user_id' => $other->id,
            'attendance_date' => '2026-09-29',
        ]);

        $this->actingAs($user)->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('Pegawai Satu')
            ->assertSee('07:40:00')
            ->assertSee('16:00:00')
            ->assertSee('Izin')
            ->assertSeeInOrder(['2026-09-30', '2026-09-28'])
            ->assertDontSee('Pegawai Lain')
            ->assertDontSee('2026-09-29');
    }

    public function test_empty_attendance_shows_message(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('Belum ada data presensi.');
    }
}
