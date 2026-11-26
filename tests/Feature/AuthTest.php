<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_login_and_registration_pages_and_dashboard_requires_authentication(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk ke SIGAP');

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Buat akun')
            ->assertSee('NIP / ID Pegawai')
            ->assertSee('Instansi');

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_registration_persists_a_hashed_password_and_authenticates_the_user(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Pengguna Baru',
            'employee_id' => '19850412',
            'email' => 'pengguna@example.test',
            'institution' => 'Bagian Perekonomian',
            'password' => 'password-aman',
            'password_confirmation' => 'password-aman',
        ])->assertRedirect(route('dashboard'));

        $user = User::where('email', 'pengguna@example.test')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'name' => 'Pengguna Baru',
            'employee_id' => '19850412',
            'email' => 'pengguna@example.test',
            'institution' => 'Bagian Perekonomian',
        ]);
        $this->assertNotSame('password-aman', $user->password);
        $this->assertTrue(Hash::check('password-aman', $user->password));
        $this->assertAuthenticatedAs($user);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pengguna Baru')
            ->assertSee('pengguna@example.test');
    }

    public function test_login_authenticates_a_registered_user(): void
    {
        $user = User::factory()->create([
            'email' => 'pengguna@example.test',
            'password' => Hash::make('password-aman'),
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password-aman',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_do_not_authenticate_the_user(): void
    {
        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'tidak-ada@example.test',
                'password' => 'password-salah',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout_invalidates_the_authenticated_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Anda berhasil keluar.');

        $this->assertGuest();
    }

    public function test_registration_validates_duplicate_email_and_password_confirmation(): void
    {
        User::factory()->create(['email' => 'existing@example.test']);

        $this->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Pengguna',
                'email' => 'existing@example.test',
                'password' => 'password-aman',
                'password_confirmation' => 'berbeda',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertDatabaseCount('users', 1);
    }
}
