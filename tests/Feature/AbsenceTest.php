<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AbsenceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_izin_and_sakit_are_recorded_without_checkin_or_checkout(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 09:00:00', 'Asia/Jakarta'));

        foreach (['izin', 'sakit'] as $status) {
            $user = User::factory()->create();

            $this->actingAs($user)->post(route('absence.store'), ['status' => $status])
                ->assertRedirect(route('dashboard'))
                ->assertSessionHas('success', ucfirst($status).' hari ini berhasil dicatat.');

            $this->assertDatabaseHas('attendances', [
                'user_id' => $user->id,
                'attendance_date' => '2026-09-28',
                'status' => $status,
                'check_in_time' => null,
                'check_out_time' => null,
            ]);

            $this->actingAs($user)->get(route('dashboard'))
                ->assertOk()
                ->assertSee(ucfirst($status).' tercatat untuk 2026-09-28.')
                ->assertDontSee('Check-out</button>', false);
        }
    }

    public function test_absence_is_rejected_when_attendance_already_exists(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 09:00:00', 'Asia/Jakarta'));
        $user = User::factory()->create();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-28',
            'check_in_time' => '07:20:00',
            'check_out_time' => null,
            'status' => 'hadir',
        ]);

        $this->actingAs($user)->post(route('absence.store'), ['status' => 'izin'])
            ->assertSessionHasErrors(['status' => 'Presensi Anda hari ini sudah tercatat.']);

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => 'hadir',
            'check_in_time' => '07:20:00',
        ]);
    }

    public function test_second_absence_and_checkin_after_absence_are_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 09:00:00', 'Asia/Jakarta'));
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('absence.store'), ['status' => 'sakit']);
        $this->actingAs($user)->post(route('absence.store'), ['status' => 'izin'])
            ->assertSessionHasErrors(['status' => 'Presensi Anda hari ini sudah tercatat.']);
        $this->actingAs($user)->post(route('check-in.store'))
            ->assertSessionHasErrors('check_in');
        $this->actingAs($user)->post(route('check-out.store'))
            ->assertSessionHasErrors('check_out');

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => 'sakit',
            'check_in_time' => null,
            'check_out_time' => null,
        ]);
    }

    public function test_guest_invalid_status_and_client_supplied_values_are_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 09:00:00', 'Asia/Jakarta'));
        $this->post(route('absence.store'), ['status' => 'izin'])->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->post(route('absence.store'), ['status' => 'hadir'])
            ->assertSessionHasErrors('status');
        $this->actingAs($user)->post(route('absence.store'), [
            'status' => 'sakit',
            'attendance_date' => '2026-09-27',
            'check_in_time' => '07:00:00',
        ])->assertSessionHasErrors(['attendance_date', 'check_in_time']);

        $this->assertDatabaseCount('attendances', 0);
    }
}
