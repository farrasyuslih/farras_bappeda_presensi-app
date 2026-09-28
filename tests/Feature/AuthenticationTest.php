<?php

namespace Tests\Feature;


use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_login_page(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertSee('Login')
            ->assertSee('Email')
            ->assertSee('Password');
    }
    public function test_guest_is_redirected_from_dashboard_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->post(route('login.authenticate'),[]);

        $response
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_login_rejects_invalid_email_format(): void
    {
        $response = $this->post(route('login.authenticate'), [
            'email' => 'email-tidak-valid',
            'password' => 'password',
        ]);

        $response
            ->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->from(route('login'))
            ->post(route('login.authenticate'), [
                'email' => $user->email,
                'password' => 'password-salah',
            ]);
        
        $response -> assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);
            // ->assertSessionHasErrors(['email' => 'Email atau password salah.',]);

            $this ->assertGuest();
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.authenticate'), [
            'email' => $user->email,
            'password' => 'password',
        ]); 

        $response -> assertRedirect(route('dashboard'))
            ->assertSessionHas('success', 'Login berhasil.');

        $this->assertAuthenticatedAs($user);
    }   

    public function test_authenticated_user_can_open_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email)
            ->assertSee('Belum ada aktivitas presensi');
    }

    public function test_authenticated_user_cannot_open_login_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/login');

        $response
            ->assertRedirect(route('dashboard'));
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Logout berhasil.');

        $this->assertGuest();
    }

    public function test_guest_cannot_logout(): void
    {
        $response = $this->post(route('logout'));

        $response
            ->assertRedirect(route('login'));
        
        $this->assertGuest();
    }

    public function test_logout_rejects_get_method(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/logout');

        $response->assertStatus(405);

        $this->assertAuthenticatedAs($user);
    }

    public function test_guest_can_open_register_page(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Daftar akun');
    }

    public function test_authenticated_user_cannot_open_register_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
        ->get(route('register'))
        ->assertRedirect(route('dashboard'));
    }

    public function test_register_requires_valid_fields(): void
    {
        $response = $this->post(route('register.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'password']);

        $this -> assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => $user->email,
            'password' => 'password123',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors(['email']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_rejects_password_mismatch(): void
    {
        $this -> post(route('register.store'), [
            'name' => 'Pegawai Baru',
            'email' => 'baru@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password456',
        ])->assertSessionHasErrors(['password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_guest_registers_as_pegawai_then_can_login(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Pegawai Baru',
            'email' => 'baru@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Registrasi berhasil. Silakan login.');

        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'name' => 'Pegawai Baru',
            'email' => 'baru@example.com',
            'role' => 'pegawai',
        ]);

        $user = User::where('email', 'baru@example.com')->firstOrFail();

        $this->assertNotSame('password123', $user->password);

        $this->post(route('login.authenticate'), [
            'email' => 'baru@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }
}
