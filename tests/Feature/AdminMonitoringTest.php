<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_monitor_all_employee_attendance_but_not_admin_attendance(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-02 12:00:00', 'Asia/Jakarta'));
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['name' => 'Pegawai Terpantau']);
        $otherEmployee = User::factory()->create(['name' => 'Pegawai Lain']);
        $otherAdmin = User::factory()->create(['name' => 'Admin Lain', 'role' => 'admin']);
        Attendance::factory()->create(['user_id' => $employee->id, 'attendance_date' => '2026-11-02', 'status' => 'hadir']);
        Attendance::factory()->create(['user_id' => $otherEmployee->id, 'attendance_date' => '2026-11-01', 'status' => 'terlambat']);
        Attendance::factory()->create(['user_id' => $otherAdmin->id, 'attendance_date' => '2026-11-02', 'status' => 'hadir']);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Pegawai Terpantau')->assertSee('Pegawai Lain')->assertDontSee('Admin Lain')
            ->assertViewHas('employeeCount', 2)->assertViewHas('todaySummary', ['hadir' => 1, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0]);
        $this->get(route('admin.attendances.index'))->assertOk()
            ->assertSee('Laporan Presensi Pegawai')->assertSee('Pegawai Terpantau')->assertSee('Pegawai Lain')
            ->assertDontSee('Admin Lain')->assertSee('Ekspor PDF')
            ->assertSee('href="'.route('admin.attendances.export.pdf').'"', false);
    }

    public function test_employee_cannot_open_admin_monitoring_or_bypass_with_query_input(): void
    {
        $employee = User::factory()->create();

        $this->actingAs($employee)->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('admin.attendances.index', ['user_id' => 1]))->assertForbidden();
    }

    public function test_admin_login_enters_the_monitoring_dashboard(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $this->post(route('login.authenticate'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_is_redirected_from_personal_pages_and_cannot_submit_attendance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
        $this->get(route('attendances.index'))->assertRedirect(route('admin.attendances.index'));
        $this->get(route('attendances.export.pdf'))->assertRedirect(route('admin.attendances.export.pdf'));
        $this->post(route('check-in.store'))->assertForbidden();
        $this->post(route('check-out.store'))->assertForbidden();
        $this->post(route('absence.store'), ['status' => 'izin'])->assertForbidden();
    }

    public function test_admin_pdf_export_uses_all_filtered_employee_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['name' => 'Pegawai PDF']);
        Attendance::factory()->create(['user_id' => $employee->id, 'attendance_date' => '2026-10-22', 'status' => 'hadir']);

        $this->actingAs($admin)->get(route('admin.attendances.export.pdf', ['status' => 'hadir']))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_admin_report_filters_and_pagination_keep_all_employee_scope(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $matched = User::factory()->create(['name' => 'Nama Dicari']);
        $unmatched = User::factory()->create(['name' => 'Tidak Ditampilkan']);
        Attendance::factory()->create(['user_id' => $matched->id, 'attendance_date' => '2026-10-22', 'status' => 'hadir']);
        Attendance::factory()->create(['user_id' => $unmatched->id, 'attendance_date' => '2026-10-22', 'status' => 'terlambat']);

        $this->actingAs($admin)->get(route('admin.attendances.index', ['name' => 'dicari', 'status' => 'hadir', 'per_page' => 10]))
            ->assertOk()->assertSee('Nama Dicari')->assertDontSee('Tidak Ditampilkan')
            ->assertSee('Total</dt><dd>1', false)->assertSee('href="'.route('admin.attendances.index').'"', false);
    }

    public function test_admin_can_sort_employee_report_by_name_in_both_directions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $zebra = User::factory()->create(['name' => 'Zebra']);
        $alpha = User::factory()->create(['name' => 'Alpha']);
        Attendance::factory()->create(['user_id' => $zebra->id, 'attendance_date' => '2026-10-22']);
        Attendance::factory()->create(['user_id' => $alpha->id, 'attendance_date' => '2026-10-22']);

        $this->actingAs($admin)->get(route('admin.attendances.index', ['sort' => 'name', 'direction' => 'asc']))
            ->assertOk()->assertViewHas('attendances', fn ($rows) => $rows->pluck('user.name')->all() === ['Alpha', 'Zebra']);
        $this->get(route('admin.attendances.index', ['sort' => 'name', 'direction' => 'desc']))
            ->assertOk()->assertViewHas('attendances', fn ($rows) => $rows->pluck('user.name')->all() === ['Zebra', 'Alpha']);
    }
}
