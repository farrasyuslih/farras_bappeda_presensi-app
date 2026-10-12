<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_page_input_returns_clear_error_without_changing_data(): void
    {
        $user = User::factory()->create();
        foreach ([0, -1, 'abc', '1.5', ['2'], '99999999999999999999'] as $page) {
            $this->actingAs($user)->get(route('attendances.index', ['page' => $page]))
                ->assertStatus(422)->assertSee('Nomor halaman')->assertDontSee('Belum ada data presensi.');
        }
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_page_limits_follow_personal_filters_and_page_size(): void
    {
        $user = User::factory()->create();
        for ($day = 1; $day <= 12; $day++) {
            Attendance::factory()->create([
                'user_id' => $user->id,
                'attendance_date' => sprintf('2026-09-%02d', $day),
                'status' => 'hadir',
            ]);
        }
        $this->actingAs($user)->get(route('attendances.index', ['page' => 2]))->assertOk();
        foreach ([['page' => 100], ['page' => 2, 'per_page' => 100], ['page' => 2, 'status' => 'izin']] as $query) {
            $this->get(route('attendances.index', $query))->assertStatus(422)
                ->assertSee('Nomor halaman tidak tersedia.')
                ->assertDontSee('aria-label="Navigasi halaman presensi"', false);
        }
        $this->get(route('attendances.index', ['per_page' => 100]))->assertOk()
            ->assertViewHas('attendances', fn ($rows) => $rows->perPage() === 100 && $rows->count() === 12);
        $this->assertDatabaseCount('attendances', 12);
    }

    public function test_array_inputs_are_rejected_across_authentication_and_filters(): void
    {
        $this->post(route('login.authenticate'), ['email' => ['x'], 'password' => ['x']])
            ->assertSessionHasErrors(['email', 'password']);
        $this->post(route('register.store'), ['name' => ['x'], 'email' => ['x'], 'password' => ['12345678'], 'password_confirmation' => ['12345678']])
            ->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertDatabaseCount('users', 0);
        $user = User::factory()->create();
        foreach (['name', 'exact_date', 'start_date', 'end_date', 'month', 'year', 'status', 'sort', 'direction', 'per_page'] as $key) {
            $this->actingAs($user)->get(route('attendances.index', [$key => ['invalid']]))
                ->assertStatus(422)->assertSee('Filter belum diterapkan.');
        }
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_absence_errors_and_dashboard_feedback_are_explicit(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->from(route('dashboard'))->post(route('absence.store'))
            ->assertSessionHasErrors(['status' => 'Pilih status izin atau sakit.']);
        $this->post(route('absence.store'), ['status' => ['izin']])
            ->assertSessionHasErrors(['status' => 'Status hanya boleh izin atau sakit.']);
        $this->assertDatabaseCount('attendances', 0);
        $this->post(route('absence.store'), ['status' => 'izin'])->assertSessionHasNoErrors();
        $this->get(route('dashboard'))->assertOk()->assertSee('Presensi hari ini sudah tercatat')
            ->assertDontSee('Sudah check-in hari ini');
    }

    public function test_each_attendance_endpoint_rejects_forged_server_fields(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        foreach (['check-in.store', 'check-out.store', 'absence.store'] as $route) {
            foreach (['user_id', 'attendance_date', 'check_in_time', 'check_out_time'] as $field) {
                $this->post(route($route), ['status' => 'izin', $field => 'forged'])
                    ->assertSessionHasErrors($field);
            }
        }
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_another_users_checkin_does_not_authorize_checkout(): void
    {
        Attendance::factory()->create(['attendance_date' => now()->toDateString(), 'check_in_time' => '07:00:00', 'check_out_time' => null, 'status' => 'hadir']);
        $before = Attendance::first()->toArray();
        $this->actingAs(User::factory()->create())->post(route('check-out.store'))
            ->assertSessionHasErrors(['check_out' => 'Anda belum check-in hari ini.']);
        $this->assertSame($before, Attendance::first()->toArray());
    }
}
