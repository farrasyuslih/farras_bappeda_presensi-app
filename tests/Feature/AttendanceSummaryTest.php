<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AttendanceSummaryTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('filterCases')]
    public function test_summary_matches_entire_filtered_personal_dataset(array $filters, array $expected): void
    {
        $user = User::factory()->create(['name' => 'Farras Summary']);
        foreach (['2025-09-15' => 'hadir', '2026-08-31' => 'sakit', '2026-09-01' => 'hadir', '2026-09-15' => 'terlambat', '2026-09-30' => 'izin', '2026-10-01' => 'hadir'] as $date => $status) {
            Attendance::factory()->create(['user_id' => $user->id, 'attendance_date' => $date, 'status' => $status]);
        }
        Attendance::factory()->create(['attendance_date' => '2026-09-01', 'status' => 'hadir']);
        $response = $this->actingAs($user)->get(route('attendances.index', $filters))->assertOk();
        $summary = array_combine(['total', 'hadir', 'terlambat', 'izin', 'sakit'], $expected);
        $response->assertViewHas('summary', $summary)->assertSee('aria-label="Rekap presensi"', false);
        $response->assertViewHas('attendances', fn ($rows) => $rows->total() === $summary['total']);
        foreach ($summary as $key => $count) {
            $label = $key === 'total' ? 'Total' : ucfirst($key);
            $response->assertSee('<dt>'.$label.'</dt><dd>'.$count.'</dd>', false);
        }
    }

    public static function filterCases(): array
    {
        return [
            'all personal records' => [[], [6, 3, 1, 1, 1]],
            'partial search' => [['name' => 'farras'], [6, 3, 1, 1, 1]],
            'literal wildcard' => [['name' => '%'], [0, 0, 0, 0, 0]],
            'search no result' => [['name' => 'Unknown'], [0, 0, 0, 0, 0]],
            'hadir' => [['status' => 'hadir'], [3, 3, 0, 0, 0]],
            'terlambat' => [['status' => 'terlambat'], [1, 0, 1, 0, 0]],
            'izin' => [['status' => 'izin'], [1, 0, 0, 1, 0]],
            'sakit' => [['status' => 'sakit'], [1, 0, 0, 0, 1]],
            'exact date' => [['exact_date' => '2026-09-15'], [1, 0, 1, 0, 0]],
            'inclusive range' => [['start_date' => '2026-09-01', 'end_date' => '2026-09-30'], [3, 1, 1, 1, 0]],
            'start only' => [['start_date' => '2026-09-30'], [2, 1, 0, 1, 0]],
            'end only' => [['end_date' => '2025-09-15'], [1, 1, 0, 0, 0]],
            'month only' => [['month' => '9'], [4, 2, 1, 1, 0]],
            'year only' => [['year' => '2025'], [1, 1, 0, 0, 0]],
            'month year' => [['month' => '9', 'year' => '2026'], [3, 1, 1, 1, 0]],
            'combined' => [['name' => 'Farras', 'month' => '9', 'year' => '2026', 'status' => 'izin'], [1, 0, 0, 1, 0]],
            'combined zero' => [['exact_date' => '2026-09-15', 'status' => 'hadir'], [0, 0, 0, 0, 0]],
        ];
    }

    public function test_pagination_page_size_and_sorting_do_not_change_filtered_summary(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 28) as $day) {
            Attendance::factory()->create(['user_id' => $user->id, 'attendance_date' => sprintf('2026-09-%02d', $day), 'status' => $day <= 26 ? 'hadir' : 'izin']);
        }
        Attendance::factory()->create(['user_id' => $user->id, 'attendance_date' => '2026-10-01', 'status' => 'sakit']);
        Attendance::factory()->create(['attendance_date' => '2026-09-01', 'status' => 'hadir']);
        $this->actingAs($user);
        foreach ([[10, 1], [10, 3], [25, 2], [50, 1], [100, 1]] as [$size, $page]) {
            foreach (['asc', 'desc'] as $direction) {
                $this->get(route('attendances.index', ['month' => 9, 'year' => 2026, 'status' => 'hadir', 'per_page' => $size, 'page' => $page, 'sort' => 'date', 'direction' => $direction]))
                    ->assertOk()->assertViewHas('summary', ['total' => 26, 'hadir' => 26, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0])
                    ->assertViewHas('attendances', fn ($rows) => $rows->total() === 26 && $rows->count() === min($size, 26 - ($page - 1) * $size));
            }
        }
    }

    public function test_empty_history_shows_zero_summary(): void
    {
        $this->actingAs(User::factory()->create())->get(route('attendances.index'))->assertOk()
            ->assertViewHas('summary', ['total' => 0, 'hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0])
            ->assertSee('Belum ada data presensi.')->assertSee('aria-label="Rekap presensi"', false);
    }

    public function test_invalid_filter_or_page_does_not_display_summary(): void
    {
        $this->actingAs(User::factory()->create());
        foreach ([['status' => 'invalid'], ['page' => 2], ['per_page' => [10]]] as $filters) {
            $this->get(route('attendances.index', $filters))->assertStatus(422)
                ->assertDontSee('aria-label="Rekap presensi"', false);
        }
    }
}
