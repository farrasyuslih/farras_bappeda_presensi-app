<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
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
            ->assertSessionHas('success', 'Check-out berhasil. Pulang lebih awal.');

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

    public static function checkoutBoundaries(): array
    {
        return [
            'Monday early' => ['2026-10-05', '15:59:59', 'Pulang lebih awal'],
            'Tuesday early' => ['2026-10-06', '15:59:59', 'Pulang lebih awal'],
            'Wednesday early' => ['2026-10-07', '15:59:59', 'Pulang lebih awal'],
            'Thursday early' => ['2026-10-08', '15:59:59', 'Pulang lebih awal'],
            'Thursday exact' => ['2026-10-08', '16:00:00', 'Sesuai jam kerja'],
            'Monday after' => ['2026-10-05', '16:00:01', 'Sesuai jam kerja'],
            'Friday 1600' => ['2026-10-09', '16:00:00', 'Pulang lebih awal'],
            'Friday early' => ['2026-10-09', '16:29:59', 'Pulang lebih awal'],
            'Friday exact' => ['2026-10-09', '16:30:00', 'Sesuai jam kerja'],
            'Friday after' => ['2026-10-09', '16:30:01', 'Sesuai jam kerja'],
            'Saturday unspecified' => ['2026-10-10', '15:00:00', null],
            'Sunday unspecified' => ['2026-10-11', '15:00:00', null],
        ];
    }

    #[DataProvider('checkoutBoundaries')]
    public function test_checkout_classification_preserves_actual_time_and_checkin_status(string $date, string $time, ?string $label): void
    {
        Carbon::setTestNow(Carbon::parse($date.' '.$time, 'Asia/Jakarta'));
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id, 'attendance_date' => $date,
            'check_in_time' => '07:40:00', 'check_out_time' => null, 'status' => 'terlambat',
        ]);
        $this->actingAs($user)->post(route('check-out.store'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', $label === 'Pulang lebih awal' ? 'Check-out berhasil. Pulang lebih awal.' : 'Check-out berhasil.');
        $this->assertDatabaseHas('attendances', ['id' => $attendance->id, 'check_out_time' => $time, 'status' => 'terlambat']);
        $this->assertSame($label, $attendance->fresh()->checkoutLabel());
        foreach (['dashboard', 'attendances.index'] as $route) {
            $response = $this->get(route($route))->assertOk()->assertSee($time);
            if ($label !== null) {
                $response->assertSee($label);
            } else {
                $response->assertDontSee('Pulang lebih awal')->assertDontSee('Sesuai jam kerja');
            }
        }
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
