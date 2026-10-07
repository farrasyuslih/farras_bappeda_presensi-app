<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AttendancePaginationTest extends TestCase
{
    use RefreshDatabase;

    private function dataset(): User
    {
        $user = User::factory()->create(['name' => 'Farras Pagination']);
        $date = Carbon::parse('2026-09-01');
        for ($i = 0; $i < 25; $i++) {
            Attendance::factory()->state(['user_id' => $user->id, 'attendance_date' => $date->toDateString()])->hadir()->create();
            $date->addWeekday();
        }
        Attendance::factory()->state(['user_id' => $user->id, 'attendance_date' => '2026-08-31'])->izin()->create();
        Attendance::factory()->state(['user_id' => User::factory()->create(['name' => 'Other Employee'])->id, 'attendance_date' => '2026-09-01'])->hadir()->create();

        return $user;
    }

    private function link($response, string $relation): string
    {
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $links = (new \DOMXPath($document))->query('//a[@rel="'.$relation.'"]');
        $this->assertCount(1, $links);

        return $links->item(0)->getAttribute('href');
    }

    public function test_next_previous_and_last_page_keep_personal_scope_and_order(): void
    {
        $user = $this->dataset();
        $expected = $user->attendances()->orderByDesc('attendance_date')->pluck('id')->all();
        $first = $this->actingAs($user)->get(route('attendances.index'))->assertOk()->assertDontSee('Other Employee');
        $first->assertViewHas('attendances', fn ($rows) => $rows instanceof LengthAwarePaginator && $rows->perPage() === 10 && $rows->total() === 26 && $rows->pluck('id')->all() === array_slice($expected, 0, 10));
        $second = $this->get($this->link($first, 'next'))->assertOk();
        $second->assertViewHas('attendances', fn ($rows) => $rows->currentPage() === 2 && $rows->pluck('id')->all() === array_slice($expected, 10, 10));
        $this->get($this->link($second, 'prev'))->assertOk()->assertViewHas('attendances', fn ($rows) => $rows->pluck('id')->all() === array_slice($expected, 0, 10));
        $last = $this->get($this->link($second, 'next'))->assertOk();
        $last->assertViewHas('attendances', fn ($rows) => $rows->count() === 6 && ! $rows->hasMorePages() && $rows->pluck('id')->all() === array_slice($expected, 20));
        $last->assertDontSee('rel="next"', false);
    }

    public static function queries(): array
    {
        return [
            'search' => [['name' => 'far']],
            'status' => [['status' => 'hadir']],
            'period' => [['month' => '9', 'year' => '2026']],
            'range' => [['start_date' => '2026-09-01', 'end_date' => '2026-10-31']],
            'ascending' => [['sort' => 'date', 'direction' => 'asc']],
            'descending' => [['sort' => 'date', 'direction' => 'desc']],
            'combined' => [['name' => 'far', 'month' => '9', 'year' => '2026', 'status' => 'hadir', 'sort' => 'date', 'direction' => 'asc']],
        ];
    }

    #[DataProvider('queries')]
    public function test_query_survives_navigation_and_results_match_filters(array $query): void
    {
        $user = $this->dataset();
        $before = Attendance::orderBy('id')->get()->toArray();
        $first = $this->actingAs($user)->get(route('attendances.index', $query))->assertOk();
        $url = $this->link($first, 'next');
        parse_str(parse_url($url, PHP_URL_QUERY), $parameters);
        $this->assertSame($query + ['page' => '2'], $parameters);
        $second = $this->get($url)->assertOk();
        $expected = $user->attendances();
        if (isset($query['status'])) {
            $expected->where('status', $query['status']);
        }
        if (isset($query['month'])) {
            $expected->whereMonth('attendance_date', 9)->whereYear('attendance_date', 2026);
        }
        if (isset($query['start_date'])) {
            $expected->whereBetween('attendance_date', [$query['start_date'], $query['end_date']]);
        }
        $ids = $expected->orderBy('attendance_date', $query['direction'] ?? 'desc')->pluck('id')->all();
        $second->assertViewHas('attendances', fn ($rows) => $rows->pluck('id')->all() === array_slice($ids, 10, 10));
        foreach ($query as $key => $value) {
            $second->assertViewHas(in_array($key, ['sort', 'direction']) ? 'sorting' : 'filters', fn ($values) => $values[$key] === $value);
        }
        parse_str(parse_url($this->link($second, 'prev'), PHP_URL_QUERY), $previous);
        $this->assertSame($query + ['page' => '1'], $previous);
        $this->assertSame($before, Attendance::orderBy('id')->get()->toArray());
    }

    public function test_filter_form_sorting_and_reset_drop_current_page(): void
    {
        $user = $this->dataset();
        $response = $this->actingAs($user)->get(route('attendances.index', ['name' => 'far', 'status' => 'hadir', 'page' => 2]))->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertCount(0, $xpath->query('//form[@id="attendance-filters"]//input[@name="page"]'));
        $sort = $xpath->query('//a[@class="sort-header"]')->item(0)->getAttribute('href');
        parse_str(parse_url($sort, PHP_URL_QUERY), $query);
        $this->assertArrayNotHasKey('page', $query);
        $this->get($sort)->assertOk()->assertViewHas('attendances', fn ($rows) => $rows->currentPage() === 1);
        $reset = $xpath->query('//a[@class="reset"]')->item(0)->getAttribute('href');
        $this->assertSame(route('attendances.index'), $reset);
        $this->get($reset)->assertOk()->assertViewHas('attendances', fn ($rows) => $rows->currentPage() === 1 && $rows->total() === 26);
    }

    public function test_page_size_is_validated_and_preserved_with_filters_and_sorting(): void
    {
        $user = $this->dataset();
        $query = ['name' => 'far', 'status' => 'hadir', 'sort' => 'date', 'direction' => 'asc', 'per_page' => '25'];
        $response = $this->actingAs($user)->get(route('attendances.index', $query))->assertOk();
        $response->assertViewHas('attendances', fn ($rows) => $rows->perPage() === 25 && $rows->count() === 25);
        $this->get(route('attendances.index', ['per_page' => 50]))->assertOk()
            ->assertViewHas('attendances', fn ($rows) => $rows->perPage() === 50 && $rows->count() === 26);
        foreach ([1, 100, 'abc', ['10']] as $invalid) {
            $this->get(route('attendances.index', ['per_page' => $invalid]))->assertStatus(422);
        }
        $response = $this->get(route('attendances.index', ['per_page' => 10, 'page' => 2]))->assertOk();
        parse_str(parse_url($this->link($response, 'next'), PHP_URL_QUERY), $parameters);
        $this->assertSame('10', $parameters['per_page']);
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertCount(1, $xpath->query('//a[@aria-label="Pertama"]'));
        $this->assertCount(1, $xpath->query('//a[@aria-label="Terakhir"]'));
        $this->assertCount(1, $xpath->query('//span[@aria-current="page" and text()="2"]'));
        $this->assertCount(0, $xpath->query('//span[@class="page-ellipsis"]'));
        $sort = $xpath->query('//a[@class="sort-header"]')->item(0)->getAttribute('href');
        parse_str(parse_url($sort, PHP_URL_QUERY), $parameters);
        $this->assertSame('10', $parameters['per_page']);
        $this->assertArrayNotHasKey('page', $parameters);
    }

    public function test_empty_results_and_out_of_range_page_are_distinct(): void
    {
        $user = $this->dataset();
        $this->actingAs($user)->get(route('attendances.index', ['name' => 'no match']))->assertOk()
            ->assertSee('Tidak ada presensi yang cocok dengan filter.')->assertDontSee('aria-label="Navigasi halaman presensi"', false);
        $this->get(route('attendances.index', ['exact_date' => '2026-09-01']))->assertOk()
            ->assertViewHas('attendances', fn ($rows) => $rows->total() === 1 && $rows->count() === 1);
        $response = $this->get(route('attendances.index', ['page' => 4]))->assertOk()
            ->assertSee('Tidak ada presensi pada halaman ini.')->assertDontSee('Belum ada data presensi.');
        $this->get($this->link($response, 'prev'))->assertOk()->assertViewHas('attendances', fn ($rows) => $rows->count() === 6);
        $this->get(route('attendances.index', ['status' => 'invalid', 'page' => 2]))->assertStatus(422);
        $this->actingAs(User::factory()->create())->get(route('attendances.index'))->assertOk()
            ->assertSee('Belum ada data presensi.')->assertDontSee('aria-label="Navigasi halaman presensi"', false);
    }
}
