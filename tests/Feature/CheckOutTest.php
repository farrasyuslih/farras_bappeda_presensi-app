<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CheckOutTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_checked_in_user_checks_out_with_server_time_without_changing_status(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 15:30:00', 'Asia/Jakarta'));
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-28',
            'check_in_time' => '07:25:00',
            'check_out_time' => null,
            'status' => 'hadir',
        ]);

        $this->actingAs($user)->post(route('check-out.store'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', 'Check-out berhasil.');

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'check_in_time' => '07:25:00',
            'check_out_time' => '15:30:00',
            'status' => 'hadir',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Check-out pukul 15:30:00')
            ->assertSee('Sudah check-out hari ini');
    }

    public function test_checkout_without_checkin_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 15:30:00', 'Asia/Jakarta'));
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('check-out.store'))
            ->assertSessionHasErrors(['check_out' => 'Anda belum check-in hari ini.']);

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_repeated_checkout_is_rejected_without_changing_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 15:30:00', 'Asia/Jakarta'));
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-28',
            'check_in_time' => '07:40:00',
            'check_out_time' => '15:00:00',
            'status' => 'terlambat',
        ]);

        $this->actingAs($user)->post(route('check-out.store'))
            ->assertSessionHasErrors(['check_out' => 'Anda sudah check-out hari ini.']);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'check_out_time' => '15:00:00',
            'status' => 'terlambat',
        ]);
    }

    public function test_guest_and_client_supplied_time_are_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 15:30:00', 'Asia/Jakarta'));
        $this->post(route('check-out.store'))->assertRedirect(route('login'));

        $user = User::factory()->create();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-28',
            'check_in_time' => '07:25:00',
            'check_out_time' => null,
            'status' => 'hadir',
        ]);

        $this->actingAs($user)->post(route('check-out.store'), [
            'check_out_time' => '16:00:00',
        ])->assertSessionHasErrors('check_out_time');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'check_out_time' => null,
        ]);
    }
}
