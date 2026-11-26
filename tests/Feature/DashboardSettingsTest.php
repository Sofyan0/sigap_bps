<?php

namespace Tests\Feature;

use App\Models\DashboardSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DashboardSettingsTest extends DashboardTestCase
{
    use RefreshDatabase;

    public function test_settings_page_shows_profile_and_notification_values_from_database(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin Pengujian',
            'email' => 'admin@example.test',
            'institution' => 'Bagian Uji',
            'employee_id' => '123456',
        ]);
        $this->actingAs($user);

        DashboardSetting::create([
            'email_ews_enabled' => false,
            'weekly_iph_enabled' => true,
        ]);

        $response = $this->get('/?tab=pengaturan');

        $response->assertOk()
            ->assertSee('Admin Pengujian')
            ->assertSee('admin@example.test')
            ->assertSee('Bagian Uji')
            ->assertSee('123456')
            ->assertSee('name="name" value="Admin Pengujian"', false)
            ->assertSee('name="employee_id" value="123456"', false)
            ->assertSee('name="email" value="admin@example.test"', false)
            ->assertSee('name="institution" value="Bagian Uji"', false)
            ->assertDontSee('id="dashboardFilterForm"', false)
            ->assertSee('name="email_ews_enabled" value="0"', false)
            ->assertSee('name="weekly_iph_enabled" value="1" checked', false);
    }

    public function test_settings_form_saves_profile_and_notification_preferences(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('dashboard.settings.update'), [
            'name' => 'Admin Baru',
            'email' => 'admin.baru@example.test',
            'institution' => 'Bagian Perekonomian',
            'employee_id' => '987654',
            'email_ews_enabled' => '0',
            'weekly_iph_enabled' => '1',
        ]);

        $response->assertRedirect(route('dashboard', ['tab' => 'pengaturan']))
            ->assertSessionHas('success', 'Pengaturan profil berhasil disimpan.');

        $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Admin Baru',
        'email' => 'admin.baru@example.test',
        'institution' => 'Bagian Perekonomian',
        'employee_id' => '987654',
        ]);
        $this->assertDatabaseHas('dashboard_settings', [
            'email_ews_enabled' => false,
            'weekly_iph_enabled' => true,
        ]);
    }

    public function test_settings_form_rejects_invalid_email(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->from('/?tab=pengaturan')->post(route('dashboard.settings.update'), [
            'name' => 'Admin Baru',
            'email' => 'bukan-email',
            'institution' => 'Bagian Perekonomian',
            'employee_id' => '',
            'email_ews_enabled' => '1',
            'weekly_iph_enabled' => '0',
        ]);

        $response->assertRedirect('/?tab=pengaturan')
            ->assertSessionHasErrors('email');
    }

    public function test_profile_photo_is_saved_and_displayed_from_public_storage(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->actingAs($user);
        $photo = UploadedFile::fake()->image('profile.png');

        $this->post(route('dashboard.settings.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'institution' => 'Bagian Perekonomian',
            'employee_id' => '123456',
            'profile_photo' => $photo,
            'email_ews_enabled' => '1',
            'weekly_iph_enabled' => '1',
        ])->assertRedirect(route('dashboard', ['tab' => 'pengaturan']));

        $user->refresh();
        $this->assertSame('Bagian Perekonomian', $user->institution);
        $this->assertSame('123456', $user->employee_id);
        $this->assertNotNull($user->profile_photo_path);
        Storage::disk('public')->assertExists($user->profile_photo_path);
        $photoUrl = Storage::disk('public')->url($user->profile_photo_path);
        $this->assertStringStartsWith('/storage/', $photoUrl);

        $this->get('/?tab=pengaturan')
            ->assertOk()
            ->assertSee($photoUrl, false);
    }
}
