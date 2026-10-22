<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_registration_to_report_and_logout_preserves_identity_and_daily_rules(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14 07:30:00', 'Asia/Jakarta'));
        $this->post(route('register.store'), [
            'name' => 'QA Integration', 'email' => 'integration@example.test',
            'password' => 'password123', 'password_confirmation' => 'password123', 'role' => 'admin',
        ])->assertRedirect(route('login'));
        $user = User::where('email', 'integration@example.test')->firstOrFail();
        $this->assertSame('pegawai', $user->role);
        $this->post(route('login.authenticate'), ['email' => $user->email, 'password' => 'password123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboard'))->assertSee('Belum ada aktivitas presensi');
        $this->post(route('check-out.store'))->assertSessionHasErrors('check_out');
        $this->post(route('check-in.store'))->assertSessionHasNoErrors();
        $this->post(route('check-in.store'))->assertSessionHasErrors('check_in');
        $this->post(route('absence.store'), ['status' => 'izin'])->assertSessionHasErrors('status');
        Carbon::setTestNow(Carbon::parse('2026-10-14 16:00:00', 'Asia/Jakarta'));
        $this->post(route('check-out.store'))->assertSessionHasNoErrors();
        $this->post(route('check-out.store'))->assertSessionHasErrors('check_out');
        $this->assertSame(1, $user->attendances()->count());
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id, 'attendance_date' => '2026-10-14',
            'check_in_time' => '07:30:00', 'check_out_time' => '16:00:00', 'status' => 'hadir',
        ]);
        $filters = ['name' => 'QA', 'exact_date' => '2026-10-14', 'status' => 'hadir', 'direction' => 'asc'];
        $this->get(route('attendances.index', $filters))->assertOk()->assertSee('16:00:00')
            ->assertViewHas('summary', ['total' => 1, 'hadir' => 1, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0]);
        $pdf = $this->get(route('attendances.export.pdf', $filters))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
        foreach (['dashboard', 'attendances.index', 'attendances.export.pdf'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
        foreach (['check-in.store', 'check-out.store', 'absence.store'] as $route) {
            $this->post(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_next_day_absence_and_account_switch_keep_dashboard_report_and_pdf_private(): void
    {
        $owner = User::factory()->create(['name' => 'Owner Integration']);
        $other = User::factory()->create(['name' => 'Other Integration']);
        $this->post(route('login.authenticate'), ['email' => $owner->email, 'password' => 'password'])->assertSessionHasNoErrors();
        foreach (['2026-10-14' => 'izin', '2026-10-15' => 'sakit'] as $date => $status) {
            Carbon::setTestNow(Carbon::parse($date.' 07:00:00', 'Asia/Jakarta'));
            $this->get(route('dashboard'))->assertSee('Belum ada aktivitas presensi');
            $this->post(route('absence.store'), ['status' => $status])->assertSessionHasNoErrors();
            $this->post(route('check-in.store'))->assertSessionHasErrors('check_in');
            $this->post(route('check-out.store'))->assertSessionHasErrors('check_out');
            $this->get(route('dashboard'))->assertSee(ucfirst($status).' tercatat')->assertDontSee('Check-out pukul');
            $this->assertDatabaseHas('attendances', ['user_id' => $owner->id, 'attendance_date' => $date,
                'status' => $status, 'check_in_time' => null, 'check_out_time' => null]);
        }
        $before = $owner->attendances()->orderBy('id')->get()->toArray();
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->post(route('login.authenticate'), ['email' => $other->email, 'password' => 'password'])->assertSessionHasNoErrors();
        $this->get(route('dashboard'))->assertSee('Belum ada aktivitas presensi');
        $this->get(route('attendances.index', ['user_id' => $owner->id]))->assertOk()
            ->assertDontSee($owner->name)->assertViewHas('summary', ['total' => 0, 'hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0]);
        $this->get(route('attendances.export.pdf', ['user_id' => $owner->id]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->post(route('check-out.store'))->assertSessionHasErrors('check_out');
        $this->post(route('check-in.store'), ['user_id' => $owner->id])->assertSessionHasErrors('user_id');
        $this->assertSame($before, $owner->attendances()->orderBy('id')->get()->toArray());
        $this->assertSame(0, $other->attendances()->count());
    }

    public function test_filtered_paginated_summary_and_pdf_share_all_personal_rows(): void
    {
        $user = User::factory()->create(['name' => 'Integration Report']);
        $expected = [];
        foreach (range(1, 12) as $day) {
            $expected[] = Attendance::factory()->hadir()->create([
                'user_id' => $user->id, 'attendance_date' => sprintf('2026-09-%02d', $day),
            ])->id;
        }
        Attendance::factory()->sakit()->create(['user_id' => $user->id, 'attendance_date' => '2026-09-13']);
        Attendance::factory()->hadir()->create(['attendance_date' => '2026-09-01']);
        $before = Attendance::orderBy('id')->get()->toArray();
        $filters = ['name' => 'Integration', 'month' => 9, 'year' => 2026, 'status' => 'hadir',
            'sort' => 'date', 'direction' => 'asc', 'per_page' => 10, 'page' => 2];
        $web = $this->actingAs($user)->get(route('attendances.index', $filters))->assertOk();
        $this->assertSame(array_slice($expected, 10), $web->viewData('attendances')->pluck('id')->all());
        $web->assertViewHas('summary', ['total' => 12, 'hadir' => 12, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0]);
        Pdf::shouldReceive('loadView')->once()->withArgs(function ($view, $data) use ($expected, $user) {
            $this->assertSame($expected, $data['attendances']->pluck('id')->all());
            $this->assertSame($user->id, $data['employee']->id);

            return $view === 'attendances.pdf';
        })->andReturnSelf();
        Pdf::shouldReceive('setPaper')->with('a4')->andReturnSelf();
        Pdf::shouldReceive('setOption')->andReturnSelf();
        Pdf::shouldReceive('download')->once()->andReturn(response('PDF', 200, ['Content-Type' => 'application/pdf']));
        $this->get(route('attendances.export.pdf', $filters))->assertOk();
        $this->get(route('attendances.index', ['status' => 'invalid']))->assertStatus(422);
        $this->assertSame($before, Attendance::orderBy('id')->get()->toArray());
    }
}
