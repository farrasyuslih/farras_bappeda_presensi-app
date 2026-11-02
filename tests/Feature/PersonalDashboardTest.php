<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PersonalDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_dashboard_requires_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_dashboard_scopes_counts_chart_and_recent_records_to_current_user(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-27 12:00:00', 'Asia/Jakarta'));
        $user = User::factory()->create();
        $other = User::factory()->create(['name' => 'Private Other Employee']);
        foreach (['2026-10-01' => 'hadir', '2026-10-22' => 'terlambat', '2026-10-23' => 'izin', '2026-10-24' => 'sakit', '2026-09-30' => 'hadir', '2026-10-28' => 'hadir'] as $date => $status) {
            Attendance::factory()->create(['user_id' => $user->id, 'attendance_date' => $date, 'status' => $status]);
        }
        Attendance::factory()->create(['user_id' => $other->id, 'attendance_date' => '2026-10-27', 'status' => 'hadir']);
        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertViewHas('summary', ['hadir' => 1, 'terlambat' => 1, 'izin' => 1, 'sakit' => 1])
            ->assertViewHas('onTime', 50.0)
            ->assertViewHas('todayAttendance', null)
            ->assertViewHas('week', fn ($week) => $week->count() === 7 && $week->first()['date'] === '2026-10-21' && $week->last()['status'] === null)
            ->assertViewHas('recentAttendances', fn ($records) => $records->every(fn ($row) => $row->user_id === $user->id))
            ->assertDontSee('Private Other Employee');
    }

    public function test_empty_dashboard_does_not_claim_zero_percent_punctuality(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()
            ->assertViewHas('onTime', null)->assertSee('Belum ada data check-in bulan ini.')
            ->assertSee('Belum ada riwayat presensi.');
    }

    public function test_recent_records_are_limited_to_ten_and_sorted_by_attendance_date(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 12) as $day) {
            Attendance::factory()->create(['user_id' => $user->id, 'attendance_date' => sprintf('2026-10-%02d', $day)]);
        }
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertViewHas('recentAttendances', fn ($records) => $records->count() === 10 && $records->first()->attendance_date === '2026-10-12' && $records->last()->attendance_date === '2026-10-03');
    }

    public function test_week_and_month_use_jakarta_date_at_month_boundary(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-31 18:00:00', 'UTC'));
        $user = User::factory()->create();
        Attendance::factory()->create(['user_id' => $user->id, 'attendance_date' => '2026-11-01', 'status' => 'hadir']);
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertViewHas('summary', ['hadir' => 1, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0])->assertViewHas('week', fn ($week) => $week->last()['date'] === '2026-11-01')->assertViewHas('todayAttendance', fn ($row) => $row?->attendance_date === '2026-11-01');
    }
}
