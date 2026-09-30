<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CheckInTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_authenticated_user_checks_in_before_deadline_using_server_values(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 07:29:59', 'Asia/Jakarta'));
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('check-in.store'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', 'Check-in berhasil.');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'attendance_date' => '2026-09-28',
            'check_in_time' => '07:29:59',
            'check_out_time' => null,
            'status' => 'hadir',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('2026-09-28')
            ->assertSee('07:29:59')
            ->assertSee('Hadir')
            ->assertSee('disabled')
            ->assertSee('Sudah check-in hari ini');
    }

    public function test_exactly_073000_is_hadir(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 07:30:00', 'Asia/Jakarta'));
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('check-in.store'));

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'check_in_time' => '07:30:00',
            'status' => 'hadir',
        ]);
    }

    public function test_after_073000_is_terlambat(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 07:30:01', 'Asia/Jakarta'));
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('check-in.store'));

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'check_in_time' => '07:30:01',
            'status' => 'terlambat',
        ]);
    }

    public function test_guest_cannot_check_in(): void
    {
        $this->post(route('check-in.store'))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_duplicate_check_in_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 07:20:00', 'Asia/Jakarta'));
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('check-in.store'));

        Carbon::setTestNow(Carbon::parse('2026-09-28 07:40:00', 'Asia/Jakarta'));
        $this->actingAs($user)->post(route('check-in.store'))
            ->assertSessionHasErrors(['check_in' => 'Presensi Anda hari ini sudah tercatat.']);

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'check_in_time' => '07:20:00',
            'status' => 'hadir',
        ]);
    }

    public function test_client_cannot_supply_attendance_values(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 07:40:00', 'Asia/Jakarta'));
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('check-in.store'), [
            'attendance_date' => '2026-09-27',
            'check_in_time' => '07:00:00',
            'status' => 'hadir',
        ])->assertSessionHasErrors(['attendance_date', 'check_in_time', 'status']);

        $this->assertDatabaseCount('attendances', 0);
    }
}
