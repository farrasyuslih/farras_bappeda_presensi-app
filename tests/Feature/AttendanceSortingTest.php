<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AttendanceSortingTest extends TestCase
{
    use RefreshDatabase;

    private function dataset(): User
    {
        $user = User::factory()->create(['name' => 'Farras']);
        foreach (['2026-09-30', '2026-09-01', '2026-10-01'] as $date) {
            Attendance::factory()->create(['user_id' => $user->id, 'attendance_date' => $date, 'status' => 'hadir']);
        }
        Attendance::factory()->create(['user_id' => User::factory()->create(['name' => 'A Other'])->id, 'attendance_date' => '2026-09-15']);

        return $user;
    }

    public static function sortingCases(): array
    {
        $asc = ['2026-09-01', '2026-09-30', '2026-10-01'];
        $desc = array_reverse($asc);

        return [
            'date asc' => [['sort' => 'date', 'direction' => 'asc'], $asc],
            'date desc' => [['sort' => 'date', 'direction' => 'desc'], $desc],
            'search' => [['sort' => 'date', 'direction' => 'asc', 'name' => 'far'], $asc],
            'single filter' => [['sort' => 'date', 'direction' => 'asc', 'month' => '9'], array_slice($asc, 0, 2)],
            'combined filters' => [['sort' => 'date', 'direction' => 'asc', 'name' => 'far', 'month' => '9', 'year' => '2026', 'status' => 'hadir'], array_slice($asc, 0, 2)],
            'date with range' => [['sort' => 'date', 'direction' => 'desc', 'start_date' => '2026-09-01', 'end_date' => '2026-09-30'], ['2026-09-30', '2026-09-01']],
            'direction alone' => [['direction' => 'asc'], $asc],
            'field alone' => [['sort' => 'date'], $desc],
            'empty sorting' => [['sort' => '', 'direction' => ''], $desc],
        ];
    }

    #[DataProvider('sortingCases')]
    public function test_sorting_preserves_filters_scope_and_database(array $input, array $dates): void
    {
        $user = $this->dataset();
        $before = Attendance::orderBy('id')->get()->toArray();
        DB::enableQueryLog();
        $response = $this->actingAs($user)->get(route('attendances.index', $input))->assertOk();
        $response->assertViewHas('attendances', fn ($rows) => $rows->pluck('attendance_date')->all() === $dates && $rows->every(fn ($row) => $row->user_id === $user->id));
        foreach ($input as $key => $value) {
            $response->assertViewHas(in_array($key, ['sort', 'direction']) ? 'sorting' : 'filters', fn ($values) => $values[$key] === $value);
        }
        DB::disableQueryLog();
        $this->assertSame($before, Attendance::orderBy('id')->get()->toArray());
    }

    public static function invalidSorting(): array
    {
        return [
            [['sort' => 'status'], 'sort'],
            [['sort' => 'name'], 'sort'],
            [['sort' => 'attendance_date desc; DROP TABLE users'], 'sort'],
            [['sort' => ['date']], 'sort'],
            [['direction' => 'up'], 'direction'],
            [['direction' => 'ASC'], 'direction'],
            [['direction' => ['asc']], 'direction'],
        ];
    }

    #[DataProvider('invalidSorting')]
    public function test_invalid_sorting_stops_attendance_query(array $input, string $key): void
    {
        $user = $this->dataset();
        DB::enableQueryLog();
        $response = $this->actingAs($user)->get(route('attendances.index', $input))->assertStatus(422);
        $response->assertViewHas('filterErrors', fn ($errors) => $errors->has($key));
        $response->assertViewHas('sorting', fn ($values) => $values[$key] === (is_array($input[$key]) ? '' : $input[$key]));
        $this->assertFalse(collect(DB::getQueryLog())->contains(fn ($entry) => str_contains($entry['query'], 'from "attendances"')));
        DB::disableQueryLog();
    }

    public function test_single_reset_clears_filters_and_sorting_and_restores_default(): void
    {
        $user = $this->dataset();
        $input = ['name' => 'Far', 'month' => '9', 'year' => '2026', 'status' => 'hadir', 'sort' => 'date', 'direction' => 'asc'];
        $response = $this->actingAs($user)->get(route('attendances.index', $input))->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $links = (new \DOMXPath($document))->query('//a[@class="reset"]');
        $this->assertCount(1, $links);
        $this->assertSame('Reset', $links->item(0)->textContent);
        $this->assertSame('Hapus semua filter dan kembalikan urutan awal', $links->item(0)->getAttribute('title'));
        $this->assertSame(route('attendances.index'), $links->item(0)->getAttribute('href'));
        $this->get($links->item(0)->getAttribute('href'))->assertOk()
            ->assertViewHas('sorting', fn ($sorting) => $sorting === ['sort' => '', 'direction' => ''])
            ->assertViewHas('filters', fn ($values) => collect($values)->every(fn ($value) => $value === ''))
            ->assertViewHas('attendances', fn ($rows) => $rows->pluck('attendance_date')->all() === ['2026-10-01', '2026-09-30', '2026-09-01'])
            ->assertSee('Terbaru');
    }

    public function test_sorting_on_empty_history_keeps_original_empty_state(): void
    {
        $this->actingAs(User::factory()->create())->get(route('attendances.index', ['sort' => 'date', 'direction' => 'asc']))
            ->assertOk()->assertSee('Belum ada data presensi.');
    }

    private function headerUrl($response, string $label): string
    {
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($document);
        $links = $xpath->query('//a[@class="sort-header" and text()="'.$label.'"]');
        $this->assertCount(1, $links);

        return $links->item(0)->getAttribute('href');
    }

    public function test_header_clicks_toggle_order_preserve_filters_and_show_active_caption(): void
    {
        $user = $this->dataset();
        $filters = ['name' => 'Far', 'month' => '9', 'year' => '2026', 'status' => 'hadir'];
        $response = $this->actingAs($user)->get(route('attendances.index', $filters))->assertOk()
            ->assertViewIs('attendances.list_presensi')
            ->assertSee('aria-sort="descending"', false)->assertSee('Terbaru')
            ->assertDontSee('<select name="sort"', false)->assertDontSee('<select name="direction"', false);
        $url = $this->headerUrl($response, 'Tanggal');
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame($filters + ['sort' => 'date', 'direction' => 'asc'], $query);
        $response = $this->get($url)->assertOk()
            ->assertSee('class="dir-u" aria-sort="ascending"', false)->assertSee('Terlama')
            ->assertSee('type="hidden" name="direction" value="asc"', false)
            ->assertViewHas('attendances', fn ($rows) => $rows->pluck('attendance_date')->all() === ['2026-09-01', '2026-09-30']);
        $response = $this->get($this->headerUrl($response, 'Tanggal'))->assertOk()
            ->assertSee('class="dir-d" aria-sort="descending"', false)
            ->assertViewHas('attendances', fn ($rows) => $rows->pluck('attendance_date')->all() === ['2026-09-30', '2026-09-01']);
        $this->assertSame(0, substr_count($response->getContent(), 'aria-label="Urutkan Nama'));
        $response->assertViewHas('filters', fn ($values) => $values['name'] === 'Far' && $values['month'] === '9' && $values['status'] === 'hadir');
    }

    public function test_dashboard_uses_renamed_presensi_view(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()->assertViewIs('presensi');
    }

    public function test_guest_cannot_sort_history(): void
    {
        $this->get(route('attendances.index', ['sort' => 'date', 'direction' => 'asc']))->assertRedirect(route('login'));
    }
}
