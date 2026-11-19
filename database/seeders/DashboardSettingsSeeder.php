<?php

namespace Database\Seeders;

use App\Models\DashboardSetting;
use Illuminate\Database\Seeder;

class DashboardSettingsSeeder extends Seeder
{
    public function run(): void
    {
        DashboardSetting::firstOrCreate(
            ['id' => 1],
            [
                'admin_name' => 'Admin TPID Situbondo',
                'email' => 'admin.tpid@situbondokab.go.id',
                'institution' => 'Bagian Perekonomian Setda',
                'employee_id' => '19850412 201001 1 008',
                'email_ews_enabled' => true,
                'weekly_iph_enabled' => true,
            ]
        );
    }
}
