<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AttendancePdfTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('filterCases')]
    public function test_pdf_dataset_matches_web_report(array $filters, array $counts): void
    {
        $user = User::factory()->create(['name' => 'Farras Summary']);
        foreach (['2025-09-15' => 'hadir', '2026-08-31' => 'sakit', '2026-09-01' => 'hadir', '2026-09-15' => 'terlambat', '2026-09-30' => 'izin', '2026-10-01' => 'hadir'] as $date => $status) {
            Attendance::factory()->create(['user_id' => $user->id, 'attendance_date' => $date, 'status' => $status]);
        }
        Attendance::factory()->create(['attendance_date' => '2026-09-01']);
        $web = $this->actingAs($user)->get(route('attendances.index', $filters))->assertOk();
        $expected = $web->viewData('attendances')->pluck('id')->all();
        Pdf::shouldReceive('loadView')->once()->withArgs(function ($view, $data) use ($expected, $counts, $user) {
            $this->assertSame('attendances.pdf', $view);
            $this->assertSame($expected, $data['attendances']->pluck('id')->all());
            $this->assertCount($counts[0], $data['attendances']);
            $this->assertTrue($data['attendances']->every(fn ($row) => $row->user_id === $user->id));

            return true;
        })->andReturnSelf();
        Pdf::shouldReceive('setPaper')->with('a4')->andReturnSelf();
        Pdf::shouldReceive('setOption')->andReturnSelf();
        Pdf::shouldReceive('download')->once()->andReturn(response('PDF', 200, ['Content-Type' => 'application/pdf']));
        $this->get(route('attendances.export.pdf', $filters))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public static function filterCases(): array
    {
        return AttendanceSummaryTest::filterCases();
    }

    public function test_pdf_contains_all_pages_and_respects_sorting(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 28) as $day) {
            Attendance::factory()->create(['user_id' => $user->id, 'attendance_date' => sprintf('2026-09-%02d', $day)]);
        }
        Pdf::shouldReceive('loadView')->once()->withArgs(function ($view, $data) {
            $this->assertCount(28, $data['attendances']);
            $this->assertSame('2026-09-01', $data['attendances']->first()->attendance_date);
            $this->assertSame('2026-09-28', $data['attendances']->last()->attendance_date);

            return true;
        })->andReturnSelf();
        Pdf::shouldReceive('setPaper')->andReturnSelf();
        Pdf::shouldReceive('setOption')->andReturnSelf();
        Pdf::shouldReceive('download')->andReturn(response('PDF'));
        $this->actingAs($user)->get(route('attendances.export.pdf', ['page' => 3, 'per_page' => 10, 'direction' => 'asc']))->assertOk();
    }

    public function test_guest_and_invalid_export_requests_are_rejected(): void
    {
        $this->get(route('attendances.export.pdf'))->assertRedirect(route('login'));
        Pdf::shouldReceive('loadView')->never();
        $this->actingAs(User::factory()->create());
        foreach ([['status' => 'invalid'], ['name' => ['x']], ['direction' => 'invalid'], ['per_page' => [10]], ['exact_date' => '2026-09-01', 'month' => 9], ['start_date' => '2026-10-01', 'end_date' => '2026-09-01']] as $filters) {
            $this->get(route('attendances.export.pdf', $filters))->assertStatus(422)->assertDontSee('Ekspor PDF');
        }
    }

    public function test_real_pdf_download_and_empty_template(): void
    {
        $user = User::factory()->create(['name' => '<Pegawai>']);
        $response = $this->actingAs($user)->get(route('attendances.export.pdf'))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('laporan-presensi-'.$user->id.'-', $response->headers->get('Content-Disposition'));
        $html = view('attendances.pdf', ['attendances' => collect(), 'employee' => $user, 'generatedAt' => now(), 'filters' => [], 'sorting' => ['direction' => '']])->render();
        $this->assertStringContainsString('Tidak ada data presensi sesuai filter.', $html);
        $this->assertStringContainsString('&lt;Pegawai&gt;', $html);
    }
}
