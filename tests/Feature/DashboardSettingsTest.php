<?php

namespace Tests\Feature;

use App\Models\DashboardSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_shows_profile_and_notification_values_from_database(): void
    {
        DashboardSetting::create([
            'admin_name' => 'Admin Pengujian',
            'email' => 'admin@example.test',
            'institution' => 'Bagian Uji',
            'employee_id' => '123456',
            'email_ews_enabled' => false,
            'weekly_iph_enabled' => true,
        ]);

        $response = $this->get('/?tab=pengaturan');

        $response->assertOk()
            ->assertSee('Admin Pengujian')
            ->assertSee('admin@example.test')
            ->assertSee('Bagian Uji')
            ->assertSee('123456')
            ->assertSee('name="email_ews_enabled" value="0"', false)
            ->assertSee('name="weekly_iph_enabled" value="1" checked', false);
    }

    public function test_settings_form_saves_profile_and_notification_preferences(): void
    {
        $response = $this->post(route('dashboard.settings.update'), [
            'admin_name' => 'Admin Baru',
            'email' => 'admin.baru@example.test',
            'institution' => 'Bagian Perekonomian',
            'employee_id' => '987654',
            'email_ews_enabled' => '0',
            'weekly_iph_enabled' => '1',
        ]);

        $response->assertRedirect(route('dashboard', ['tab' => 'pengaturan']))
            ->assertSessionHas('success', 'Pengaturan profil berhasil disimpan.');

        $this->assertDatabaseHas('dashboard_settings', [
            'admin_name' => 'Admin Baru',
            'email' => 'admin.baru@example.test',
            'institution' => 'Bagian Perekonomian',
            'employee_id' => '987654',
            'email_ews_enabled' => false,
            'weekly_iph_enabled' => true,
        ]);
    }

    public function test_settings_form_rejects_invalid_email(): void
    {
        $response = $this->from('/?tab=pengaturan')->post(route('dashboard.settings.update'), [
            'admin_name' => 'Admin Baru',
            'email' => 'bukan-email',
            'institution' => 'Bagian Perekonomian',
            'employee_id' => '',
            'email_ews_enabled' => '1',
            'weekly_iph_enabled' => '0',
        ]);

        $response->assertRedirect('/?tab=pengaturan')
            ->assertSessionHasErrors('email');
    }
}
