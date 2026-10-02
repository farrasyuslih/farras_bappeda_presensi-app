<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AttendanceFilterTest extends TestCase
{
    use RefreshDatabase;

    private function dataset(): User
    {
        $user = User::factory()->create(['name' => 'Muhammad Farras Yuslih']);
        foreach (['2025-09-15' => 'hadir', '2026-08-31' => 'sakit', '2026-09-01' => 'hadir', '2026-09-15' => 'terlambat', '2026-09-30' => 'izin', '2026-10-01' => 'hadir'] as $date => $status) {
            Attendance::factory()->create(['user_id' => $user->id, 'attendance_date' => $date, 'status' => $status]);
        }
        $other = User::factory()->create(['name' => 'Farras Pegawai Lain']);
        Attendance::factory()->create(['user_id' => $other->id, 'attendance_date' => '2026-09-02', 'status' => 'hadir']);

        return $user;
    }

    public static function validFilters(): array
    {
        return [
            'no filter' => [[], ['2026-10-01', '2026-09-30', '2026-09-15', '2026-09-01', '2026-08-31', '2025-09-15']],
            'partial name' => [['name' => 'farras'], ['2026-10-01', '2026-09-30', '2026-09-15', '2026-09-01', '2026-08-31', '2025-09-15']],
            'exact date' => [['exact_date' => '2026-09-15'], ['2026-09-15']],
            'inclusive range' => [['start_date' => '2026-09-01', 'end_date' => '2026-09-30'], ['2026-09-30', '2026-09-15', '2026-09-01']],
            'start only' => [['start_date' => '2026-09-30'], ['2026-10-01', '2026-09-30']],
            'end only' => [['end_date' => '2025-09-15'], ['2025-09-15']],
            'same boundaries' => [['start_date' => '2026-09-15', 'end_date' => '2026-09-15'], ['2026-09-15']],
            'month only' => [['month' => '9'], ['2026-09-30', '2026-09-15', '2026-09-01', '2025-09-15']],
            'year only' => [['year' => '2025'], ['2025-09-15']],
            'month and year' => [['month' => '9', 'year' => '2026'], ['2026-09-30', '2026-09-15', '2026-09-01']],
            'status hadir' => [['status' => 'hadir'], ['2026-10-01', '2026-09-01', '2025-09-15']],
            'status terlambat' => [['status' => 'terlambat'], ['2026-09-15']],
            'status izin' => [['status' => 'izin'], ['2026-09-30']],
            'status sakit' => [['status' => 'sakit'], ['2026-08-31']],
            'combined month' => [['name' => 'Farras', 'month' => '9', 'year' => '2026', 'status' => 'hadir'], ['2026-09-01']],
            'combined exact' => [['exact_date' => '2026-09-15', 'status' => 'terlambat'], ['2026-09-15']],
            'combined range' => [['name' => 'Farras', 'start_date' => '2026-09-01', 'end_date' => '2026-09-30', 'status' => 'izin'], ['2026-09-30']],
            'zero name' => [['name' => 'Pegawai Lain'], []],
            'zero date' => [['exact_date' => '2026-09-02'], []],
            'zero combined' => [['exact_date' => '2026-09-15', 'status' => 'hadir'], []],
            'literal wildcard' => [['name' => '%'], []],
            'empty inputs' => [['name' => '', 'exact_date' => '', 'start_date' => '', 'end_date' => '', 'month' => '', 'year' => '', 'status' => ''], ['2026-10-01', '2026-09-30', '2026-09-15', '2026-09-01', '2026-08-31', '2025-09-15']],
        ];
    }

    #[DataProvider('validFilters')]
    public function test_filters_preserve_scope_order_input_and_database(array $filters, array $dates): void
    {
        $user = $this->dataset();
        $before = Attendance::orderBy('id')->get()->toArray();
        $response = $this->actingAs($user)->get(route('attendances.index', $filters))->assertOk();
        $response->assertViewHas('attendances', fn ($rows) => $rows->pluck('attendance_date')->all() === $dates && $rows->every(fn ($row) => $row->user_id === $user->id));
        foreach ($filters as $key => $value) {
            $response->assertViewHas('filters', fn ($values) => $values[$key] === $value);
        }
        if ($dates === []) {
            $response->assertSee('Tidak ada presensi yang cocok dengan filter.');
        }
        $this->assertSame($before, Attendance::orderBy('id')->get()->toArray());
    }

    public static function invalidFilters(): array
    {
        return [
            [['exact_date' => '2026-02-30'], 'exact_date'],
            [['start_date' => 'bad'], 'start_date'],
            [['end_date' => '2026-13-01'], 'end_date'],
            [['start_date' => '2026-09-30', 'end_date' => '2026-09-01'], 'end_date'],
            [['month' => '13'], 'month'], [['month' => '0'], 'month'],
            [['year' => 'abc'], 'year'], [['year' => '10000'], 'year'],
            [['status' => 'admin'], 'status'], [['name' => str_repeat('a', 256)], 'name'],
            [['name' => ['Farras']], 'name'], [['month' => ['9']], 'month'],
            [['exact_date' => '2026-09-15', 'start_date' => '2026-09-01'], 'date_mode'],
            [['exact_date' => '2026-09-15', 'month' => '9'], 'date_mode'],
            [['end_date' => '2026-09-30', 'year' => '2026'], 'date_mode'],
            [['exact_date' => '2026-09-15', 'year' => '2026'], 'date_mode'],
        ];
    }

    #[DataProvider('invalidFilters')]
    public function test_invalid_input_is_visible_and_does_not_query_or_change_attendance(array $filters, string $error): void
    {
        $user = $this->dataset();
        $before = Attendance::orderBy('id')->get()->toArray();
        $response = $this->actingAs($user)->get(route('attendances.index', $filters))
            ->assertStatus(422)->assertSee('Filter belum diterapkan.');
        $response->assertViewHas('filterErrors', fn ($errors) => $errors->has($error));
        $response->assertViewHas('attendances', fn ($rows) => $rows->isEmpty());
        foreach ($filters as $key => $value) {
            if (is_string($value)) {
                $response->assertSee('value="'.e($value).'"', false);
            }
        }
        $this->assertSame($before, Attendance::orderBy('id')->get()->toArray());
    }

    public function test_reset_link_returns_unfiltered_listing(): void
    {
        $user = $this->dataset();
        $this->actingAs($user)->get(route('attendances.index', ['status' => 'izin']))
            ->assertSee('href="'.route('attendances.index').'">Reset filter', false);
        $this->get(route('attendances.index'))->assertOk()
            ->assertViewHas('attendances', fn ($rows) => $rows->count() === 6)
            ->assertViewHas('filters', fn ($filters) => collect($filters)->every(fn ($value) => $value === ''));
    }

    public function test_guest_cannot_access_filtered_listing(): void
    {
        $this->get(route('attendances.index', ['status' => 'hadir']))->assertRedirect(route('login'));
    }
}
