<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Database\Seeders\AttendanceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceSeedConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_states_match_checkin_and_checkout_rules_on_monday_and_friday(): void
    {
        $user = User::factory()->create();
        foreach (['2026-10-05' => '16:00:00', '2026-10-09' => '16:30:00'] as $date => $end) {
            foreach (['hadir', 'terlambat'] as $status) {
                $attendance = Attendance::factory()->state(['attendance_date' => $date])->{$status}()->create(['user_id' => $user->id]);
                $this->assertSame($status, $attendance->check_in_time <= '07:30:00' ? 'hadir' : 'terlambat');
                $this->assertGreaterThanOrEqual($end, $attendance->check_out_time);
                $this->assertSame('Sesuai jam kerja', $attendance->checkoutLabel());
                $attendance->delete();
            }
        }
    }

    public function test_seeded_records_follow_business_rules(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-06 12:00:00', 'Asia/Jakarta'));
        try {
            User::factory(10)->create();
            $this->seed(AttendanceSeeder::class);
            $rows = Attendance::all();
            $this->assertCount(100, $rows);
            foreach ($rows as $attendance) {
                $day = Carbon::parse($attendance->attendance_date, 'Asia/Jakarta')->dayOfWeekIso;
                $this->assertLessThanOrEqual(5, $day);
                if (in_array($attendance->status, ['izin', 'sakit'], true)) {
                    $this->assertNull($attendance->check_in_time);
                    $this->assertNull($attendance->check_out_time);

                    continue;
                }
                $this->assertSame($attendance->check_in_time <= '07:30:00' ? 'hadir' : 'terlambat', $attendance->status);
                $this->assertSame('Sesuai jam kerja', $attendance->checkoutLabel());
            }
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_early_checkout_is_a_separate_label_from_checkin_status(): void
    {
        $attendance = Attendance::factory()->create([
            'attendance_date' => '2026-10-05',
            'check_in_time' => '07:21:25',
            'check_out_time' => '15:21:25',
            'status' => 'hadir',
        ]);
        $this->assertSame('hadir', $attendance->status);
        $this->assertSame('Pulang lebih awal', $attendance->checkoutLabel());
    }
}
