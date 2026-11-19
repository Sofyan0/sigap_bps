<?php

namespace App\Http\Controllers;

use App\Models\DashboardSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardSettingsController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admin_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'institution' => ['required', 'string', 'max:255'],
            'employee_id' => ['nullable', 'string', 'max:50'],
            'email_ews_enabled' => ['required', 'boolean'],
            'weekly_iph_enabled' => ['required', 'boolean'],
        ]);

        $settings = DashboardSetting::firstOrCreate(
            ['id' => 1],
            [
                'admin_name' => 'Admin TPID Situbondo',
                'institution' => 'Bagian Perekonomian Setda',
            ]
        );
        $settings->update($validated);

        return redirect()
            ->route('dashboard', ['tab' => 'pengaturan'])
            ->with('success', 'Pengaturan profil berhasil disimpan.');
    }
}
